<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        required: true,
    },
    role: {
        type: Object,
        default: null,
    },
    permissionGroups: {
        type: Object,
        default: () => ({}),
    },
    submitUrl: {
        type: String,
        required: true,
    },
    method: {
        type: String,
        default: 'post',
    },
    submitLabel: {
        type: String,
        required: true,
    },
});

const form = useForm({
    name: props.role?.name ?? '',
    permissions: props.role?.permissions ?? [],
});

const permissionEntries = computed(() => Object.entries(props.permissionGroups ?? {}));

const submit = () => {
    if (props.method === 'patch') {
        form.patch(props.submitUrl);
        return;
    }

    form.post(props.submitUrl);
};
</script>

<template>
    <Head :title="title" />

    <AppLayout>
        <template #header>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">{{ title }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ subtitle }}</p>
            </div>
        </template>

        <form class="space-y-6" @submit.prevent="submit">
            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <label class="mb-2 block text-sm font-semibold text-slate-700">Nom du role</label>
                <input v-model="form.name" type="text" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                <p v-if="form.errors.name" class="mt-2 text-sm text-red-600">{{ form.errors.name }}</p>
            </div>

            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-lg font-semibold text-slate-900">Matrice de permissions</h2>
                <div class="mt-6 grid gap-4 lg:grid-cols-2">
                    <section v-for="[group, permissions] in permissionEntries" :key="group" class="rounded-2xl border border-slate-200 p-4">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">{{ group }}</h3>
                        <div class="mt-3 space-y-3">
                            <label v-for="(label, permission) in permissions" :key="permission" class="flex items-start gap-3">
                                <input v-model="form.permissions" :value="permission" type="checkbox" class="mt-1 rounded border-slate-300 text-[#0066B3] focus:ring-[#0066B3]">
                                <div>
                                    <p class="text-sm font-medium text-slate-800">{{ permission }}</p>
                                    <p class="text-xs text-slate-500">{{ label }}</p>
                                </div>
                            </label>
                        </div>
                    </section>
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="rounded-2xl bg-[#0066B3] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#005290]" :disabled="form.processing">
                    {{ submitLabel }}
                </button>
                <Link :href="route('admin.roles.index')" class="rounded-2xl bg-slate-100 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-200">
                    Retour aux roles
                </Link>
            </div>
        </form>
    </AppLayout>
</template>