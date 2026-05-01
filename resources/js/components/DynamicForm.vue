<script setup>
import FieldRenderer from './FieldRenderer.vue';

defineProps({
    fields: { type: Array, required: true },
    modelValue: { type: Object, required: true },
    errors: { type: Object, default: () => ({}) },
});
defineEmits(['update:modelValue']);

const setValue = (key, value, modelValue, emit) => {
    emit('update:modelValue', { ...modelValue, [key]: value });
};
</script>

<template>
    <div class="space-y-4">
        <div v-for="f in fields" :key="f.id ?? f.key">
            <FieldRenderer
                :field="f"
                :model-value="modelValue[f.key]"
                @update:model-value="(v) => $emit('update:modelValue', { ...modelValue, [f.key]: v })"
            />
            <p v-if="errors[`values.${f.key}`]" class="mt-1 text-xs text-rose-600">
                {{ errors[`values.${f.key}`][0] }}
            </p>
        </div>
    </div>
</template>
