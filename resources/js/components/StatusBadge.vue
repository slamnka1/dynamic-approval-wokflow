<script setup>
import { computed } from 'vue';

const props = defineProps({ status: { type: String, required: true } });

const config = {
    pending:   { cls: 'bg-amber-50 text-amber-700 ring-amber-200',   dot: 'bg-amber-500' },
    approved:  { cls: 'bg-emerald-50 text-emerald-700 ring-emerald-200', dot: 'bg-emerald-500' },
    rejected:  { cls: 'bg-rose-50 text-rose-700 ring-rose-200',     dot: 'bg-rose-500' },
    cancelled: { cls: 'bg-ink-100 text-ink-700 ring-ink-200',       dot: 'bg-ink-400' },
};

const c = computed(() => config[props.status] || config.cancelled);
</script>

<template>
    <span :class="['inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium ring-1 capitalize', c.cls]">
        <span :class="['h-1.5 w-1.5 rounded-full', c.dot]"></span>
        {{ status }}
    </span>
</template>
