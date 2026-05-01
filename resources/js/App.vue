<script setup>
import { computed } from 'vue';
import { useAuthStore } from './stores/auth';
import { useUiStore } from './stores/ui';
import { useRouter, useRoute } from 'vue-router';

const auth = useAuthStore();
const ui = useUiStore();
const router = useRouter();
const route = useRoute();

const logout = async () => {
    await auth.logout();
    router.push({ name: 'login' });
};

const initials = computed(() => {
    const n = auth.user?.name || '';
    return n.split(' ').map((p) => p[0]).filter(Boolean).slice(0, 2).join('').toUpperCase() || 'U';
});

const roleBadge = computed(() => {
    const map = {
        admin:     'bg-violet-100 text-violet-700 ring-violet-200',
        approver:  'bg-emerald-100 text-emerald-700 ring-emerald-200',
        requester: 'bg-sky-100 text-sky-700 ring-sky-200',
    };
    return map[auth.role] || 'bg-ink-100 text-ink-700 ring-ink-200';
});

const links = computed(() => {
    const items = [
        { name: 'dashboard',         label: 'Dashboard',  icon: 'home',   show: true },
        { name: 'forms',             label: 'Forms',      icon: 'forms',  show: true },
        { name: 'my.requests',       label: 'My Requests',icon: 'inbox',  show: true },
        { name: 'approvals.pending', label: 'Pending',    icon: 'check',  show: auth.isApprover },
        { name: 'approvals.past',    label: 'History',    icon: 'clock',  show: auth.isApprover },
        { name: 'admin.forms',       label: 'Manage Forms', icon: 'cog',  show: auth.isAdmin },
    ];
    return items.filter((i) => i.show);
});

const isActive = (name) => route.name === name || route.name?.startsWith(name + '.');
</script>

<template>
    <div v-if="auth.isAuthenticated" class="min-h-screen flex">
        <!-- Sidebar -->
        <aside class="hidden md:flex md:flex-col w-64 shrink-0 border-r border-ink-200 bg-white/70 backdrop-blur-sm">
            <div class="px-5 py-5 flex items-center gap-2.5">
                <div class="h-9 w-9 rounded-xl bg-gradient-to-br from-brand-500 to-violet-500 flex items-center justify-center shadow-sm">
                    <svg viewBox="0 0 24 24" class="h-5 w-5 text-white" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 12l2 2 4-4" /><circle cx="12" cy="12" r="9" />
                    </svg>
                </div>
                <div>
                    <div class="font-bold text-ink-900 leading-tight">CyberAgora</div>
                    <div class="text-[11px] uppercase tracking-wider text-ink-400">Approval Workflows</div>
                </div>
            </div>

            <nav class="px-3 py-2 space-y-1">
                <router-link
                    v-for="l in links" :key="l.name"
                    :to="{ name: l.name }"
                    class="nav-link w-full"
                    :class="{ 'nav-link-active': isActive(l.name) }"
                >
                    <span class="text-current">
                        <svg v-if="l.icon === 'home'" viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12l9-9 9 9"/><path d="M5 10v10h14V10"/></svg>
                        <svg v-else-if="l.icon === 'forms'" viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="2"/><path d="M8 9h8M8 13h8M8 17h5"/></svg>
                        <svg v-else-if="l.icon === 'inbox'" viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h5l2 3h4l2-3h5"/><path d="M3 12V6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v6"/><path d="M3 12v6a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-6"/></svg>
                        <svg v-else-if="l.icon === 'check'" viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        <svg v-else-if="l.icon === 'clock'" viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                        <svg v-else-if="l.icon === 'cog'" viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1A2 2 0 1 1 4.4 17l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.8l-.1-.1A2 2 0 1 1 7 4.4l.1.1a1.7 1.7 0 0 0 1.8.3h.1a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1A2 2 0 1 1 19.6 7l-.1.1a1.7 1.7 0 0 0-.3 1.8v.1a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/></svg>
                    </span>
                    {{ l.label }}
                </router-link>
            </nav>

            <div class="mt-auto px-3 py-4 border-t border-ink-200">
                <div class="flex items-center gap-3 px-2 py-2">
                    <div class="h-9 w-9 rounded-full bg-gradient-to-br from-brand-500 to-fuchsia-500 text-white text-xs font-semibold flex items-center justify-center shadow-sm">{{ initials }}</div>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-medium text-ink-900 truncate">{{ auth.user.name }}</div>
                        <span class="inline-flex items-center text-[10px] uppercase tracking-wider px-1.5 py-0.5 rounded ring-1" :class="roleBadge">{{ auth.role }}</span>
                    </div>
                </div>
                <button @click="logout" class="w-full mt-2 nav-link justify-start text-rose-600 hover:bg-rose-50 hover:text-rose-700">
                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
                    Sign out
                </button>
            </div>
        </aside>

        <!-- Mobile top bar -->
        <header class="md:hidden fixed top-0 inset-x-0 z-40 bg-white/80 backdrop-blur border-b border-ink-200">
            <div class="px-4 h-14 flex items-center justify-between">
                <router-link :to="{ name: 'dashboard' }" class="flex items-center gap-2">
                    <div class="h-8 w-8 rounded-lg bg-gradient-to-br from-brand-500 to-violet-500 flex items-center justify-center">
                        <svg viewBox="0 0 24 24" class="h-4 w-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
                    </div>
                    <span class="font-bold">CyberAgora</span>
                </router-link>
                <button @click="logout" class="text-sm text-rose-600">Sign out</button>
            </div>
            <nav class="px-2 pb-2 flex gap-1 overflow-x-auto">
                <router-link v-for="l in links" :key="l.name" :to="{ name: l.name }" class="nav-link whitespace-nowrap" :class="{ 'nav-link-active': isActive(l.name) }">
                    {{ l.label }}
                </router-link>
            </nav>
        </header>

        <main class="flex-1 min-w-0 pt-28 md:pt-0">
            <div class="max-w-6xl mx-auto w-full px-4 md:px-8 py-6 md:py-8 animate-fade-in-up">
                <router-view />
            </div>
        </main>

        <!-- Toasts -->
        <div class="fixed bottom-5 right-5 space-y-2 z-50">
            <div
                v-for="t in ui.toasts" :key="t.id"
                class="animate-fade-in-up flex items-start gap-3 pl-4 pr-5 py-3 rounded-xl shadow-[var(--shadow-pop)] text-sm bg-white border min-w-[260px] max-w-sm"
                :class="t.type === 'success' ? 'border-emerald-200' : t.type === 'error' ? 'border-rose-200' : 'border-ink-200'"
            >
                <span class="mt-0.5">
                    <svg v-if="t.type === 'success'" viewBox="0 0 24 24" class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
                    <svg v-else-if="t.type === 'error'" viewBox="0 0 24 24" class="h-5 w-5 text-rose-600" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M15 9l-6 6M9 9l6 6"/></svg>
                    <svg v-else viewBox="0 0 24 24" class="h-5 w-5 text-ink-600" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
                </span>
                <div class="text-ink-800">{{ t.message }}</div>
            </div>
        </div>
    </div>

    <router-view v-else />
</template>
