<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Confirmation du mot de passe" />

        <div class="mb-8">
            <h1 class="font-['Outfit'] text-3xl font-bold text-slate-900">Confirmation requise</h1>
            <p class="mt-2 text-sm text-slate-500">Veuillez confirmer votre mot de passe avant de continuer.</p>
        </div>

        <form class="space-y-5" @submit.prevent="submit">
            <div>
                <InputLabel for="password" value="Mot de passe" />
                <TextInput id="password" v-model="form.password" type="password" class="mt-1 block w-full" required autocomplete="current-password" autofocus />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="flex justify-end">
                <PrimaryButton class="ms-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Confirmer
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>