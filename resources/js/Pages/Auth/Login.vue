<script setup>
import { computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'

defineProps({
    canResetPassword: Boolean,
    status:           String,
})

const flash = computed(() => usePage().props.flash ?? {})

const form = useForm({
    email:    '',
    password: '',
    remember: false,
})

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    })
}
</script>

<template>
    <div class="min-h-screen bg-page-bg font-sans antialiased">

        <!-- ═══════════ ENTÊTE LOGO ═══════════ -->
        <div class="pt-16 text-center">
            <h1 class="font-display text-5xl font-extrabold tracking-tight">
                <span class="text-moov-blue">MOOV</span>
                <span class="text-moov-blue ml-2">AFRICA</span>
            </h1>
            <p class="mt-2 text-xs font-semibold uppercase tracking-[0.3em]">
                <span class="text-moov-blue">Burkina Faso</span>
                <span class="mx-2 text-moov-orange">•</span>
                <span class="text-moov-orange">DCIRP</span>
            </p>
        </div>

        <!-- ═══════════ CARTE LOGIN ═══════════ -->
        <div class="mt-10 flex justify-center px-4">
            <div class="w-full max-w-md">

                <div class="overflow-hidden rounded-2xl bg-white shadow-card">

                    <!-- En-tête -->
                    <div class="border-b border-border-soft px-8 py-5 text-center">
                        <h2 class="text-sm font-bold uppercase tracking-[0.2em] text-text-main">
                            Authentification Système
                        </h2>
                    </div>

                    <!-- Body -->
                    <div class="px-8 py-8">

                        <!-- Status / Flash -->
                        <div v-if="status"
                             class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                            ✅ {{ status }}
                        </div>
                        <div v-if="flash.info"
                             class="mb-5 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700">
                            ℹ️ {{ flash.info }}
                        </div>

                        <!-- Erreur globale -->
                        <div v-if="form.errors.email"
                             class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            ⚠️ {{ form.errors.email }}
                        </div>

                        <form @submit.prevent="submit" class="space-y-5">

                            <!-- Email -->
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                    Identifiant 
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-text-muted">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                    </span>
                                    <input v-model="form.email"
                                           type="email"
                                           autocomplete="email"
                                           required
                                           placeholder="admin@moov.bf"
                                           class="w-full rounded-lg border border-border-soft bg-page-bg/50 py-3 pl-11 pr-4 text-sm font-semibold text-text-main outline-none transition focus:border-moov-blue focus:bg-white focus:ring-2 focus:ring-moov-blue/20"/>
                                </div>
                            </div>

                            <!-- Mot de passe -->
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                    Mot de passe
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-text-muted">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                        </svg>
                                    </span>
                                    <input v-model="form.password"
                                           type="password"
                                           autocomplete="current-password"
                                           required
                                           placeholder="••••••••"
                                           class="w-full rounded-lg border border-border-soft bg-page-bg/50 py-3 pl-11 pr-4 text-sm font-semibold text-text-main outline-none transition focus:border-moov-blue focus:bg-white focus:ring-2 focus:ring-moov-blue/20"/>
                                </div>
                                <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">
                                    {{ form.errors.password }}
                                </p>
                            </div>

                            <!-- Remember -->
                            <label class="flex items-center gap-2 text-xs text-text-sub cursor-pointer">
                                <input type="checkbox"
                                       v-model="form.remember"
                                       class="rounded border-border-soft text-moov-blue focus:ring-moov-blue"/>
                                Maintenir la session active
                            </label>

                            <!-- Bouton -->
                            <button type="submit"
                                    :disabled="form.processing"
                                    class="w-full rounded-lg bg-moov-noir px-4 py-3.5 text-sm font-bold uppercase tracking-wider text-white shadow-md transition hover:bg-moov-noir-soft disabled:opacity-60">
                                {{ form.processing ? 'Authentification...' : 'Se connecter' }}
                            </button>

                        </form>

                    </div>      
                </div>
                <!-- Lien register -->
                <p class="mt-6 text-center text-xs text-text-sub">
                    Pas encore de compte ?
                    <a :href="route('register')"
                       class="font-bold text-moov-orange hover:text-moov-orange-dark">
                        Demander un accès
                    </a>
                </p>

            </div>
        </div>

        
        <div class="mt-10 pb-8 text-center">
            <p class="text-xs font-bold uppercase tracking-[0.25em] text-text-muted">
                Moov Africa system
            </p>
            <div class="mx-auto mt-3 h-1 w-12 rounded bg-moov-orange"/>
        </div>
    </div>
</template>