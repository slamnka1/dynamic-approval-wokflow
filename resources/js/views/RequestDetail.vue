<script setup>
import { ref, onMounted, computed } from 'vue';
import client from '../api/client';
import StatusBadge from '../components/StatusBadge.vue';
import { useAuthStore } from '../stores/auth';
import { useUiStore } from '../stores/ui';

const props = defineProps({
    id: { type: [String, Number], required: true },
    scope: { type: String, default: 'mine' },
});

const auth = useAuthStore();
const ui = useUiStore();
const request = ref(null);
const loadError = ref(null);
const acting = ref(false);
const comment = ref('');

const endpoint = computed(() => props.scope === 'approver' ? `/approvals/${props.id}` : `/my/requests/${props.id}`);

onMounted(async () => {
    try {
        const { data } = await client.get(endpoint.value);
        request.value = data.data;
    } catch (e) {
        loadError.value = e.response?.data?.message || `Could not load request (HTTP ${e.response?.status ?? 'error'}).`;
        ui.error(loadError.value);
    }
});

const act = async (kind) => {
    acting.value = true;
    try {
        const { data } = await client.post(`/approvals/${props.id}/${kind}`, { comment: comment.value || null });
        request.value = data.data;
        ui.success(`Request ${kind === 'approve' ? 'approved' : 'rejected'}.`);
        comment.value = '';
    } catch (e) {
        ui.error(e.response?.data?.message || 'Action failed.');
    } finally {
        acting.value = false;
    }
};

const alreadyActed = computed(() =>
    request.value?.actions?.some((a) => a.approver?.id === auth.user.id) ?? false,
);

const isInWorkflow = computed(() =>
    request.value?.workflow?.steps?.some((s) => s.approver_id === auth.user.id) ?? false,
);

const canAct = computed(() => {
    if (!request.value || request.value.status !== 'pending' || !auth.isApprover) return false;

    const wf = request.value.workflow;
    if (!wf) return false;

    if (wf.type === 'sequential') {
        const current = wf.steps?.find((s) => s.step_order === request.value.current_step_order);
        return current?.approver_id === auth.user.id;
    }

    return isInWorkflow.value && !alreadyActed.value;
});

const initials = (name) => (name || '').split(' ').map((p) => p[0]).filter(Boolean).slice(0, 2).join('').toUpperCase() || '?';

const fileUrl = (path) => `/storage/${String(path).replace(/^\/+/, '')}`;
const fileName = (path) => String(path).split('/').pop();
const isImageFile = (path) => /\.(jpe?g|png|gif|webp|svg|bmp)$/i.test(String(path));

const formatValue = (v) => {
    if (v.value === null || v.value === undefined || v.value === '') return '—';
    if (v.field_type === 'checkbox') return v.value ? 'Yes' : 'No';
    if (v.field_type === 'number') {
        const n = Number(v.value);
        return Number.isFinite(n) ? (n % 1 === 0 ? n.toString() : n.toString()) : v.value;
    }
    return v.value;
};

const progress = computed(() => {
    const wf = request.value?.workflow;
    const steps = wf?.steps ?? [];
    if (!wf || steps.length === 0) return [];

    const actions = request.value?.actions ?? [];

    if (wf.type === 'sequential') {
        return steps.map((s, i) => {
            const action = actions.find((a) => a.step_order === i + 1);
            return {
                approver: s.approver,
                stepNumber: i + 1,
                status: action
                    ? (action.action === 'approve' ? 'approved' : 'rejected')
                    : (i === actions.length && request.value.status === 'pending' ? 'current' : 'upcoming'),
                actedAt: action?.acted_at,
                comment: action?.comment,
            };
        });
    }

    const approveActions = actions.filter((a) => a.action === 'approve');
    return steps.map((s) => {
        const action = approveActions.find((a) => a.approver?.id === s.approver?.id);
        return {
            approver: s.approver,
            status: action ? 'approved' : 'pending',
            actedAt: action?.acted_at,
            comment: action?.comment,
        };
    });
});
</script>

