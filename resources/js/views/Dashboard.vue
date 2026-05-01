<script setup>
import { ref, onMounted, computed } from 'vue';
import { useAuthStore } from '../stores/auth';
import client from '../api/client';
import StatusBadge from '../components/StatusBadge.vue';

const auth = useAuthStore();

const myRequests = ref([]);
const pending = ref([]);
const forms = ref([]);
const loading = ref(true);

onMounted(async () => {
    try {
        const calls = [
            client.get('/my/requests').then((r) => myRequests.value = r.data.data ?? []).catch(() => {}),
            client.get('/forms').then((r) => forms.value = r.data.data ?? []).catch(() => {}),
        ];
        if (auth.isApprover) {
            calls.push(client.get('/approvals/pending').then((r) => pending.value = r.data.data ?? []).catch(() => {}));
        }
        await Promise.all(calls);
    } finally {
        loading.value = false;
    }
});

const stats = computed(() => ({
    pendingMine:    myRequests.value.filter((r) => r.status === 'pending').length,
    approvedMine:   myRequests.value.filter((r) => r.status === 'approved').length,
    rejectedMine:   myRequests.value.filter((r) => r.status === 'rejected').length,
    totalForms:     forms.value.length,
    pendingOnMe:    pending.value.length,
}));

const recent = computed(() => [...myRequests.value].sort((a, b) => new Date(b.created_at) - new Date(a.created_at)).slice(0, 5));

const greeting = computed(() => {
    const h = new Date().getHours();
    if (h < 12) return 'Good morning';
    if (h < 18) return 'Good afternoon';
    return 'Good evening';
});
</script>

