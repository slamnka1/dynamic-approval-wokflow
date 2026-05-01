<script setup>
import { ref, onMounted, computed } from 'vue';
import client from '../../api/client';
import StatusBadge from '../../components/StatusBadge.vue';

const requests = ref([]);
const loading = ref(true);
const filter = ref('all');

onMounted(async () => {
    try {
        const { data } = await client.get('/my/requests');
        requests.value = data.data;
    } finally {
        loading.value = false;
    }
});

const filtered = computed(() => {
    if (filter.value === 'all') return requests.value;
    return requests.value.filter((r) => r.status === filter.value);
});

const counts = computed(() => ({
    all:       requests.value.length,
    pending:   requests.value.filter((r) => r.status === 'pending').length,
    approved:  requests.value.filter((r) => r.status === 'approved').length,
    rejected:  requests.value.filter((r) => r.status === 'rejected').length,
}));
</script>

<template>
    <div>
        <div class="flex items-center justify-between gap-4 mb-6 flex-wrap">
            <div>
                <h1 class="text-2xl font-bold text-ink-900">My requests</h1>
                <p class="text-sm muted mt-1">Track every submission you've made.</p>
            </div>
            <router-link :to="{ name: 'forms' }" class="btn-primary">
                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                New request
            </router-link>
        </div>

        <div class="flex flex-wrap gap-2 mb-4">
            <button
                v-for="tab in [
                    { key: 'all', label: 'All' },
                    { key: 'pending', label: 'Pending' },
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
                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h5l2 3h4l2-3h5"/><path d="M3 12V6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v6"/></svg>
            </div>
            <p class="mt-3 text-sm muted">{{ filter === 'all' ? "You haven't submitted anything yet." : `No ${filter} requests.` }}</p>
            <router-link v-if="filter === 'all'" :to="{ name: 'forms' }" class="btn-primary mt-4 inline-flex">Submit your first</router-link>
        </div>

        <div v-else class="card overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-ink-50 text-ink-500">
                    <tr>
                        <th class="text-left px-5 py-3 font-medium">Form</th>
                        <th class="text-left px-5 py-3 font-medium">Workflow</th>
                        <th class="text-left px-5 py-3 font-medium">Status</th>
                        <th class="text-left px-5 py-3 font-medium">Submitted</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    <tr v-for="r in filtered" :key="r.id" class="hover:bg-ink-50/60 transition">
                        <td class="px-5 py-3 font-medium text-ink-900">{{ r.form.name }}</td>
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-2">
                                <span class="muted">{{ r.workflow?.name }}</span>
                                <span class="chip" :class="r.workflow?.type === 'sequential' ? 'bg-sky-100 text-sky-700' : 'bg-violet-100 text-violet-700'">
                                    {{ r.workflow?.type }}
                                </span>
                            </div>
                        </td>
                        <td class="px-5 py-3"><StatusBadge :status="r.status" /></td>
                        <td class="px-5 py-3 muted whitespace-nowrap">{{ new Date(r.created_at).toLocaleString() }}</td>
                        <td class="px-5 py-3 text-right">
                            <router-link :to="{ name: 'my.requests.show', params: { id: r.id } }" class="text-brand-600 hover:underline text-sm font-medium">View →</router-link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
