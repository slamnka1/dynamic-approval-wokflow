<script setup>
import { ref, onMounted, computed } from 'vue';
import client from '../../api/client';

const forms = ref([]);
const loading = ref(true);
const search = ref('');

onMounted(async () => {
    try {
        const { data } = await client.get('/forms');
        forms.value = data.data;
    } finally {
        loading.value = false;
    }
});

const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return forms.value;
    return forms.value.filter((f) =>
        f.name.toLowerCase().includes(q) || (f.description ?? '').toLowerCase().includes(q),
    );
});

const accent = (i) => {
    const palette = [
        'from-brand-500 to-violet-500',
        'from-emerald-500 to-teal-500',
        'from-rose-500 to-orange-500',
        'from-sky-500 to-cyan-500',
        'from-amber-500 to-rose-500',
        'from-fuchsia-500 to-purple-500',
    ];
    return palette[i % palette.length];
};
</script>

<template>
    <div>
        <div class="flex items-center justify-between gap-4 mb-6 flex-wrap">
            <div>
                <h1 class="text-2xl font-bold text-ink-900">Available forms</h1>
                <p class="text-sm muted mt-1">Browse what's open and start a new request.</p>
            </div>
            <div class="relative w-full sm:w-auto sm:min-w-72">
                <span class="absolute inset-y-0 left-3 flex items-center text-ink-400">
                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
                </span>
                <input v-model="search" placeholder="Search forms…" class="input pl-9" />
            </div>
        </div>

        <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="i in 6" :key="i" class="card p-5 animate-pulse">
                <div class="h-5 w-1/2 bg-ink-100 rounded"></div>
                <div class="h-3 w-3/4 bg-ink-100 rounded mt-3"></div>
                <div class="h-3 w-2/3 bg-ink-100 rounded mt-2"></div>
                <div class="h-8 w-32 bg-ink-100 rounded mt-5"></div>
            </div>
        </div>

        <div v-else-if="filtered.length === 0" class="card p-10 text-center">
            <div class="mx-auto h-12 w-12 rounded-full bg-ink-100 text-ink-500 flex items-center justify-center">
                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="2"/><path d="M8 9h8M8 13h8M8 17h5"/></svg>
            </div>
            <p class="mt-3 text-sm muted">{{ search ? 'No forms match your search.' : 'No active forms yet.' }}</p>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="(f, i) in filtered" :key="f.id" class="card card-hover overflow-hidden flex flex-col">
                <div class="h-1.5 w-full bg-gradient-to-r" :class="accent(i)"></div>
                <div class="p-5 flex-1 flex flex-col">
                    <h2 class="font-semibold text-lg text-ink-900">{{ f.name }}</h2>
                    <p class="text-sm muted mt-1 line-clamp-3 flex-1">{{ f.description || 'No description provided.' }}</p>
                    <div class="flex items-center gap-2 mt-4">
                        <span class="chip">
                            <svg viewBox="0 0 24 24" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
                            {{ f.fields.length }} field{{ f.fields.length === 1 ? '' : 's' }}
                        </span>
                        <span v-if="f.workflow" class="chip" :class="f.workflow.type === 'sequential' ? 'bg-sky-100 text-sky-700' : 'bg-violet-100 text-violet-700'">
                            {{ f.workflow.type }}
                        </span>
                    </div>
                    <router-link :to="{ name: 'forms.submit', params: { id: f.id } }" class="btn-primary mt-5 self-start">
                        Submit a request
                        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </router-link>
                </div>
            </div>
        </div>
    </div>
</template>
