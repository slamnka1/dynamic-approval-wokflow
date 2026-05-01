import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from './stores/auth';

import Login from './views/Login.vue';
import Register from './views/Register.vue';
import Dashboard from './views/Dashboard.vue';

import FormsIndex from './views/admin/FormsIndex.vue';
import FormEditor from './views/admin/FormEditor.vue';

import AvailableForms from './views/user/AvailableForms.vue';
import SubmitRequest from './views/user/SubmitRequest.vue';
import MyRequests from './views/user/MyRequests.vue';

import PendingApprovals from './views/approver/PendingApprovals.vue';
import PastApprovals from './views/approver/PastApprovals.vue';
import RequestDetail from './views/RequestDetail.vue';

const routes = [
    { path: '/login', name: 'login', component: Login, meta: { guest: true } },
    { path: '/register', name: 'register', component: Register, meta: { guest: true } },

    { path: '/', name: 'dashboard', component: Dashboard, meta: { auth: true } },

    { path: '/admin/forms', name: 'admin.forms', component: FormsIndex, meta: { auth: true, roles: ['admin'] } },
    { path: '/admin/forms/new', name: 'admin.forms.new', component: FormEditor, meta: { auth: true, roles: ['admin'] } },
    { path: '/admin/forms/:id/edit', name: 'admin.forms.edit', component: FormEditor, props: true, meta: { auth: true, roles: ['admin'] } },

    { path: '/forms', name: 'forms', component: AvailableForms, meta: { auth: true } },
    { path: '/forms/:id/submit', name: 'forms.submit', component: SubmitRequest, props: true, meta: { auth: true } },
    { path: '/my/requests', name: 'my.requests', component: MyRequests, meta: { auth: true } },
    { path: '/my/requests/:id', name: 'my.requests.show', component: RequestDetail, props: (r) => ({ id: r.params.id, scope: 'mine' }), meta: { auth: true } },

    { path: '/approvals/pending', name: 'approvals.pending', component: PendingApprovals, meta: { auth: true, roles: ['approver'] } },
    { path: '/approvals/past', name: 'approvals.past', component: PastApprovals, meta: { auth: true, roles: ['approver'] } },
    { path: '/approvals/:id', name: 'approvals.show', component: RequestDetail, props: (r) => ({ id: r.params.id, scope: 'approver' }), meta: { auth: true, roles: ['approver'] } },

    { path: '/:pathMatch(.*)*', redirect: '/' },
];

const router = createRouter({ history: createWebHistory(), routes });

router.beforeEach((to) => {
    const auth = useAuthStore();

    if (to.meta.auth && !auth.isAuthenticated) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }

    if (to.meta.guest && auth.isAuthenticated) {
        return { name: 'dashboard' };
    }

    if (to.meta.roles && !to.meta.roles.includes(auth.role)) {
        return { name: 'dashboard' };
    }
});

export default router;
