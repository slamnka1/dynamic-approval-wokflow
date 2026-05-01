<script setup>
import { ref, onMounted, computed } from 'vue';
import client from '../../api/client';
import StatusBadge from '../../components/StatusBadge.vue';

const list = ref([]);
const loading = ref(true);
const filter = ref('all');

onMounted(async () => {
    try {
        const { data } = await client.get('/approvals/past');
        list.value = data.data;
    } finally {
        loading.value = false;
    }
});

const filtered = computed(() => {
    if (filter.value === 'all') return list.value;
    return list.value.filter((r) => r.status === filter.value);
});

const counts = computed(() => ({
    all:      list.value.length,
    approved: list.value.filter((r) => r.status === 'approved').length,
    rejected: list.value.filter((r) => r.status === 'rejected').length,
}));
</script>

<template>
    <div>
        <div class="flex items-center justify-between gap-4 mb-6 flex-wrap">
            <div>
                <h1 class="text-2xl font-bold text-ink-900">Past approvals</h1>
                <p class="text-sm muted mt-1">Everything you've already acted on.</p>
            </div>
        </div>

        <div class="flex flex-wrap gap-2 mb-4">
            <button
                v-for="tab in [
                    { key: 'all', label: 'All' },
                    { key: 'approved', label: 'Approved' },
                    { key: 'rejected', label: 'Rejected' },
                ]" :key="tab.key"
                @click="filter = tab.key"
                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium transition"
                :class="filter === tab.key ? 'bg-brand-600 text-white' : 'bg-white border border-ink-200 text-ink-600 hover:bg-ink-50'"
            >
                {{ tab.label }}
                <span class="text-xs px-1.5 py-0.5 rounded-full" :class="filter === tab.key ? 'bg-white/20' : 'bg-ink-100 text-ink-500'">{{ counts[tab.key] }}</span>
            </button>
        </div>

        <div v-if="loading" class="card p-8 text-center muted">Loading…</div>

        <div v-else-if="filtered.length === 0" class="card p-10 text-center">
            <div class="mx-auto h-12 w-12 rounded-full bg-ink-100 text-ink-500 flex items-center justify-center">
                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
            </div>
            <p class="mt-3 text-sm muted">No history yet.</p>
        </div>

        <div v-else class="card overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-ink-50 text-ink-500">
                    <tr>
                        <th class="text-left px-5 py-3 font-medium">Form</th>
                        <th class="text-left px-5 py-3 font-medium">Requester</th>
                        <th class="text-left px-5 py-3 font-medium">Workflow</th>
                        <th class="text-left px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    <tr v-for="r in filtered" :key="r.id" class="hover:bg-ink-50/60 transition">
                        <td class="px-5 py-3 font-medium text-ink-900">{{ r.form.name }}</td>
                        <td class="px-5 py-3 text-ink-700">{{ r.requester?.name }}</td>
                        <td class="px-5 py-3">
                            <span class="chip" :class="r.workflow?.type === 'sequential' ? 'bg-sky-100 text-sky-700' : 'bg-violet-100 text-violet-700'">{{ r.workflow?.type }}</span>
                        </td>
                        <td class="px-5 py-3"><StatusBadge :status="r.status" /></td>
                        <td class="px-5 py-3 text-right">
                            <router-link :to="{ name: 'approvals.show', params: { id: r.id } }" class="text-brand-600 hover:underline text-sm font-medium">View →</router-link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
