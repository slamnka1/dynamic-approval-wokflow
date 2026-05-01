<script setup>
defineProps({
    field: { type: Object, required: true },
    modelValue: { default: null },
});
defineEmits(['update:modelValue']);
</script>

<template>
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">
            {{ field.label }}
            <span v-if="field.is_required" class="text-rose-500">*</span>
        </label>

        <input
            v-if="field.type === 'text'"
            type="text"
            :placeholder="field.placeholder"
            :value="modelValue"
            @input="$emit('update:modelValue', $event.target.value)"
            class="w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border"
        />

        <textarea
            v-else-if="field.type === 'textarea'"
            :placeholder="field.placeholder"
            :value="modelValue"
            @input="$emit('update:modelValue', $event.target.value)"
            rows="4"
            class="w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border"
        />

        <input
            v-else-if="field.type === 'number'"
            type="number"
            :min="field.min_value ?? undefined"
            :max="field.max_value ?? undefined"
            :placeholder="field.placeholder"
            :value="modelValue"
            @input="$emit('update:modelValue', $event.target.value)"
            class="w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border"
        />

        <input
            v-else-if="field.type === 'date'"
            type="date"
            :value="modelValue"
            @input="$emit('update:modelValue', $event.target.value)"
            class="w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border"
        />

        <select
            v-else-if="field.type === 'select'"
            :value="modelValue"
            @change="$emit('update:modelValue', $event.target.value)"
            class="w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border bg-white"
        >
            <option value="">— select —</option>
            <option v-for="o in field.options" :key="o.id ?? o.value" :value="o.value">{{ o.label }}</option>
        </select>

        <label v-else-if="field.type === 'checkbox'" class="inline-flex items-center gap-2">
            <input
                type="checkbox"
                :checked="!!modelValue"
                @change="$emit('update:modelValue', $event.target.checked)"
                class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
            />
            <span class="text-sm text-slate-700">Yes</span>
        </label>

        <input
            v-else-if="field.type === 'file'"
            type="file"
            @change="$emit('update:modelValue', $event.target.files[0] || null)"
            class="block w-full text-sm text-slate-700 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-600 file:px-3 file:py-2 file:text-white hover:file:bg-indigo-700"
        />
    </div>
</template>
