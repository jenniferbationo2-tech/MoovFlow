<script setup>
import { computed } from 'vue';

const props = defineProps({
    type: {
        type: Object,
        default: null,
    },
});

const themes = {
    conference: 'bg-[#0066B3]/10 text-[#0066B3] ring-[#0066B3]/20',
    forum: 'bg-violet-100 text-violet-700 ring-violet-200',
    atelier: 'bg-amber-100 text-amber-700 ring-amber-200',
    gala: 'bg-pink-100 text-pink-700 ring-pink-200',
    formation: 'bg-cyan-100 text-cyan-700 ring-cyan-200',
    meetup: 'bg-lime-100 text-lime-700 ring-lime-200',
    salon: 'bg-[#00A651]/10 text-[#008a45] ring-[#00A651]/20',
};

const badgeClass = computed(() => {
    const key = (props.type?.code ?? props.type?.nom ?? '').toString().toLowerCase();

    return themes[key] ?? 'bg-slate-100 text-slate-700 ring-slate-200';
});

const label = computed(() => props.type?.nom ?? 'Non defini');
</script>

<template>
    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset shadow-sm" :class="badgeClass">
        {{ label }}
    </span>
</template>