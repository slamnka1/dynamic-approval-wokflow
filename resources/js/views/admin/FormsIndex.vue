<script setup>
import { ref, onMounted, computed } from 'vue';
import client from '../../api/client';
import { useUiStore } from '../../stores/ui';

const ui = useUiStore();
const forms = ref([]);
const loading = ref(true);
const search = ref('');

const fetchAll = async () => {
    loading.value = true;
    try {
        const { data } = await client.get('/admin/forms');
        forms.value = data.data;
    } finally {
        loading.value = false;
    }
};

const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return forms.value;
    return forms.value.filter((f) =>
        f.name.toLowerCase().includes(q) || (f.description ?? '').toLowerCase().includes(q),
    );
});

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
        <div class="flex items-center justify-between gap-4 mb-6 flex-wrap">
            <div>
                <h1 class="text-2xl font-bold text-ink-900">Forms</h1>
                <p class="text-sm muted mt-1">Build forms and route them through approval workflows.</p>
            </div>
            <router-link :to="{ name: 'admin.forms.new' }" class="btn-primary">
                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                New form
            </router-link>
        </div>

        <div class="mb-4 max-w-sm relative">
            <span class="absolute inset-y-0 left-3 flex items-center text-ink-400">
                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
            </span>
            <input v-model="search" placeholder="Search forms…" class="input pl-9" />
        </div>

        <div v-if="loading" class="card p-8 text-center text-ink-500">Loading…</div>

        <div v-else-if="filtered.length === 0" class="card p-10 text-center">
            <div class="mx-auto h-12 w-12 rounded-full bg-ink-100 text-ink-500 flex items-center justify-center">
                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="2"/><path d="M8 9h8M8 13h8M8 17h5"/></svg>
            </div>
            <p class="mt-3 text-sm muted">{{ search ? 'No forms match your search.' : 'No forms yet — create your first one.' }}</p>
            <router-link v-if="!search" :to="{ name: 'admin.forms.new' }" class="btn-primary mt-4 inline-flex">Create form</router-link>
        </div>

        <div v-else class="card overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-ink-50 text-ink-500">
                    <tr>
                        <th class="text-left px-5 py-3 font-medium">Name</th>
                        <th class="text-left px-5 py-3 font-medium">Fields</th>
                        <th class="text-left px-5 py-3 font-medium">Workflow</th>
                        <th class="text-left px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    <tr v-for="f in filtered" :key="f.id" class="hover:bg-ink-50/60 transition">
                        <td class="px-5 py-3">
                            <div class="font-medium text-ink-900">{{ f.name }}</div>
                            <div v-if="f.description" class="text-xs muted truncate max-w-md">{{ f.description }}</div>
                        </td>
                        <td class="px-5 py-3">
                            <span class="chip">
                                <svg viewBox="0 0 24 24" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
                                {{ f.fields.length }}
                            </span>
                        </td>
                        <td class="px-5 py-3">
                            <div v-if="f.workflow" class="flex items-center gap-2">
                                <span class="font-medium text-ink-800">{{ f.workflow.name }}</span>
                                <span class="chip" :class="f.workflow.type === 'sequential' ? 'bg-sky-100 text-sky-700' : 'bg-violet-100 text-violet-700'">
                                    {{ f.workflow.type }}
                                </span>
                            </div>
                            <span v-else class="chip bg-rose-100 text-rose-700">no workflow</span>
                        </td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="h-2 w-2 rounded-full" :class="f.is_active ? 'bg-emerald-500' : 'bg-ink-300'"></span>
                                <span :class="f.is_active ? 'text-emerald-700' : 'muted'">{{ f.is_active ? 'Active' : 'Inactive' }}</span>
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <div class="inline-flex items-center gap-1">
                                <router-link :to="{ name: 'admin.forms.edit', params: { id: f.id } }" class="btn-ghost px-2.5 py-1 text-xs">
                                    Edit
                                </router-link>
                                <button @click="remove(f)" class="btn-ghost px-2.5 py-1 text-xs text-rose-600 hover:bg-rose-50 hover:text-rose-700">
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
