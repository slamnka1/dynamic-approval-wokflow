<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import client from '../../api/client';
import { useUiStore } from '../../stores/ui';

const props = defineProps({ id: { type: [String, Number], default: null } });
const router = useRouter();
const ui = useUiStore();

const isEdit = computed(() => !!props.id);
const saving = ref(false);

const form = ref({
    name: '',
    description: '',
    is_active: true,
    fields: [],
});

const workflow = ref({
    name: '',
    type: 'sequential',
    required_approvals: 1,
    steps: [],
});

const approvers = ref([]);
const errors = ref({});

const fieldTypes = [
    { value: 'text',     label: 'Text',     icon: 'text' },
    { value: 'textarea', label: 'Textarea', icon: 'paragraph' },
    { value: 'number',   label: 'Number',   icon: 'hash' },
    { value: 'select',   label: 'Select',   icon: 'list' },
    { value: 'date',     label: 'Date',     icon: 'calendar' },
    { value: 'checkbox', label: 'Checkbox', icon: 'check' },
    { value: 'file',     label: 'File',     icon: 'paperclip' },
];

const blankField = () => ({ key: '', label: '', type: 'text', is_required: false, options: [] });
const blankStep = () => ({ approver_id: approvers.value[0]?.id ?? null });

onMounted(async () => {
    const { data: u } = await client.get('/admin/users?role=approver');
    approvers.value = u.data;

    if (isEdit.value) {
        const { data } = await client.get(`/forms/${props.id}`);
        form.value = {
            name: data.data.name,
            description: data.data.description ?? '',
            is_active: data.data.is_active,
            fields: data.data.fields.map((f) => ({
                key: f.key, label: f.label, type: f.type, is_required: f.is_required,
                min_value: f.min_value, max_value: f.max_value, placeholder: f.placeholder,
                options: f.options.map((o) => ({ value: o.value, label: o.label })),
            })),
        };
        if (data.data.workflow) {
            workflow.value = {
                name: data.data.workflow.name,
                type: data.data.workflow.type,
                required_approvals: data.data.workflow.required_approvals,
                steps: data.data.workflow.steps.map((s) => ({ approver_id: s.approver_id })),
            };
        }
    } else {
        form.value.fields.push(blankField());
        workflow.value.steps.push(blankStep());
    }
});

const moveField = (idx, dir) => {
    const target = idx + dir;
    if (target < 0 || target >= form.value.fields.length) return;
    const [item] = form.value.fields.splice(idx, 1);
    form.value.fields.splice(target, 0, item);
};

const moveStep = (idx, dir) => {
    const target = idx + dir;
    if (target < 0 || target >= workflow.value.steps.length) return;
    const [item] = workflow.value.steps.splice(idx, 1);
    workflow.value.steps.splice(target, 0, item);
};

