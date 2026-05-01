import { defineStore } from 'pinia';
import client from '../api/client';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        token: localStorage.getItem('auth_token') || null,
        user: JSON.parse(localStorage.getItem('auth_user') || 'null'),
    }),

    getters: {
        isAuthenticated: (s) => !!s.token,
        role: (s) => s.user?.role,
        isAdmin: (s) => s.user?.role === 'admin',
        isApprover: (s) => s.user?.role === 'approver',
        isRequester: (s) => s.user?.role === 'requester',
    },

    actions: {
        async login(email, password) {
            const { data } = await client.post('/auth/login', { email, password });
            this.persist(data.user, data.token);
        },

        async register(payload) {
            const { data } = await client.post('/auth/register', payload);
            this.persist(data.user, data.token);
        },

        async logout() {
            try { await client.post('/auth/logout'); } catch {}
            this.clear();
        },

        async refresh() {
            if (!this.token) return;
            try {
                const { data } = await client.get('/auth/me');
                this.persist(data.user, this.token);
            } catch {
                this.clear();
            }
        },

        persist(user, token) {
            this.user = user;
            this.token = token;
            localStorage.setItem('auth_token', token);
            localStorage.setItem('auth_user', JSON.stringify(user));
        },

        clear() {
            this.user = null;
            this.token = null;
            localStorage.removeItem('auth_token');
            localStorage.removeItem('auth_user');
        },
    },
});
