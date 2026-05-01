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
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-slate-50">
        <form @submit.prevent="submit" class="w-full max-w-sm bg-white rounded-lg shadow-sm p-6 space-y-4 border border-slate-200">
            <h1 class="text-xl font-bold text-slate-900">CyberAgora</h1>
            <p class="text-sm text-slate-500">Sign in to manage approvals.</p>

            <div>
                <label class="text-sm text-slate-700">Email</label>
                <input v-model="email" type="email" required class="w-full mt-1 border rounded-md px-3 py-2 border-slate-300 focus:border-indigo-500" />
            </div>
            <div>
                <label class="text-sm text-slate-700">Password</label>
                <input v-model="password" type="password" required class="w-full mt-1 border rounded-md px-3 py-2 border-slate-300 focus:border-indigo-500" />
            </div>

            <button :disabled="submitting" class="w-full bg-indigo-600 text-white rounded-md py-2 hover:bg-indigo-700 disabled:opacity-60">
                {{ submitting ? 'Signing in…' : 'Sign in' }}
            </button>
            <p class="text-xs text-slate-500 text-center">
                No account? <router-link :to="{ name: 'register' }" class="text-indigo-600 hover:underline">Register</router-link>
            </p>
        </form>
    </div>
</template>
