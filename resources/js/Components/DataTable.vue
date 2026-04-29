<script setup>
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    columns: {
        type: Array,
        default: () => [],
    },
    rows: {
        type: Array,
        default: () => [],
    },
    pagination: {
        type: Object,
        default: null,
    },
    sortBy: {
        type: String,
        default: '',
    },
    sortDirection: {
        type: String,
        default: 'asc',
    },
    emptyMessage: {
        type: String,
        default: 'Aucune donnee disponible.',
    },
});

const emit = defineEmits(['sort']);

const localSortBy = ref(props.sortBy);
const localSortDirection = ref(props.sortDirection);

watch(() => props.sortBy, (value) => {
    localSortBy.value = value;
});

watch(() => props.sortDirection, (value) => {
    localSortDirection.value = value;
});

const visibleColumns = computed(() => props.columns.filter((column) => column.key !== 'actions'));

const toggleSort = (column) => {
    if (!column.sortable) {
        return;
    }

    if (localSortBy.value === column.key) {
        localSortDirection.value = localSortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        localSortBy.value = column.key;
        localSortDirection.value = 'asc';
    }

    emit('sort', {
        sortBy: localSortBy.value,
        sortDirection: localSortDirection.value,
    });
};

const visitPage = (url) => {
    if (!url) {
        return;
    }

    router.visit(url, {
        preserveScroll: true,
        preserveState: true,
    });
};
</script>

<template>
    <div class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm transition hover:shadow-md">
        <div class="hidden overflow-x-auto md:block">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50/90">
                    <tr>
                        <th
                            v-for="column in columns"
                            :key="column.key"
                            class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
                        >
                            <button
                                v-if="column.sortable"
                                type="button"
                                class="inline-flex items-center gap-2 transition hover:text-[#0066B3]"
                                @click="toggleSort(column)"
                            >
                                <span>{{ column.label }}</span>
                                <span v-if="localSortBy === column.key">{{ localSortDirection === 'asc' ? '↑' : '↓' }}</span>
                            </button>
                            <span v-else>{{ column.label }}</span>
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    <tr v-if="rows.length === 0">
                        <td :colspan="columns.length" class="px-6 py-10 text-center text-sm text-slate-500">
                            {{ emptyMessage }}
                        </td>
                    </tr>

                    <tr v-for="row in rows" :key="row.id" class="transition hover:bg-slate-50/80">
                        <td v-for="column in columns" :key="column.key" class="px-6 py-4 align-top text-sm text-slate-700">
                            <slot :name="`cell-${column.key}`" :row="row">
                                {{ row[column.key] }}
                            </slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="space-y-4 p-4 md:hidden">
            <div
                v-for="row in rows"
                :key="row.id"
                class="rounded-[1.5rem] border border-slate-200 p-4 shadow-sm"
            >
                <div class="space-y-3">
                    <div v-for="column in visibleColumns" :key="column.key" class="flex items-start justify-between gap-4">
                        <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ column.label }}</span>
                        <div class="text-right text-sm text-slate-700">
                            <slot :name="`cell-${column.key}`" :row="row">
                                {{ row[column.key] }}
                            </slot>
                        </div>
                    </div>

                    <div v-if="$slots['cell-actions']" class="border-t border-slate-100 pt-3">
                        <slot name="cell-actions" :row="row" />
                    </div>
                </div>
            </div>

            <div v-if="rows.length === 0" class="rounded-[1.5rem] border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">
                {{ emptyMessage }}
            </div>
        </div>

        <div v-if="pagination?.links?.length" class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-4 py-4">
            <div class="text-sm text-slate-500">
                Affichage de {{ pagination.from ?? 0 }} a {{ pagination.to ?? 0 }} sur {{ pagination.total ?? rows.length }} resultats
            </div>

            <div class="flex flex-wrap gap-2">
                <button
                    v-for="(link, index) in pagination.links"
                    :key="`${index}-${link.label}`"
                    type="button"
                    class="rounded-xl px-3 py-2 text-sm font-medium transition"
                    :class="link.active ? 'bg-[#0066B3] text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                    :disabled="!link.url"
                    @click="visitPage(link.url)"
                >
                    <span v-html="link.label" />
                </button>
            </div>
        </div>
    </div>
</template>