<template>
    <div v-if="loadError" class="card p-8 text-center">
        <div class="mx-auto h-12 w-12 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center">
            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
        </div>
        <p class="mt-3 font-medium text-ink-900">Couldn't load this request</p>
        <p class="text-sm muted mt-1">{{ loadError }}</p>
    </div>

    <div v-else-if="!request" class="card p-8 text-center muted">Loading…</div>

    <div v-else class="space-y-6">
        <!-- Header -->
        <div class="card overflow-hidden">
            <div class="h-1.5 w-full bg-gradient-to-r"
                :class="request.status === 'pending' ? 'from-amber-400 to-amber-600' :
                        request.status === 'approved' ? 'from-emerald-400 to-emerald-600' :
                        request.status === 'rejected' ? 'from-rose-400 to-rose-600' : 'from-ink-300 to-ink-500'"
            ></div>
            <div class="p-6 flex items-start justify-between gap-4 flex-wrap">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 text-xs muted">
                        <router-link :to="{ name: scope === 'approver' ? 'approvals.pending' : 'my.requests' }" class="hover:text-ink-700">
                            ← Back
                        </router-link>
                        <span>·</span>
                        <span>Request #{{ request.id }}</span>
                    </div>
                    <h1 class="text-2xl font-bold text-ink-900 mt-1">{{ request.form.name }}</h1>
                    <p class="text-sm muted mt-1">
                        Submitted by <span class="font-medium text-ink-800">{{ request.requester?.name }}</span>
                        · {{ new Date(request.created_at).toLocaleString() }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="chip" :class="request.workflow?.type === 'sequential' ? 'bg-sky-100 text-sky-700' : 'bg-violet-100 text-violet-700'">
                        {{ request.workflow?.name }} · {{ request.workflow?.type }}
                    </span>
                    <StatusBadge :status="request.status" />
                </div>
            </div>
        </div>

        <div class="grid lg:grid-cols-3 gap-6">
            <!-- Submitted values -->
            <div class="card p-6 lg:col-span-2">
                <h2 class="section-title mb-4">Submitted values</h2>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div v-for="v in request.values" :key="v.form_field_id" class="rounded-lg border border-ink-200 bg-ink-50/50 p-3">
                        <dt class="text-xs uppercase tracking-wider text-ink-500">{{ v.field_label }}</dt>

                        <dd v-if="v.field_type === 'file' && v.value" class="mt-2">
                            <a v-if="isImageFile(v.value)" :href="fileUrl(v.value)" target="_blank" rel="noopener" class="block group">
                                <img :src="fileUrl(v.value)" :alt="fileName(v.value)" class="h-32 w-full object-cover rounded-md border border-ink-200 group-hover:opacity-90 transition" />
                                <div class="flex items-center gap-2 mt-2 text-xs muted">
                                    <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"/><path d="M10 14L21 3"/><path d="M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5"/></svg>
                                    <span class="truncate flex-1">{{ fileName(v.value) }}</span>
                                </div>
                            </a>

                            <div v-else class="flex items-center gap-3 rounded-lg bg-white border border-ink-200 p-3">
                                <span class="h-10 w-10 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center shrink-0">
                                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                                </span>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-ink-900 truncate" :title="fileName(v.value)">{{ fileName(v.value) }}</div>
                                    <div class="text-xs muted">Uploaded file</div>
                                </div>
                                <a :href="fileUrl(v.value)" target="_blank" rel="noopener" class="btn-secondary px-3 py-1.5 text-xs" :title="`Open ${fileName(v.value)}`">
                                    <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"/><path d="M10 14L21 3"/><path d="M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5"/></svg>
                                    View
                                </a>
                                <a :href="fileUrl(v.value)" :download="fileName(v.value)" class="btn-secondary px-3 py-1.5 text-xs" :title="`Download ${fileName(v.value)}`">
                                    <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
                                </a>
                            </div>
                        </dd>

                        <dd v-else class="font-medium text-ink-900 mt-1 break-words">{{ formatValue(v) }}</dd>
                    </div>
                    <div v-if="request.values.length === 0" class="muted col-span-full text-sm">No values submitted.</div>
                </dl>
            </div>

            <!-- Workflow progress -->
            <aside class="lg:col-span-1 space-y-6">
                <div class="card p-6">
                    <h2 class="section-title mb-4">Workflow progress</h2>
                    <ol class="relative space-y-4 pl-5 before:content-[''] before:absolute before:left-2 before:top-2 before:bottom-2 before:w-px before:bg-ink-200">
                        <li v-for="(s, i) in progress" :key="i" class="relative">
                            <span class="absolute -left-3.5 top-1 h-3 w-3 rounded-full ring-2 ring-white"
                                :class="s.status === 'approved' ? 'bg-emerald-500' :
                                        s.status === 'rejected' ? 'bg-rose-500' :
                                        s.status === 'current' ? 'bg-amber-500 animate-pulse' :
                                        s.status === 'pending' ? 'bg-ink-300' : 'bg-ink-200'"
                            ></span>
                            <div class="flex items-center gap-2">
                                <div class="h-7 w-7 rounded-full bg-gradient-to-br from-brand-500 to-violet-500 text-white text-[10px] font-semibold flex items-center justify-center">
                                    {{ initials(s.approver?.name) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-ink-900 truncate">{{ s.approver?.name }}</div>
                                    <div class="text-xs muted">
                                        <span v-if="s.stepNumber">Step {{ s.stepNumber }} · </span>
                                        <span class="capitalize">{{ s.status }}</span>
                                    </div>
                                </div>
                            </div>
                            <div v-if="s.actedAt" class="text-xs muted mt-1 ml-9">{{ new Date(s.actedAt).toLocaleString() }}</div>
                            <div v-if="s.comment" class="text-xs text-ink-700 mt-1 ml-9 italic">"{{ s.comment }}"</div>
                        </li>
                    </ol>
                </div>
            </aside>
        </div>

        <!-- Action trail -->
        <div class="card p-6">
            <h2 class="section-title mb-4">Action trail</h2>
            <ol class="space-y-3">
                <li v-for="a in request.actions" :key="a.id" class="flex items-start gap-3 p-3 rounded-lg border border-ink-100 bg-ink-50/40">
                    <div class="h-9 w-9 rounded-full bg-gradient-to-br from-brand-500 to-violet-500 text-white text-xs font-semibold flex items-center justify-center shrink-0">
                        {{ initials(a.approver?.name) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-medium text-ink-900">{{ a.approver?.name }}</span>
                            <StatusBadge :status="a.action === 'approve' ? 'approved' : 'rejected'" />
                            <span v-if="a.step_order" class="chip">step {{ a.step_order }}</span>
                            <span class="text-xs muted ml-auto">{{ new Date(a.acted_at).toLocaleString() }}</span>
                        </div>
                        <div v-if="a.comment" class="text-sm text-ink-700 mt-1.5">"{{ a.comment }}"</div>
                    </div>
                </li>
                <li v-if="request.actions.length === 0" class="text-sm muted text-center py-4">No actions yet.</li>
            </ol>
        </div>

        <!-- Already acted banner -->
        <div v-if="!canAct && alreadyActed && auth.isApprover" class="card p-5 bg-emerald-50/40 border-emerald-200 flex items-center gap-3">
            <span class="h-9 w-9 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
            </span>
            <div>
                <p class="font-medium text-ink-900">You've already acted on this request</p>
                <p class="text-sm muted">Your decision is recorded in the action trail above.</p>
            </div>
        </div>

        <!-- Awaiting earlier step (sequential, not your turn yet) -->
        <div v-else-if="!canAct && isInWorkflow && request.status === 'pending' && auth.isApprover" class="card p-5 bg-ink-50/40 border-ink-200 flex items-center gap-3">
            <span class="h-9 w-9 rounded-lg bg-ink-100 text-ink-600 flex items-center justify-center shrink-0">
                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
            </span>
            <div>
                <p class="font-medium text-ink-900">Waiting on an earlier approver</p>
                <p class="text-sm muted">You'll be able to act once the request reaches your step.</p>
            </div>
        </div>

        <!-- Decision panel -->
        <div v-if="canAct" class="card p-6 border-amber-200 ring-1 ring-amber-200/40">
            <div class="flex items-center gap-2 mb-3">
                <span class="h-8 w-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01"/><circle cx="12" cy="12" r="9"/></svg>
                </span>
                <h2 class="section-title">Your decision</h2>
            </div>
            <textarea v-model="comment" placeholder="Optional comment (visible to requester and other approvers)" rows="3" class="input"></textarea>
            <div class="flex flex-wrap gap-2 mt-4">
                <button :disabled="acting" @click="act('approve')" class="btn-success">
                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
                    Approve
                </button>
                <button :disabled="acting" @click="act('reject')" class="btn-danger">
                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M15 9l-6 6M9 9l6 6"/></svg>
                    Reject
                </button>
            </div>
        </div>
    </div>
</template>