const save = async () => {
    saving.value = true;
    errors.value = {};
    try {
        const url = isEdit.value ? `/admin/forms/${props.id}` : '/admin/forms';
        const method = isEdit.value ? 'put' : 'post';
        const { data } = await client[method](url, form.value);
        const formId = data.data.id;

        if (workflow.value.steps.length) {
            await client.post(`/admin/forms/${formId}/workflow`, workflow.value);
        }
        ui.success('Saved.');
        router.push({ name: 'admin.forms' });
    } catch (e) {
        errors.value = e.response?.data?.errors || {};
        ui.error(e.response?.data?.message || 'Save failed.');
    } finally {
        saving.value = false;
    }
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div class="flex items-center gap-3">
                <router-link :to="{ name: 'admin.forms' }" class="btn-ghost p-2">
                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
                </router-link>
                <div>
                    <h1 class="text-2xl font-bold text-ink-900">{{ isEdit ? 'Edit form' : 'New form' }}</h1>
                    <p class="text-sm muted">Define fields, then attach an approval workflow.</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <router-link :to="{ name: 'admin.forms' }" class="btn-secondary">Cancel</router-link>
                <button :disabled="saving" @click="save" class="btn-primary">
                    <svg v-if="saving" viewBox="0 0 24 24" class="h-4 w-4 animate-spin" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity="0.25" stroke-width="3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                    {{ saving ? 'Saving…' : 'Save form' }}
                </button>
            </div>
        </div>

        <!-- Form details -->
        <div class="card p-6">
            <div class="flex items-center gap-2 mb-4">
                <span class="h-7 w-7 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center text-xs font-bold">1</span>
                <h2 class="section-title">Form details</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label class="label">Name</label>
                    <input v-model="form.name" class="input" placeholder="e.g. Travel reimbursement" />
                    <p v-if="errors['name']" class="text-xs text-rose-600 mt-1">{{ errors['name'][0] }}</p>
                </div>
                <div>
                    <label class="label">Visibility</label>
                    <label class="flex items-center gap-3 px-3 py-2 rounded-lg border border-ink-200 cursor-pointer">
                        <input id="active" type="checkbox" v-model="form.is_active" class="h-4 w-4 rounded border-ink-300 text-brand-600 focus:ring-brand-500" />
                        <span class="text-sm">{{ form.is_active ? 'Active — accepting requests' : 'Inactive — hidden from requesters' }}</span>
                    </label>
                </div>
            </div>
            <div class="mt-4">
                <label class="label">Description</label>
                <textarea v-model="form.description" rows="2" class="input" placeholder="What is this form for?"></textarea>
            </div>
        </div>

        <!-- Fields -->
        <div class="card p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <span class="h-7 w-7 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center text-xs font-bold">2</span>
                    <h2 class="section-title">Fields <span class="muted font-normal">({{ form.fields.length }})</span></h2>
                </div>
                <button @click="form.fields.push(blankField())" class="btn-secondary text-xs px-3 py-1.5">
                    <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                    Add field
                </button>
            </div>

            <div v-if="form.fields.length === 0" class="text-center py-8 muted text-sm">No fields yet — add your first one above.</div>

            <div v-for="(f, idx) in form.fields" :key="idx" class="rounded-xl border border-ink-200 bg-ink-50/40 p-4 mb-3">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-mono text-ink-400">#{{ idx + 1 }}</span>
                        <span class="chip" :class="f.is_required ? 'bg-rose-50 text-rose-700' : 'bg-ink-100'">{{ f.is_required ? 'required' : 'optional' }}</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <button @click="moveField(idx, -1)" :disabled="idx === 0" class="btn-ghost p-1 disabled:opacity-30">
                            <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 15l-6-6-6 6"/></svg>
                        </button>
                        <button @click="moveField(idx, 1)" :disabled="idx === form.fields.length - 1" class="btn-ghost p-1 disabled:opacity-30">
                            <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
                        </button>
                        <button @click="form.fields.splice(idx, 1)" class="btn-ghost p-1 text-rose-600 hover:bg-rose-50">
                            <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                    <div class="md:col-span-3">
                        <label class="label">Key</label>
                        <input v-model="f.key" placeholder="amount" class="input" />
                    </div>
                    <div class="md:col-span-4">
                        <label class="label">Label</label>
                        <input v-model="f.label" placeholder="Amount in USD" class="input" />
                    </div>
                    <div class="md:col-span-3">
                        <label class="label">Type</label>
                        <select v-model="f.type" class="input">
                            <option v-for="t in fieldTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
                        </select>
                    </div>
                    <div class="md:col-span-2 flex items-end">
                        <label class="flex items-center gap-2 px-3 py-2 rounded-lg border border-ink-200 bg-white w-full cursor-pointer">
                            <input type="checkbox" v-model="f.is_required" class="h-4 w-4 rounded border-ink-300 text-brand-600 focus:ring-brand-500" />
                            <span class="text-xs">Required</span>
                        </label>
                    </div>

                    <div v-if="f.type === 'text' || f.type === 'textarea' || f.type === 'number'" class="md:col-span-12">
                        <label class="label">Placeholder</label>
                        <input v-model="f.placeholder" class="input" placeholder="Hint shown inside the input" />
                    </div>

                    <div v-if="f.type === 'number'" class="md:col-span-3">
                        <label class="label">Min</label>
                        <input v-model.number="f.min_value" type="number" class="input" />
                    </div>
                    <div v-if="f.type === 'number'" class="md:col-span-3">
                        <label class="label">Max</label>
                        <input v-model.number="f.max_value" type="number" class="input" />
                    </div>

                    <div v-if="f.type === 'select'" class="md:col-span-12">
                        <div class="flex justify-between items-center mb-2">
                            <label class="label !mb-0">Options</label>
                            <button @click="f.options.push({ value: '', label: '' })" class="text-xs text-brand-600 hover:underline">+ option</button>
                        </div>
                        <div v-if="f.options.length === 0" class="text-xs muted py-2">Add at least one option.</div>
                        <div v-for="(o, oi) in f.options" :key="oi" class="grid grid-cols-12 gap-2 mb-2">
                            <input v-model="o.value" placeholder="value" class="col-span-5 input" />
                            <input v-model="o.label" placeholder="label" class="col-span-6 input" />
                            <button @click="f.options.splice(oi,1)" class="col-span-1 btn-ghost text-rose-600 hover:bg-rose-50">×</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Workflow -->
        <div class="card p-6">
            <div class="flex items-center gap-2 mb-4">
                <span class="h-7 w-7 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center text-xs font-bold">3</span>
                <h2 class="section-title">Approval workflow</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="label">Workflow name</label>
                    <input v-model="workflow.name" class="input" placeholder="Standard approval" />
                </div>
                <div>
                    <label class="label">Type</label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="cursor-pointer rounded-lg border p-2.5 transition" :class="workflow.type === 'sequential' ? 'border-brand-500 bg-brand-50/60 ring-1 ring-brand-500/30' : 'border-ink-200'">
                            <input type="radio" v-model="workflow.type" value="sequential" class="sr-only" />
                            <div class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full" :class="workflow.type === 'sequential' ? 'bg-brand-500' : 'bg-ink-300'"></span>
                                <span class="text-xs font-medium">Sequential</span>
                            </div>
                        </label>
                        <label class="cursor-pointer rounded-lg border p-2.5 transition" :class="workflow.type === 'threshold' ? 'border-brand-500 bg-brand-50/60 ring-1 ring-brand-500/30' : 'border-ink-200'">
                            <input type="radio" v-model="workflow.type" value="threshold" class="sr-only" />
                            <div class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full" :class="workflow.type === 'threshold' ? 'bg-brand-500' : 'bg-ink-300'"></span>
                                <span class="text-xs font-medium">Threshold</span>
                            </div>
                        </label>
                    </div>
                </div>
                <div v-if="workflow.type === 'threshold'">
                    <label class="label">Required approvals</label>
                    <input v-model.number="workflow.required_approvals" type="number" min="1" class="input" />
                </div>
            </div>

            <div class="rounded-lg bg-brand-50/50 border border-brand-100 p-3 text-xs text-brand-900 mb-4">
                <span class="font-medium">{{ workflow.type === 'sequential' ? 'Sequential' : 'Threshold' }}:</span>
                {{ workflow.type === 'sequential'
                    ? 'Approvers act in the order shown. Each step must approve before the next.'
                    : 'Any approver from the pool can act. Request approves once it reaches the required count.' }}
            </div>

            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-ink-800">{{ workflow.type === 'sequential' ? 'Ordered approvers' : 'Approver pool' }}</span>
                <button @click="workflow.steps.push(blankStep())" class="btn-secondary text-xs px-3 py-1.5">
                    <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                    Add approver
                </button>
            </div>

            <div v-if="workflow.steps.length === 0" class="text-center py-6 muted text-sm">No approvers yet.</div>

            <div v-for="(s, si) in workflow.steps" :key="si" class="flex items-center gap-2 mb-2">
                <span v-if="workflow.type === 'sequential'" class="h-7 w-7 rounded-full bg-brand-50 text-brand-700 text-xs font-semibold flex items-center justify-center">{{ si + 1 }}</span>
                <span v-else class="h-7 w-7 rounded-full bg-ink-100 text-ink-500 flex items-center justify-center">
                    <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="7" r="3"/><path d="M3 21v-1a6 6 0 0 1 12 0v1"/><circle cx="17" cy="9" r="2.5"/></svg>
                </span>
                <select v-model.number="s.approver_id" class="flex-1 input">
                    <option v-for="a in approvers" :key="a.id" :value="a.id">{{ a.name }} — {{ a.email }}</option>
                </select>
                <button v-if="workflow.type === 'sequential'" @click="moveStep(si, -1)" :disabled="si === 0" class="btn-ghost p-2 disabled:opacity-30">
                    <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 15l-6-6-6 6"/></svg>
                </button>
                <button v-if="workflow.type === 'sequential'" @click="moveStep(si, 1)" :disabled="si === workflow.steps.length - 1" class="btn-ghost p-2 disabled:opacity-30">
                    <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
                </button>
                <button @click="workflow.steps.splice(si,1)" class="btn-ghost p-2 text-rose-600 hover:bg-rose-50">
                    <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                </button>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <router-link :to="{ name: 'admin.forms' }" class="btn-secondary">Cancel</router-link>
            <button :disabled="saving" @click="save" class="btn-primary">
                <svg v-if="saving" viewBox="0 0 24 24" class="h-4 w-4 animate-spin" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity="0.25" stroke-width="3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                {{ saving ? 'Saving…' : 'Save form' }}
            </button>
        </div>
    </div>
</template>
