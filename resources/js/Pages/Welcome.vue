<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
        default: false,
    },
    canRegister: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <Head title="MoovFlow" />

    <div class="min-h-screen bg-[#F8FAFC] text-slate-800">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(0,102,179,0.14),_transparent_30%),radial-gradient(circle_at_bottom_right,_rgba(255,152,0,0.12),_transparent_25%)]" />

        <div class="relative mx-auto flex min-h-screen max-w-7xl flex-col px-6 py-8 lg:px-8">
            <header class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-[#0066B3] to-[#00A651] text-lg font-bold text-white">
                        ◈
                    </div>
                    <div>
                        <p class="font-['Outfit'] text-2xl font-bold text-slate-900">MoovFlow</p>
                        <p class="text-sm text-slate-500">Gestion evenementielle premium</p>
                    </div>
                </div>

                <nav v-if="canLogin" class="flex items-center gap-3">
                    <Link
                        :href="route('login')"
                        class="rounded-2xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-white"
                    >
                        Connexion
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="rounded-2xl bg-[#0066B3] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#005290]"
                    >
                        Inscription
                    </Link>
                </nav>
            </header>

            <main class="flex flex-1 items-center py-12">
                <div class="grid gap-10 lg:grid-cols-[1.1fr,0.9fr] lg:items-center">
                    <section>
                        <span class="inline-flex rounded-full border border-[#0066B3]/10 bg-[#0066B3]/5 px-4 py-2 text-xs font-semibold uppercase tracking-[0.28em] text-[#0066B3]">
                            Inspire par Moov Africa et Tikerama
                        </span>
                        <h1 class="mt-6 font-['Outfit'] text-5xl font-bold leading-tight text-slate-900 sm:text-6xl">
                            Une plateforme elegante pour piloter vos evenements de bout en bout.
                        </h1>
                        <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-600">
                            MoovFlow centralise vos evenements, inscriptions, participants, communication et reporting dans une interface claire, rapide et professionnelle.
                        </p>

                        <div class="mt-8 flex flex-wrap gap-4">
                            <Link
                                v-if="canLogin"
                                :href="route('login')"
                                class="rounded-[1.5rem] bg-[#0066B3] px-6 py-4 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:bg-[#005290]"
                            >
                                Acceder a la plateforme
                            </Link>
                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="rounded-[1.5rem] border border-slate-300 px-6 py-4 text-sm font-semibold text-slate-700 transition hover:bg-white"
                            >
                                Creer un compte
                            </Link>
                        </div>

                        <div class="mt-10 grid gap-4 sm:grid-cols-3">
                            <div class="rounded-[1.75rem] bg-white p-5 shadow-sm ring-1 ring-slate-200">
                                <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Evenements</p>
                                <p class="mt-3 text-3xl font-bold text-slate-900">360°</p>
                            </div>
                            <div class="rounded-[1.75rem] bg-white p-5 shadow-sm ring-1 ring-slate-200">
                                <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Communication</p>
                                <p class="mt-3 text-3xl font-bold text-slate-900">Ciblee</p>
                            </div>
                            <div class="rounded-[1.75rem] bg-white p-5 shadow-sm ring-1 ring-slate-200">
                                <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Reporting</p>
                                <p class="mt-3 text-3xl font-bold text-slate-900">Temps reel</p>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-[2rem] bg-white p-6 shadow-[0_24px_80px_rgba(15,23,42,0.12)] ring-1 ring-slate-200">
                        <div class="rounded-[1.75rem] bg-gradient-to-br from-[#0066B3] to-[#004A82] p-6 text-white">
                            <p class="text-xs uppercase tracking-[0.28em] text-white/70">Vue operationnelle</p>
                            <h2 class="mt-3 font-['Outfit'] text-3xl font-bold">MoovFlow Control Room</h2>
                            <p class="mt-3 text-sm leading-7 text-white/80">
                                Une lecture rapide de l'activite, des taux de remplissage et des operations en cours.
                            </p>
                        </div>

                        <div class="mt-6 space-y-4">
                            <div class="rounded-[1.5rem] border border-slate-200 p-5">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">Tableau de bord executif</p>
                                        <p class="mt-1 text-sm text-slate-500">Suivi consolidé des KPIs evenementiels</p>
                                    </div>
                                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Actif</span>
                                </div>
                            </div>
                            <div class="rounded-[1.5rem] border border-slate-200 p-5">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">Inscriptions & QR codes</p>
                                        <p class="mt-1 text-sm text-slate-500">Parcours fluides pour vos participants</p>
                                    </div>
                                    <span class="rounded-full bg-[#0066B3]/10 px-3 py-1 text-xs font-semibold text-[#0066B3]">Optimise</span>
                                </div>
                            </div>
                            <div class="rounded-[1.5rem] border border-slate-200 p-5">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">Rapports & CRM</p>
                                        <p class="mt-1 text-sm text-slate-500">Des decisions eclairees a chaque etape</p>
                                    </div>
                                    <span class="rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-700">Premium</span>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </main>
        </div>
    </div>
</template>