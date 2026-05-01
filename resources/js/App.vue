<script setup>
import { useAuthStore } from './stores/auth';
import { useUiStore } from './stores/ui';
import { useRouter } from 'vue-router';

const auth = useAuthStore();
const ui = useUiStore();
const router = useRouter();

const logout = async () => {
    await auth.logout();
    router.push({ name: 'login' });
};
</script>

<template>
    <div class="min-h-screen flex flex-col">
        <header v-if="auth.isAuthenticated" class="bg-white border-b border-slate-200">
            <nav class="max-w-6xl mx-auto px-4 py-3 flex items-center gap-6 text-sm">
                <router-link :to="{ name: 'dashboard' }" class="font-bold text-indigo-700">CyberAgora</router-link>

                <router-link :to="{ name: 'forms' }" class="hover:text-indigo-700">Forms</router-link>
                <router-link :to="{ name: 'my.requests' }" class="hover:text-indigo-700">My Requests</router-link>

                <template v-if="auth.isApprover">
                    <router-link :to="{ name: 'approvals.pending' }" class="hover:text-indigo-700">Pending Approvals</router-link>
                    <router-link :to="{ name: 'approvals.past' }" class="hover:text-indigo-700">Past</router-link>
                </template>

                <template v-if="auth.isAdmin">
                    <router-link :to="{ name: 'admin.forms' }" class="hover:text-indigo-700">Manage Forms</router-link>
                </template>

                <span class="ml-auto text-slate-500">{{ auth.user.name }} <span class="text-xs uppercase tracking-wide bg-slate-100 px-1.5 py-0.5 rounded ml-1">{{ auth.role }}</span></span>
                <button @click="logout" class="text-rose-600 hover:underline">Logout</button>
            </nav>
        </header>

        <main class="flex-1 max-w-6xl mx-auto w-full px-4 py-6">
            <router-view />
        </main>

        <div class="fixed bottom-4 right-4 space-y-2 z-50">
            <div
                v-for="t in ui.toasts" :key="t.id"
                :class="[
                    'px-4 py-2 rounded-md shadow-md text-sm text-white min-w-[220px]',
                    t.type === 'success' ? 'bg-emerald-600' : t.type === 'error' ? 'bg-rose-600' : 'bg-slate-800',
                ]"
            >
                {{ t.message }}
            </div>
        </div>
    </div>
</template>
