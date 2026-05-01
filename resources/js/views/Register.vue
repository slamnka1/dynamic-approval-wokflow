<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useUiStore } from '../stores/ui';

const auth = useAuthStore();
const ui = useUiStore();
const router = useRouter();

const form = ref({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'requester',
});
const errors = ref({});
const submitting = ref(false);

const roles = [
    { value: 'requester', label: 'Requester', desc: 'Submit requests against forms.' },
    { value: 'approver',  label: 'Approver',  desc: 'Review and decide on requests.' },
    { value: 'admin',     label: 'Admin',     desc: 'Build forms and configure flows.' },
];

const submit = async () => {
    submitting.value = true;
    errors.value = {};
    try {
        await auth.register(form.value);
        ui.success('Welcome aboard.');
        router.push({ name: 'dashboard' });
    } catch (e) {
        errors.value = e.response?.data?.errors || {};
        ui.error(e.response?.data?.message || 'Registration failed.');
    } finally {
        submitting.value = false;
    }
};
</script>

<template>
    <div class="min-h-screen flex items-center justify-center p-4 sm:p-8">
        <div class="w-full max-w-xl">
            <div class="flex items-center justify-center gap-2 mb-6">
                <div class="h-10 w-10 rounded-xl bg-gradient-to-br from-brand-500 to-violet-500 flex items-center justify-center shadow-sm">
                    <svg viewBox="0 0 24 24" class="h-5 w-5 text-white" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
                </div>
                <span class="font-bold text-lg text-ink-900">CyberAgora</span>
            </div>

            <form @submit.prevent="submit" class="card p-6 sm:p-8 space-y-5 animate-fade-in-up">
                <div>
                    <h1 class="text-xl font-bold text-ink-900">Create your account</h1>
                    <p class="text-sm muted mt-1">Pick a role and you're in.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label">Full name</label>
                        <input v-model="form.name" required class="input" placeholder="Jane Doe" />
                        <p v-if="errors.name" class="text-xs text-rose-600 mt-1">{{ errors.name[0] }}</p>
                    </div>
                    <div>
                        <label class="label">Email</label>
                        <input v-model="form.email" type="email" required class="input" placeholder="jane@company.com" />
                        <p v-if="errors.email" class="text-xs text-rose-600 mt-1">{{ errors.email[0] }}</p>
                    </div>
                    <div>
                        <label class="label">Password</label>
                        <input v-model="form.password" type="password" required class="input" placeholder="At least 8 characters" />
                        <p v-if="errors.password" class="text-xs text-rose-600 mt-1">{{ errors.password[0] }}</p>
                    </div>
                    <div>
                        <label class="label">Confirm password</label>
                        <input v-model="form.password_confirmation" type="password" required class="input" placeholder="Repeat password" />
                    </div>
                </div>

                <div>
                    <label class="label">Role</label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        <label
                            v-for="r in roles" :key="r.value"
                            class="cursor-pointer rounded-xl border p-3 transition"
                            :class="form.role === r.value ? 'border-brand-500 bg-brand-50/60 ring-1 ring-brand-500/30' : 'border-ink-200 hover:border-ink-300'"
                        >
                            <input type="radio" v-model="form.role" :value="r.value" class="sr-only" />
                            <div class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full" :class="form.role === r.value ? 'bg-brand-500' : 'bg-ink-300'"></span>
                                <span class="font-medium text-sm text-ink-900">{{ r.label }}</span>
                            </div>
                            <p class="text-xs muted mt-1">{{ r.desc }}</p>
                        </label>
                    </div>
                </div>

                <button :disabled="submitting" class="btn-primary w-full py-2.5">
                    <svg v-if="submitting" viewBox="0 0 24 24" class="h-4 w-4 animate-spin" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity="0.25" stroke-width="3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                    {{ submitting ? 'Creating…' : 'Create account' }}
                </button>

                <p class="text-xs muted text-center">
                    Already have an account? <router-link :to="{ name: 'login' }" class="text-brand-600 hover:underline font-medium">Sign in</router-link>
                </p>
            </form>
        </div>
    </div>
</template>
