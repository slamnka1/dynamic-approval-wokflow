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
const acting = ref(false);
const comment = ref('');

const path = computed(() => props.scope === 'mine' ? `/my/requests/${props.id}` : `/my/requests/${props.id}`);

const endpoint = computed(() => props.scope === 'approver' ? `/approvals/${props.id}` : `/my/requests/${props.id}`);

onMounted(async () => {
    try {
        const { data } = await client.get(endpoint.value);
        request.value = data.data;
    } catch {
        ui.error('Could not load request.');
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

const canAct = computed(() => {
    if (!request.value || request.value.status !== 'pending' || !auth.isApprover) return false;
    if (request.value.workflow?.type === 'sequential') {
        return true;
    }
    return !request.value.actions?.some((a) => a.approver?.id === auth.user.id);
});
</script>

<template>
    <div v-if="!request" class="text-slate-500">Loading…</div>
    <div v-else class="space-y-6">
        <div class="bg-white border border-slate-200 rounded-lg p-6">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-xl font-bold">{{ request.form.name }}</h1>
                    <p class="text-sm text-slate-500">By {{ request.requester?.name }} · {{ request.workflow?.name }} ({{ request.workflow?.type }})</p>
                </div>
                <StatusBadge :status="request.status" />
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-lg p-6">
            <h2 class="font-semibold mb-3">Submitted values</h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                <div v-for="v in request.values" :key="v.form_field_id">
                    <dt class="text-slate-500">{{ v.field_label }}</dt>
                    <dd class="font-medium text-slate-800">{{ v.value ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        <div class="bg-white border border-slate-200 rounded-lg p-6">
            <h2 class="font-semibold mb-3">Action trail</h2>
            <ol class="space-y-2 text-sm">
                <li v-for="a in request.actions" :key="a.id" class="flex items-start gap-3">
                    <StatusBadge :status="a.action === 'approve' ? 'approved' : 'rejected'" />
                    <div>
                        <div class="font-medium">{{ a.approver?.name }} <span v-if="a.step_order" class="text-xs text-slate-400">step {{ a.step_order }}</span></div>
                        <div class="text-slate-500 text-xs">{{ new Date(a.acted_at).toLocaleString() }}</div>
                        <div v-if="a.comment" class="text-slate-700 mt-1">{{ a.comment }}</div>
                    </div>
                </li>
                <li v-if="request.actions.length === 0" class="text-slate-500">No actions yet.</li>
            </ol>
        </div>

        <div v-if="canAct" class="bg-white border border-slate-200 rounded-lg p-6">
            <h2 class="font-semibold mb-3">Your decision</h2>
            <textarea v-model="comment" placeholder="Optional comment" rows="2" class="w-full border-slate-300 rounded-md p-2 border text-sm"></textarea>
            <div class="flex gap-2 mt-3">
                <button :disabled="acting" @click="act('approve')" class="bg-emerald-600 text-white px-4 py-2 rounded-md hover:bg-emerald-700 disabled:opacity-60">Approve</button>
                <button :disabled="acting" @click="act('reject')" class="bg-rose-600 text-white px-4 py-2 rounded-md hover:bg-rose-700 disabled:opacity-60">Reject</button>
            </div>
        </div>
    </div>
</template>
