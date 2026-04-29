<script setup>
import { computed } from 'vue';

const props = defineProps({
    label: {
        type: String,
        required: true,
    },
    value: {
        type: [String, Number],
        required: true,
    },
    color: {
        type: String,
        default: 'blue',
    },
    icon: {
        type: String,
        default: 'M12 6v6l4 2',
    },
});

const palette = computed(() => ({
    blue: {
        card: 'border-[#0066B3]/20 bg-white',
        accent: 'bg-[#0066B3]/10 text-[#0066B3]',
    },
    green: {
        card: 'border-[#00A651]/20 bg-white',
        accent: 'bg-[#00A651]/10 text-[#00A651]',
    },
    slate: {
        card: 'border-slate-200 bg-white',
        accent: 'bg-slate-100 text-slate-700',
    },
}[props.color] ?? {
    card: 'border-[#0066B3]/20 bg-white',
    accent: 'bg-[#0066B3]/10 text-[#0066B3]',
}));
</script>

<template>
    <div class="rounded-[1.75rem] border p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md" :class="palette.card">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-slate-500">{{ label }}</p>
                <p class="mt-2 text-3xl font-bold text-slate-900">{{ value }}</p>
            </div>

            <div class="flex h-12 w-12 items-center justify-center rounded-xl" :class="palette.accent">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" :d="icon" />
                </svg>
            </div>
        </div>
    </div>
</template>