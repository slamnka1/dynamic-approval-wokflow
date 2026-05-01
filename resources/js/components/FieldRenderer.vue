<script setup>
defineProps({
    field: { type: Object, required: true },
    modelValue: { default: null },
});
defineEmits(['update:modelValue']);
</script>

<template>
    <div>
        <label class="flex items-center gap-1 text-sm font-medium text-ink-800 mb-1.5">
            {{ field.label }}
            <span v-if="field.is_required" class="text-rose-500">*</span>
        </label>

        <input
            v-if="field.type === 'text'"
            type="text"
            :placeholder="field.placeholder"
            :value="modelValue"
            @input="$emit('update:modelValue', $event.target.value)"
            class="input"
        />

        <textarea
            v-else-if="field.type === 'textarea'"
            :placeholder="field.placeholder"
            :value="modelValue"
            @input="$emit('update:modelValue', $event.target.value)"
            rows="4"
            class="input"
        />

        <input
            v-else-if="field.type === 'number'"
            type="number"
            :min="field.min_value ?? undefined"
            :max="field.max_value ?? undefined"
            :placeholder="field.placeholder"
            :value="modelValue"
            @input="$emit('update:modelValue', $event.target.value)"
            class="input"
        />

        <input
            v-else-if="field.type === 'date'"
            type="date"
            :value="modelValue"
            @input="$emit('update:modelValue', $event.target.value)"
            class="input"
        />

        <select
            v-else-if="field.type === 'select'"
            :value="modelValue"
            @change="$emit('update:modelValue', $event.target.value)"
            class="input"
        >
            <option value="">— select —</option>
            <option v-for="o in field.options" :key="o.id ?? o.value" :value="o.value">{{ o.label }}</option>
        </select>

        <label v-else-if="field.type === 'checkbox'" class="flex items-center gap-2.5 px-3 py-2 rounded-lg border border-ink-200 bg-white cursor-pointer hover:bg-ink-50 transition">
            <input
                type="checkbox"
                :checked="!!modelValue"
                @change="$emit('update:modelValue', $event.target.checked)"
                class="h-4 w-4 rounded border-ink-300 text-brand-600 focus:ring-brand-500"
            />
            <span class="text-sm text-ink-700">{{ modelValue ? 'Yes' : 'No' }}</span>
        </label>

        <input
            v-else-if="field.type === 'file'"
            type="file"
            accept=".jpg,.jpeg,.png,.gif,.webp,.svg,.bmp,.csv,.txt,.xlsx,.xls,.pdf,image/*"
            @change="$emit('update:modelValue', $event.target.files[0] || null)"
            class="block w-full text-sm text-ink-700 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-600 file:px-3.5 file:py-2 file:text-white file:font-medium hover:file:bg-brand-700"
        />

        <p v-if="field.placeholder && (field.type === 'select' || field.type === 'date' || field.type === 'checkbox' || field.type === 'file')" class="text-xs muted mt-1">
            {{ field.placeholder }}
        </p>
    </div>
</template>
