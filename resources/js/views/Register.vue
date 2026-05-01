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
    <div class="min-h-screen flex items-center justify-center bg-slate-50">
        <form @submit.prevent="submit" class="w-full max-w-sm bg-white rounded-lg shadow-sm p-6 space-y-4 border border-slate-200">
            <h1 class="text-xl font-bold">Create account</h1>

            <div v-for="(field, key) in { name: 'Name', email: 'Email', password: 'Password', password_confirmation: 'Confirm password' }" :key="key">
                <label class="text-sm text-slate-700">{{ field }}</label>
                <input
                    v-model="form[key]"
                    :type="key.includes('password') ? 'password' : (key === 'email' ? 'email' : 'text')"
                    required
                    class="w-full mt-1 border rounded-md px-3 py-2 border-slate-300 focus:border-indigo-500"
                />
                <p v-if="errors[key]" class="text-xs text-rose-600">{{ errors[key][0] }}</p>
            </div>

            <div>
                <label class="text-sm text-slate-700">Role</label>
                <select v-model="form.role" class="w-full mt-1 border rounded-md px-3 py-2 border-slate-300 bg-white">
                    <option value="requester">Requester</option>
                    <option value="approver">Approver</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <button :disabled="submitting" class="w-full bg-indigo-600 text-white rounded-md py-2 hover:bg-indigo-700">
                {{ submitting ? 'Creating…' : 'Create account' }}
            </button>
            <p class="text-xs text-center text-slate-500">
                Already have one? <router-link :to="{ name: 'login' }" class="text-indigo-600">Sign in</router-link>
            </p>
        </form>
    </div>
</template>
