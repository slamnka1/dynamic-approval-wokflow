<script setup>
import { ref, onMounted } from 'vue';
import client from '../../api/client';
import StatusBadge from '../../components/StatusBadge.vue';

const list = ref([]);
const loading = ref(true);

onMounted(async () => {
    try {
        const { data } = await client.get('/approvals/past');
        list.value = data.data;
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div>
        <h1 class="text-xl font-semibold mb-4">Past approvals</h1>
        <div v-if="loading" class="text-slate-500">Loading…</div>
        <div v-else-if="list.length === 0" class="text-slate-500">No history yet.</div>

        <div v-else class="bg-white border border-slate-200 rounded-lg overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left px-4 py-2">Form</th>
                        <th class="text-left px-4 py-2">Requester</th>
                        <th class="text-left px-4 py-2">Status</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="r in list" :key="r.id" class="border-t border-slate-100">
                        <td class="px-4 py-2 font-medium">{{ r.form.name }}</td>
                        <td class="px-4 py-2 text-slate-500">{{ r.requester?.name }}</td>
                        <td class="px-4 py-2"><StatusBadge :status="r.status" /></td>
                        <td class="px-4 py-2 text-right">
                            <router-link :to="{ name: 'approvals.show', params: { id: r.id } }" class="text-indigo-600 hover:underline">View</router-link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
