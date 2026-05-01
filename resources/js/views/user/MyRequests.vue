<script setup>
import { ref, onMounted } from 'vue';
import client from '../../api/client';
import StatusBadge from '../../components/StatusBadge.vue';

const requests = ref([]);
const loading = ref(true);

onMounted(async () => {
    try {
        const { data } = await client.get('/my/requests');
        requests.value = data.data;
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div>
        <h1 class="text-xl font-semibold mb-4">My requests</h1>

        <div v-if="loading" class="text-slate-500">Loading…</div>
        <div v-else-if="requests.length === 0" class="text-slate-500">You haven't submitted anything yet.</div>

        <div v-else class="bg-white border border-slate-200 rounded-lg overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left px-4 py-2">Form</th>
                        <th class="text-left px-4 py-2">Workflow</th>
                        <th class="text-left px-4 py-2">Status</th>
                        <th class="text-left px-4 py-2">Submitted</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="r in requests" :key="r.id" class="border-t border-slate-100">
                        <td class="px-4 py-2 font-medium">{{ r.form.name }}</td>
                        <td class="px-4 py-2 text-slate-500">{{ r.workflow?.name }} <span class="text-xs text-slate-400">({{ r.workflow?.type }})</span></td>
                        <td class="px-4 py-2"><StatusBadge :status="r.status" /></td>
                        <td class="px-4 py-2 text-slate-500">{{ new Date(r.created_at).toLocaleString() }}</td>
                        <td class="px-4 py-2 text-right">
                            <router-link :to="{ name: 'my.requests.show', params: { id: r.id } }" class="text-indigo-600 hover:underline">View</router-link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
