<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Mot de passe oublie" />

        <div class="mb-8">
            <h1 class="font-['Outfit'] text-3xl font-bold text-slate-900">Mot de passe oublie</h1>
            <p class="mt-2 text-sm text-slate-500">
                Indiquez votre email et nous vous enverrons un lien pour definir un nouveau mot de passe.
            </p>
        </div>

        <div v-if="status" class="mb-4 rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
            {{ status }}
        </div>

        <form class="space-y-5" @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Courriel" />
                <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" required autofocus autocomplete="username" />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="flex justify-end">
                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Envoyer le lien
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>