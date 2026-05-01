<script setup>
import { ref, onMounted } from 'vue';
import client from '../../api/client';
import { useUiStore } from '../../stores/ui';

const ui = useUiStore();
const forms = ref([]);
const loading = ref(true);

const fetchAll = async () => {
    loading.value = true;
    try {
        const { data } = await client.get('/admin/forms');
        forms.value = data.data;
    } finally {
        loading.value = false;
    }
};

const remove = async (form) => {
    if (!confirm(`Delete "${form.name}"? This cascades to its requests.`)) return;
    try {
        await client.delete(`/admin/forms/${form.id}`);
        forms.value = forms.value.filter((f) => f.id !== form.id);
        ui.success('Deleted.');
    } catch (e) {
        ui.error(e.response?.data?.message || 'Delete failed.');
    }
};

onMounted(fetchAll);
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">Forms</h1>
            <router-link :to="{ name: 'admin.forms.new' }" class="bg-indigo-600 text-white text-sm px-4 py-2 rounded-md hover:bg-indigo-700">New form</router-link>
        </div>

        <div v-if="loading" class="text-slate-500">Loading…</div>
        <div v-else-if="forms.length === 0" class="text-slate-500">No forms yet — create your first one.</div>

        <div v-else class="bg-white border border-slate-200 rounded-lg overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left px-4 py-2">Name</th>
                        <th class="text-left px-4 py-2">Fields</th>
                        <th class="text-left px-4 py-2">Workflow</th>
                        <th class="text-left px-4 py-2">Active</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="f in forms" :key="f.id" class="border-t border-slate-100">
                        <td class="px-4 py-2 font-medium">{{ f.name }}</td>
                        <td class="px-4 py-2 text-slate-500">{{ f.fields.length }}</td>
                        <td class="px-4 py-2 text-slate-500">
                            <span v-if="f.workflow">{{ f.workflow.name }} ({{ f.workflow.type }})</span>
                            <span v-else class="text-rose-600">none</span>
                        </td>
                        <td class="px-4 py-2">
                            <span :class="f.is_active ? 'text-emerald-700' : 'text-slate-500'">{{ f.is_active ? 'Yes' : 'No' }}</span>
                        </td>
                        <td class="px-4 py-2 text-right space-x-3">
                            <router-link :to="{ name: 'admin.forms.edit', params: { id: f.id } }" class="text-indigo-600 hover:underline">Edit</router-link>
                            <button @click="remove(f)" class="text-rose-600 hover:underline">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
