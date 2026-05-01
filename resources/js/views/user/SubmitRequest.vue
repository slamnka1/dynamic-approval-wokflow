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

const submit = async () => {
    submitting.value = true;
    errors.value = {};
    try {
        await client.post(`/forms/${props.id}/requests`, { values: values.value });
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
    <div v-if="!form" class="text-slate-500">Loading…</div>
    <div v-else>
        <h1 class="text-xl font-semibold">{{ form.name }}</h1>
        <p class="text-sm text-slate-500 mb-6">{{ form.description }}</p>

        <form @submit.prevent="submit" class="bg-white border border-slate-200 rounded-lg p-6 space-y-4">
            <DynamicForm v-model="values" :fields="form.fields" :errors="errors" />

            <div class="flex justify-end gap-2 pt-2">
                <router-link :to="{ name: 'forms' }" class="px-4 py-2 text-sm text-slate-600 hover:underline">Cancel</router-link>
                <button :disabled="submitting" class="bg-indigo-600 text-white rounded-md px-4 py-2 hover:bg-indigo-700 disabled:opacity-60">
                    {{ submitting ? 'Submitting…' : 'Submit request' }}
                </button>
            </div>
        </form>
    </div>
</template>
