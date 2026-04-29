<script setup>
import { computed } from 'vue';

const props = defineProps({
    tabs: {
        type: Array,
        default: () => [],
    },
    activeTab: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:activeTab']);

const currentTab = computed(() => props.activeTab || props.tabs[0]?.value || '');

const setActive = (value) => {
    emit('update:activeTab', value);
};
</script>

<template>
    <div class="space-y-6">
        <div class="overflow-x-auto">
            <div class="inline-flex min-w-full gap-2 border-b border-slate-200 pb-1">
                <button
                    v-for="tab in tabs"
                    :key="tab.value"
                    type="button"
                    class="whitespace-nowrap border-b-2 px-4 py-3 text-sm font-semibold transition"
                    :class="currentTab === tab.value ? 'border-[#0066B3] text-[#0066B3]' : 'border-transparent text-slate-500 hover:text-slate-800'"
                    @click="setActive(tab.value)"
                >
                    {{ tab.label }}
                </button>
            </div>
        </div>

        <div>
            <slot :active-tab="currentTab" />
        </div>
    </div>
</template>