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
        <h1 class="text-xl font-semibold mb-4">Pending approvals</h1>
        <div v-if="loading" class="text-slate-500">Loading…</div>
        <div v-else-if="list.length === 0" class="text-slate-500">Nothing waiting on you. 🎉</div>

        <div v-else class="space-y-2">
            <router-link
                v-for="r in list" :key="r.id"
                :to="{ name: 'approvals.show', params: { id: r.id } }"
                class="block bg-white border border-slate-200 rounded-lg p-4 hover:shadow-md transition"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <div class="font-semibold">{{ r.form.name }}</div>
                        <div class="text-sm text-slate-500">By {{ r.requester?.name }} · {{ r.workflow?.name }} ({{ r.workflow?.type }})</div>
                    </div>
                    <StatusBadge :status="r.status" />
                </div>
            </router-link>
        </div>
    </div>
</template>