<template>
    <div class="space-y-8">
        <!-- Hero -->
        <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-brand-600 via-brand-600 to-violet-600 text-white p-6 sm:p-8">
            <div class="absolute -top-24 -right-16 h-64 w-64 rounded-full bg-white/10 blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-24 -left-16 h-64 w-64 rounded-full bg-fuchsia-500/20 blur-3xl pointer-events-none"></div>

            <div class="relative flex items-start justify-between gap-4 flex-wrap">
                <div>
                    <p class="text-white/70 text-sm">{{ greeting }},</p>
                    <h1 class="text-2xl sm:text-3xl font-bold">{{ auth.user.name }}</h1>
                    <p class="text-white/80 mt-2 max-w-xl">Here's a quick view of what's moving through your workflows today.</p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <router-link :to="{ name: 'forms' }" class="bg-white text-brand-700 hover:bg-white/90 btn">
                        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                        New request
                    </router-link>
                    <router-link v-if="auth.isAdmin" :to="{ name: 'admin.forms.new' }" class="btn bg-white/15 hover:bg-white/25 text-white ring-1 ring-white/20 backdrop-blur">
                        Build form
                    </router-link>
                </div>
            </div>
        </section>

        <!-- Stats -->
        <section class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="card p-5">
                <div class="flex items-center justify-between">
                    <span class="text-xs uppercase tracking-wider muted">Pending (mine)</span>
                    <span class="h-9 w-9 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                        <svg viewBox="0 0 24 24" class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    </span>
                </div>
                <div class="text-3xl font-bold text-ink-900 mt-3">{{ stats.pendingMine }}</div>
            </div>
            <div class="card p-5">
                <div class="flex items-center justify-between">
                    <span class="text-xs uppercase tracking-wider muted">Approved</span>
                    <span class="h-9 w-9 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg viewBox="0 0 24 24" class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
                    </span>
                </div>
                <div class="text-3xl font-bold text-ink-900 mt-3">{{ stats.approvedMine }}</div>
            </div>
            <div class="card p-5">
                <div class="flex items-center justify-between">
                    <span class="text-xs uppercase tracking-wider muted">Rejected</span>
                    <span class="h-9 w-9 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
                        <svg viewBox="0 0 24 24" class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M15 9l-6 6M9 9l6 6"/></svg>
                    </span>
                </div>
                <div class="text-3xl font-bold text-ink-900 mt-3">{{ stats.rejectedMine }}</div>
            </div>
            <div class="card p-5">
                <div class="flex items-center justify-between">
                    <span class="text-xs uppercase tracking-wider muted">{{ auth.isApprover ? 'On your desk' : 'Active forms' }}</span>
                    <span class="h-9 w-9 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center">
                        <svg viewBox="0 0 24 24" class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="2"/><path d="M8 9h8M8 13h8M8 17h5"/></svg>
                    </span>
                </div>
                <div class="text-3xl font-bold text-ink-900 mt-3">{{ auth.isApprover ? stats.pendingOnMe : stats.totalForms }}</div>
            </div>
        </section>

        <!-- Quick actions -->
        <section>
            <h2 class="section-title mb-3">Quick actions</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <router-link v-if="auth.isAdmin" :to="{ name: 'admin.forms' }" class="card card-hover p-5 group">
                    <div class="flex items-start gap-3">
                        <div class="h-10 w-10 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center group-hover:bg-violet-100 transition">
                            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1A2 2 0 1 1 4.4 17l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1z"/></svg>
                        </div>
                        <div>
                            <div class="text-xs uppercase tracking-wider text-violet-600 font-semibold">Admin</div>
                            <div class="font-semibold text-ink-900 mt-0.5">Manage forms</div>
                            <p class="text-sm muted mt-1">Build forms and configure approval workflows.</p>
                        </div>
                    </div>
                </router-link>

                <router-link :to="{ name: 'forms' }" class="card card-hover p-5 group">
                    <div class="flex items-start gap-3">
                        <div class="h-10 w-10 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center group-hover:bg-brand-100 transition">
                            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                        </div>
                        <div>
                            <div class="text-xs uppercase tracking-wider text-brand-600 font-semibold">Submit</div>
                            <div class="font-semibold text-ink-900 mt-0.5">Available forms</div>
                            <p class="text-sm muted mt-1">Browse what's open and file a request.</p>
                        </div>
                    </div>
                </router-link>

                <router-link :to="{ name: 'my.requests' }" class="card card-hover p-5 group">
                    <div class="flex items-start gap-3">
                        <div class="h-10 w-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center group-hover:bg-sky-100 transition">
                            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h5l2 3h4l2-3h5"/><path d="M3 12V6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v6"/></svg>
                        </div>
                        <div>
                            <div class="text-xs uppercase tracking-wider text-sky-600 font-semibold">Track</div>
                            <div class="font-semibold text-ink-900 mt-0.5">My requests</div>
                            <p class="text-sm muted mt-1">Status and full action trail of submissions.</p>
                        </div>
                    </div>
                </router-link>

                <router-link v-if="auth.isApprover" :to="{ name: 'approvals.pending' }" class="card card-hover p-5 group">
                    <div class="flex items-start gap-3">
                        <div class="h-10 w-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center group-hover:bg-emerald-100 transition">
                            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/></svg>
                        </div>
                        <div>
                            <div class="text-xs uppercase tracking-wider text-emerald-600 font-semibold">Approve</div>
                            <div class="font-semibold text-ink-900 mt-0.5">Pending approvals</div>
                            <p class="text-sm muted mt-1">Items waiting on your decision.</p>
                        </div>
                    </div>
                </router-link>
            </div>
        </section>

        <!-- Recent activity -->
        <section v-if="!loading && recent.length">
            <div class="flex items-center justify-between mb-3">
                <h2 class="section-title">Recent activity</h2>
                <router-link :to="{ name: 'my.requests' }" class="text-sm text-brand-600 hover:underline">View all</router-link>
            </div>
            <div class="card divide-y divide-ink-100">
                <router-link
                    v-for="r in recent" :key="r.id"
                    :to="{ name: 'my.requests.show', params: { id: r.id } }"
                    class="flex items-center justify-between p-4 hover:bg-ink-50 transition"
                >
                    <div class="min-w-0">
                        <div class="font-medium text-ink-900 truncate">{{ r.form.name }}</div>
                        <div class="text-xs muted mt-0.5">{{ r.workflow?.name }} · {{ new Date(r.created_at).toLocaleString() }}</div>
                    </div>
                    <StatusBadge :status="r.status" />
                </router-link>
            </div>
        </section>
    </div>
</template>
