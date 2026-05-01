<script setup>
import { ref, onMounted } from 'vue';
import client from '../../api/client';
import StatusBadge from '../../components/StatusBadge.vue';

const list = ref([]);
const loading = ref(true);

onMounted(async () => {
    try {
        const { data } = await client.get('/approvals/pending');
        list.value = data.data;
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div>
        <div class="flex items-center justify-between gap-4 mb-6 flex-wrap">
            <div>
                <h1 class="text-2xl font-bold text-ink-900">Pending approvals</h1>
                <p class="text-sm muted mt-1">Items waiting on your decision.</p>
            </div>
            <span v-if="list.length" class="chip bg-amber-50 text-amber-700">
                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                {{ list.length }} waiting
            </span>
        </div>

        <div v-if="loading" class="card p-8 text-center muted">Loading…</div>

        <div v-else-if="list.length === 0" class="card p-10 text-center">
            <div class="mx-auto h-14 w-14 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <svg viewBox="0 0 24 24" class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            </div>
            <p class="mt-3 font-medium text-ink-800">All caught up</p>
            <p class="text-sm muted">Nothing waiting on you right now.</p>
        </div>

        <div v-else class="space-y-3">
            <router-link
                v-for="r in list" :key="r.id"
                :to="{ name: 'approvals.show', params: { id: r.id } }"
                class="card card-hover p-5 flex items-center gap-4"
            >
                <div class="h-11 w-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="font-semibold text-ink-900 truncate">{{ r.form.name }}</div>
                    <div class="text-sm muted truncate">By <span class="text-ink-700 font-medium">{{ r.requester?.name }}</span> · {{ r.workflow?.name }}</div>
                </div>
                <span class="chip" :class="r.workflow?.type === 'sequential' ? 'bg-sky-100 text-sky-700' : 'bg-violet-100 text-violet-700'">{{ r.workflow?.type }}</span>
                <StatusBadge :status="r.status" />
                <svg viewBox="0 0 24 24" class="h-4 w-4 text-ink-400" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
            </router-link>
        </div>
    </div>
</template>
