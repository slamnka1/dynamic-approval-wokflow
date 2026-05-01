<script setup>
import { useAuthStore } from '../stores/auth';
const auth = useAuthStore();
</script>

<template>
    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            <h1 class="text-2xl font-bold">Hi {{ auth.user.name }}.</h1>
            <p class="text-slate-500 mt-1">Signed in as <strong>{{ auth.role }}</strong>.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <router-link v-if="auth.isAdmin" :to="{ name: 'admin.forms' }" class="bg-white border border-slate-200 rounded-lg p-5 hover:shadow-md transition">
                <div class="text-sm text-indigo-600 font-medium">Admin</div>
                <div class="text-lg font-semibold mt-1">Manage forms</div>
                <p class="text-sm text-slate-500 mt-2">Build forms and configure approval workflows.</p>
            </router-link>

            <router-link :to="{ name: 'forms' }" class="bg-white border border-slate-200 rounded-lg p-5 hover:shadow-md transition">
                <div class="text-sm text-indigo-600 font-medium">Submit</div>
                <div class="text-lg font-semibold mt-1">Available forms</div>
                <p class="text-sm text-slate-500 mt-2">Browse what's open and file a request.</p>
            </router-link>

            <router-link :to="{ name: 'my.requests' }" class="bg-white border border-slate-200 rounded-lg p-5 hover:shadow-md transition">
                <div class="text-sm text-indigo-600 font-medium">Track</div>
                <div class="text-lg font-semibold mt-1">My requests</div>
                <p class="text-sm text-slate-500 mt-2">Status and full action trail of your submissions.</p>
            </router-link>

            <router-link v-if="auth.isApprover" :to="{ name: 'approvals.pending' }" class="bg-white border border-slate-200 rounded-lg p-5 hover:shadow-md transition">
                <div class="text-sm text-emerald-700 font-medium">Approve</div>
                <div class="text-lg font-semibold mt-1">Pending approvals</div>
                <p class="text-sm text-slate-500 mt-2">Items waiting on your decision.</p>
            </router-link>
        </div>
    </div>
</template>
