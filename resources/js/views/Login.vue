<script setup>
import { ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useUiStore } from '../stores/ui';

const auth = useAuthStore();
const ui = useUiStore();
const router = useRouter();
const route = useRoute();

const email = ref('admin@cyberagora.test');
const password = ref('password');
const submitting = ref(false);
const showPassword = ref(false);

const submit = async () => {
    submitting.value = true;
    try {
        await auth.login(email.value, password.value);
        ui.success('Welcome back.');
        router.push(route.query.redirect || { name: 'dashboard' });
    } catch (e) {
        ui.error(e.response?.data?.message || 'Login failed.');
    } finally {
        submitting.value = false;
    }
};

const demos = [
    { label: 'Admin',     email: 'admin@cyberagora.test'    },
    { label: 'Approver',  email: 'approver@cyberagora.test' },
    { label: 'Requester', email: 'requester@cyberagora.test'},
];
</script>

<template>
    <div class="min-h-screen grid lg:grid-cols-2">
        <!-- Brand panel -->
        <div class="hidden lg:flex relative overflow-hidden bg-gradient-to-br from-brand-700 via-brand-600 to-violet-600 text-white p-12 flex-col">
            <div class="absolute -top-24 -right-24 h-80 w-80 rounded-full bg-white/10 blur-3xl"></div>
            <div class="absolute bottom-0 -left-24 h-80 w-80 rounded-full bg-fuchsia-500/20 blur-3xl"></div>

            <div class="relative flex items-center gap-3">
                <div class="h-11 w-11 rounded-2xl bg-white/15 backdrop-blur flex items-center justify-center ring-1 ring-white/20">
                    <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
                </div>
                <div>
                    <div class="font-bold text-lg leading-tight">CyberAgora</div>
                    <div class="text-xs text-white/70 uppercase tracking-wider">Approval Workflows</div>
                </div>
            </div>

            <div class="relative mt-auto space-y-6">
                <h2 class="text-3xl font-semibold leading-tight">Move requests forward.<br/>Without the email chaos.</h2>
                <p class="text-white/80 max-w-md">Build dynamic forms, route them through sequential or threshold approvals, and track every action in a single trail.</p>
                <ul class="space-y-2 text-white/90 text-sm">
                    <li class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-emerald-300"/> Sequential & threshold workflows</li>
                    <li class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-emerald-300"/> Per-field validation, dynamic forms</li>
                    <li class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-emerald-300"/> Full audit trail of every decision</li>
                </ul>
            </div>
        </div>

        <!-- Form panel -->
        <div class="flex items-center justify-center p-6 sm:p-12">
            <div class="w-full max-w-md">
                <div class="lg:hidden mb-8 flex items-center gap-2">
                    <div class="h-9 w-9 rounded-xl bg-gradient-to-br from-brand-500 to-violet-500 flex items-center justify-center">
                        <svg viewBox="0 0 24 24" class="h-5 w-5 text-white" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
                    </div>
                    <span class="font-bold text-lg">CyberAgora</span>
                </div>

                <h1 class="text-2xl font-bold text-ink-900">Sign in</h1>
                <p class="text-sm muted mt-1">Welcome back. Enter your credentials to continue.</p>

                <form @submit.prevent="submit" class="mt-8 space-y-4">
                    <div>
                        <label class="label">Email</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-3 flex items-center text-ink-400">
                                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>
                            </span>
                            <input v-model="email" type="email" required autocomplete="email" class="input pl-9" placeholder="you@company.com" />
                        </div>
                    </div>
                    <div>
                        <label class="label">Password</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-3 flex items-center text-ink-400">
                                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="9" rx="2"/><path d="M8 11V7a4 4 0 1 1 8 0v4"/></svg>
                            </span>
                            <input v-model="password" :type="showPassword ? 'text' : 'password'" required autocomplete="current-password" class="input pl-9 pr-10" placeholder="••••••••" />
                            <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-2 px-2 text-ink-400 hover:text-ink-700 text-xs">
                                {{ showPassword ? 'hide' : 'show' }}
                            </button>
                        </div>
                    </div>

                    <button :disabled="submitting" class="btn-primary w-full py-2.5">
                        <svg v-if="submitting" viewBox="0 0 24 24" class="h-4 w-4 animate-spin" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity="0.25" stroke-width="3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                        {{ submitting ? 'Signing in…' : 'Sign in' }}
                    </button>
                </form>

                <div class="mt-6 rounded-xl border border-dashed border-ink-200 p-4 bg-white/60">
                    <div class="text-xs uppercase tracking-wider text-ink-400 mb-2">Demo accounts (password: <code class="px-1 py-0.5 bg-ink-100 rounded">password</code>)</div>
                    <div class="flex flex-wrap gap-2">
                        <button v-for="d in demos" :key="d.email" type="button" @click="email = d.email; password = 'password'" class="chip hover:bg-brand-100 hover:text-brand-700">
                            {{ d.label }}
                        </button>
                    </div>
                </div>

                <p class="text-xs muted text-center mt-6">
                    No account? <router-link :to="{ name: 'register' }" class="text-brand-600 hover:underline font-medium">Create one</router-link>
                </p>
            </div>
        </div>
    </div>
</template>
