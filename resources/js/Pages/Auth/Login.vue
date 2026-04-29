<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Connexion" />

        <div class="mb-8">
            <h1 class="font-['Outfit'] text-3xl font-bold text-slate-900">Connexion</h1>
            <p class="mt-2 text-sm text-slate-500">Accedez a votre espace MoovFlow.</p>
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

            <div>
                <InputLabel for="password" value="Mot de passe" />
                <TextInput id="password" v-model="form.password" type="password" class="mt-1 block w-full" required autocomplete="current-password" />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <label class="flex items-center gap-3">
                <Checkbox name="remember" v-model:checked="form.remember" />
                <span class="text-sm text-slate-600">Se souvenir de moi</span>
            </label>

            <div class="flex items-center justify-between gap-4">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm font-medium text-[#0066B3] transition hover:text-[#005290]"
                >
                    Mot de passe oublie ?
                </Link>

                <PrimaryButton class="ms-auto" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Connexion
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>