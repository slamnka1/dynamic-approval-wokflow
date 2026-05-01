<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import client from '../../api/client';
import DynamicForm from '../../components/DynamicForm.vue';
import { useUiStore } from '../../stores/ui';

const props = defineProps({ id: { type: [String, Number], required: true } });
const router = useRouter();
const ui = useUiStore();

const form = ref(null);
const values = ref({});
const errors = ref({});
const submitting = ref(false);

onMounted(async () => {
    const { data } = await client.get(`/forms/${props.id}`);
    form.value = data.data;
    for (const f of form.value.fields) {
        values.value[f.key] = f.type === 'checkbox' ? false : '';
    }
});

const buildPayload = () => {
    const hasFile = Object.values(values.value).some((v) => v instanceof File);
    if (!hasFile) return { values: values.value };

    const fd = new FormData();
    for (const [key, val] of Object.entries(values.value)) {
        if (val instanceof File) {
            fd.append(`values[${key}]`, val);
        } else if (typeof val === 'boolean') {
            fd.append(`values[${key}]`, val ? '1' : '0');
        } else if (val !== null && val !== undefined && val !== '') {
            fd.append(`values[${key}]`, val);
        }
    }
    return fd;
};

const submit = async () => {
    submitting.value = true;
    errors.value = {};
    try {
        await client.post(`/forms/${props.id}/requests`, buildPayload());
        ui.success('Request submitted.');
        router.push({ name: 'my.requests' });
    } catch (e) {
        errors.value = e.response?.data?.errors || {};
        ui.error(e.response?.data?.message || 'Failed to submit.');
    } finally {
        submitting.value = false;
    }
};
</script>

<template>
    <div v-if="!form" class="card p-8 text-center muted">Loading…</div>
    <div v-else class="space-y-6">
        <div class="flex items-center gap-3">
            <router-link :to="{ name: 'forms' }" class="btn-ghost p-2">
                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
            </router-link>
            <div>
                <h1 class="text-2xl font-bold text-ink-900">{{ form.name }}</h1>
                <p class="text-sm muted">{{ form.description || 'Fill in the fields below to file a new request.' }}</p>
            </div>
        </div>

        <div class="grid lg:grid-cols-3 gap-6">
            <form @submit.prevent="submit" class="card p-6 space-y-5 lg:col-span-2">
                <div v-if="errors.form" class="rounded-lg bg-rose-50 border border-rose-200 p-3 flex items-start gap-2.5">
                    <svg viewBox="0 0 24 24" class="h-5 w-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
                    <p class="text-sm text-rose-800">{{ errors.form[0] }}</p>
                </div>

                <DynamicForm v-model="values" :fields="form.fields" :errors="errors" />

                <div class="flex justify-end gap-2 pt-2 border-t border-ink-100">
                    <router-link :to="{ name: 'forms' }" class="btn-secondary">Cancel</router-link>
                    <button :disabled="submitting" class="btn-primary">
                        <svg v-if="submitting" viewBox="0 0 24 24" class="h-4 w-4 animate-spin" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity="0.25" stroke-width="3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                        {{ submitting ? 'Submitting…' : 'Submit request' }}
                    </button>
                </div>
            </form>

            <aside class="lg:col-span-1 space-y-4">
                <div v-if="form.workflow" class="card p-5">
                    <h3 class="text-xs uppercase tracking-wider muted mb-3">Approval workflow</h3>
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-ink-900">{{ form.workflow.name }}</span>
                        <span class="chip" :class="form.workflow.type === 'sequential' ? 'bg-sky-100 text-sky-700' : 'bg-violet-100 text-violet-700'">
                            {{ form.workflow.type }}
                        </span>
                    </div>
                    <p class="text-xs muted mt-2">
                        {{ form.workflow.type === 'sequential'
                            ? 'Each approver must act in order.'
                            : `Any ${form.workflow.required_approvals} approval${form.workflow.required_approvals === 1 ? '' : 's'} from the pool will approve.` }}
                    </p>

                    <ol class="mt-4 space-y-2.5">
                        <li v-for="(s, i) in form.workflow.steps" :key="s.id ?? i" class="flex items-center gap-2.5 text-sm">
                            <span class="h-6 w-6 rounded-full bg-brand-50 text-brand-700 text-xs font-semibold flex items-center justify-center shrink-0">
                                {{ form.workflow.type === 'sequential' ? i + 1 : '·' }}
                            </span>
                            <span class="text-ink-800">{{ s.approver?.name }}</span>
                        </li>
                    </ol>
                </div>

                <div class="card p-5">
                    <h3 class="text-xs uppercase tracking-wider muted mb-3">Tip</h3>
                    <p class="text-sm text-ink-700">Required fields are marked with <span class="text-rose-500">*</span>. Make sure all values are accurate before submitting — you can't edit a request once it's filed.</p>
                </div>
            </aside>
        </div>
    </div>
</template>
