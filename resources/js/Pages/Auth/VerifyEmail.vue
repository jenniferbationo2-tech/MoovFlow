<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <GuestLayout>
        <Head title="Verification du courriel" />

        <div class="mb-8">
            <h1 class="font-['Outfit'] text-3xl font-bold text-slate-900">Verification du courriel</h1>
            <p class="mt-2 text-sm text-slate-500">
                Consultez votre boite de reception puis cliquez sur le lien de verification que nous venons d'envoyer.
            </p>
        </div>

        <div v-if="verificationLinkSent" class="mb-4 rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
            Un nouveau lien de verification a ete envoye a votre adresse.
        </div>

        <form @submit.prevent="submit">
            <div class="mt-4 flex items-center justify-between gap-4">
                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Renvoyer le courriel
                </PrimaryButton>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="text-sm font-medium text-slate-600 transition hover:text-slate-900"
                >
                    Deconnexion
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>