<script setup>
import { computed } from 'vue';

const props = defineProps({ status: { type: String, required: true } });

const palette = {
    pending: 'bg-amber-100 text-amber-800 ring-amber-200',
    approved: 'bg-emerald-100 text-emerald-800 ring-emerald-200',
    rejected: 'bg-rose-100 text-rose-800 ring-rose-200',
    cancelled: 'bg-slate-100 text-slate-700 ring-slate-200',
};

const cls = computed(() => palette[props.status] || palette.cancelled);
</script>

<template>
    <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ring-1', cls]">
        {{ status }}
    </span>
</template>
