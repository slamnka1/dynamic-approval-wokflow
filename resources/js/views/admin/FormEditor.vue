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
    <div>
        <h1 class="text-xl font-semibold mb-4">{{ isEdit ? 'Edit form' : 'New form' }}</h1>

        <div class="bg-white border border-slate-200 rounded-lg p-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm text-slate-700">Name</label>
                    <input v-model="form.name" class="w-full mt-1 border-slate-300 border rounded-md px-3 py-2" />
                    <p v-if="errors['name']" class="text-xs text-rose-600">{{ errors['name'][0] }}</p>
                </div>
                <div class="flex items-center gap-2 self-end">
                    <input id="active" type="checkbox" v-model="form.is_active" />
                    <label for="active" class="text-sm">Active</label>
                </div>
            </div>
            <div>
                <label class="text-sm text-slate-700">Description</label>
                <textarea v-model="form.description" rows="2" class="w-full mt-1 border-slate-300 border rounded-md px-3 py-2"></textarea>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-lg p-6 mt-6">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-semibold">Fields</h2>
                <button @click="form.fields.push(blankField())" class="text-sm text-indigo-600 hover:underline">+ Add field</button>
            </div>

            <div v-for="(f, idx) in form.fields" :key="idx" class="border border-slate-200 rounded-md p-4 mb-3 grid grid-cols-1 md:grid-cols-12 gap-3">
                <div class="md:col-span-3">
                    <label class="text-xs text-slate-500">Key</label>
                    <input v-model="f.key" placeholder="e.g. amount" class="w-full mt-1 border-slate-300 border rounded-md px-2 py-1 text-sm" />
                </div>
                <div class="md:col-span-4">
                    <label class="text-xs text-slate-500">Label</label>
                    <input v-model="f.label" class="w-full mt-1 border-slate-300 border rounded-md px-2 py-1 text-sm" />
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs text-slate-500">Type</label>
                    <select v-model="f.type" class="w-full mt-1 border-slate-300 border rounded-md px-2 py-1 text-sm bg-white">
                        <option v-for="t in ['text','textarea','number','select','date','checkbox','file']" :key="t" :value="t">{{ t }}</option>
                    </select>
                </div>
                <div class="md:col-span-2 flex items-center gap-2 mt-5">
                    <input :id="`req-${idx}`" type="checkbox" v-model="f.is_required" />
                    <label :for="`req-${idx}`" class="text-xs">Required</label>
                </div>
                <div class="md:col-span-1 mt-5 text-right">
                    <button @click="form.fields.splice(idx, 1)" class="text-rose-600 text-xs hover:underline">remove</button>
                </div>

                <div v-if="f.type === 'number'" class="md:col-span-3">
                    <label class="text-xs text-slate-500">Min</label>
                    <input v-model.number="f.min_value" type="number" class="w-full mt-1 border-slate-300 border rounded-md px-2 py-1 text-sm" />
                </div>
                <div v-if="f.type === 'number'" class="md:col-span-3">
                    <label class="text-xs text-slate-500">Max</label>
                    <input v-model.number="f.max_value" type="number" class="w-full mt-1 border-slate-300 border rounded-md px-2 py-1 text-sm" />
                </div>

                <div v-if="f.type === 'select'" class="md:col-span-12">
                    <div class="flex justify-between items-center">
                        <label class="text-xs text-slate-500">Options</label>
                        <button @click="f.options.push({ value: '', label: '' })" class="text-xs text-indigo-600">+ option</button>
                    </div>
                    <div v-for="(o, oi) in f.options" :key="oi" class="grid grid-cols-12 gap-2 mt-1">
                        <input v-model="o.value" placeholder="value" class="col-span-5 border-slate-300 border rounded-md px-2 py-1 text-sm" />
                        <input v-model="o.label" placeholder="label" class="col-span-6 border-slate-300 border rounded-md px-2 py-1 text-sm" />
                        <button @click="f.options.splice(oi,1)" class="col-span-1 text-rose-600 text-xs">×</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-lg p-6 mt-6">
            <h2 class="font-semibold mb-3">Workflow</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-3">
                <div>
                    <label class="text-xs text-slate-500">Name</label>
                    <input v-model="workflow.name" class="w-full mt-1 border-slate-300 border rounded-md px-2 py-1 text-sm" />
                </div>
                <div>
                    <label class="text-xs text-slate-500">Type</label>
                    <select v-model="workflow.type" class="w-full mt-1 border-slate-300 border rounded-md px-2 py-1 text-sm bg-white">
                        <option value="sequential">sequential</option>
                        <option value="threshold">threshold</option>
                    </select>
                </div>
                <div v-if="workflow.type === 'threshold'">
                    <label class="text-xs text-slate-500">Required approvals</label>
                    <input v-model.number="workflow.required_approvals" type="number" min="1" class="w-full mt-1 border-slate-300 border rounded-md px-2 py-1 text-sm" />
                </div>
            </div>

            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium">{{ workflow.type === 'sequential' ? 'Ordered approvers' : 'Approver pool' }}</span>
                <button @click="workflow.steps.push(blankStep())" class="text-xs text-indigo-600">+ approver</button>
            </div>
            <div v-for="(s, si) in workflow.steps" :key="si" class="flex items-center gap-2 mb-2">
                <span class="text-xs text-slate-400 w-6">{{ si + 1 }}.</span>
                <select v-model.number="s.approver_id" class="flex-1 border-slate-300 border rounded-md px-2 py-1 text-sm bg-white">
                    <option v-for="a in approvers" :key="a.id" :value="a.id">{{ a.name }} ({{ a.email }})</option>
                </select>
                <button @click="workflow.steps.splice(si,1)" class="text-rose-600 text-xs">remove</button>
            </div>
        </div>

        <div class="flex justify-end gap-2 mt-6">
            <router-link :to="{ name: 'admin.forms' }" class="px-4 py-2 text-sm text-slate-600">Cancel</router-link>
            <button :disabled="saving" @click="save" class="bg-indigo-600 text-white rounded-md px-4 py-2 hover:bg-indigo-700 disabled:opacity-60">
                {{ saving ? 'Saving…' : 'Save' }}
            </button>
        </div>
    </div>
</template>
