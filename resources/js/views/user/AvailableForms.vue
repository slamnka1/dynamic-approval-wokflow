<script setup>
import { ref, onMounted } from 'vue';
import client from '../../api/client';

const forms = ref([]);
const loading = ref(true);

onMounted(async () => {
    try {
        const { data } = await client.get('/forms');
        forms.value = data.data;
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div>
        <h1 class="text-xl font-semibold mb-4">Available forms</h1>

        <div v-if="loading" class="text-slate-500">Loading…</div>
        <div v-else-if="forms.length === 0" class="text-slate-500">No active forms yet.</div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-for="f in forms" :key="f.id" class="bg-white border border-slate-200 rounded-lg p-5">
                <h2 class="font-semibold text-lg">{{ f.name }}</h2>
                <p class="text-sm text-slate-500 mt-1">{{ f.description || 'No description.' }}</p>
                <div class="text-xs text-slate-400 mt-2">{{ f.fields.length }} field(s)</div>
                <router-link :to="{ name: 'forms.submit', params: { id: f.id } }" class="inline-block mt-3 text-sm bg-indigo-600 text-white px-3 py-1.5 rounded-md hover:bg-indigo-700">
                    Submit a request
                </router-link>
            </div>
        </div>
    </div>
</template>
