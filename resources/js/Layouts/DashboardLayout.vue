<script setup>
import { computed, ref } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'

const page = usePage()
const user = computed(() => page.props.auth?.user ?? null)
const flash = computed(() => page.props.flash ?? {})
const roles = computed(() => user.value?.roles ?? [])

const sidebarOpen = ref(true)

// ── Couleur d'accent par rôle ─────────────
const couleurRole = computed(() => {
    if (roles.value.includes('admin')) return { bg: 'bg-red-600', text: 'text-red-600', label: 'ADMIN' }
    if (roles.value.includes('responsable_dcirp')) return { bg: 'bg-purple-600', text: 'text-purple-600', label: 'RESPONSABLE_DCIRP' }
    if (roles.value.includes('organisateur')) return { bg: 'bg-moov-orange', text: 'text-moov-orange', label: 'ORGANISATEUR' }
    if (roles.value.includes('participant')) return { bg: 'bg-emerald-600', text: 'text-emerald-600', label: 'PARTICIPANT' }
    if (roles.value.includes('intervenant')) return { bg: 'bg-blue-600', text: 'text-blue-600', label: 'INTERVENANT' }
    if (roles.value.includes('benevole')) return { bg: 'bg-amber-600', text: 'text-amber-600', label: 'BÉNÉVOLE' }
    return { bg: 'bg-slate-600', text: 'text-slate-600', label: 'UTILISATEUR' }
})

// ── Initiales utilisateur ─────────────────
const initials = computed(() => {
    if (!user.value) return ''
    const p = user.value.prenom?.[0] ?? ''
    const n = user.value.nom?.[0] ?? ''
    return (p + n).toUpperCase() || user.value.email?.[0]?.toUpperCase() || 'U'
})

// ── Menu adapté au rôle ────────────────────
const menus = computed(() => {
    if (roles.value.includes('admin')) {
        return [
            { label: 'Tableau de Bord', icon: '▦', href: '/dashboard' },
            { label: 'Gestion Utilisateurs', icon: '👥', href: '/admin/users' },
            { label: 'Gestion Événements', icon: '◷', href: '/evenements' },
            { label: 'Dossiers Inscriptions', icon: '◫', href: '/inscriptions' },
            { label: 'Annuaire Participants', icon: '◉', href: '/annuaire' },
            { label: 'Logistique & Stocks', icon: '⬒', href: '#' },
            { label: 'Impact RSE & Rapports', icon: '◲', href: '#' },
            { label: 'Paramètres Système', icon: '⚙', href: '/admin/settings' },
            { label: ' Bénévoles', icon: '', href: '/vivier/benevoles' },
            { label: 'Intervenants', icon: '', href: '/vivier/intervenants' },
        ]
    }

    if (roles.value.includes('responsable_dcirp')) {
        return [
            { label: 'Tableau de Bord', icon: '▦', href: '/dashboard' },
            { label: 'Gestion Événements', icon: '◷', href: '/evenements' },
            { label: 'Dossiers Inscriptions', icon: '◫', href: '/inscriptions' },
            { label: 'Annuaire Participants', icon: '📇', href: '/annuaire' },
            { label: 'Validation', icon: '✓', href: '/inscriptions?statut=en_attente' },
            { label: 'Impact RSE & Rapports', icon: '📊', href: '/rapports' },
            { label: ' Bénévoles', icon: '', href: '/vivier/benevoles' },
            { label: ' Intervenants', icon: '', href: '/vivier/intervenants' },
        ]
    }

    if (roles.value.includes('organisateur')) {
        return [
            { label: 'Tableau de Bord', icon: '▦', href: '/dashboard' },
            { label: 'Mes Événements', icon: '◷', href: '/evenements' },
            { label: 'Nouvel Événement', icon: '➕', href: '/evenements/create' },
            { label: 'Dossiers Inscriptions', icon: '◫', href: '/inscriptions' },
            { label: 'Annuaire Participants', icon: '📇', href: '/annuaire' },
            { label: 'Logistique & Stocks', icon: '⬒', href: '#' },
            { label: 'Scanner Billets', icon: '📷', href: '#' },
            { label: 'Annuaire Participants', icon: '👤', href: '#' },
        ]
    }

    if (roles.value.includes('participant')) {
        return [
            { label: 'Tableau de Bord', icon: '▦', href: '/dashboard' },
            { label: 'Événements', icon: '◷', href: '/evenements' },
            { label: 'Mes Inscriptions', icon: '◫', href: '/mes-inscriptions' },
        ]
    }

    return [
        { label: 'Tableau de Bord', icon: '▦', href: '/dashboard' },
        { label: 'Événements', icon: '◷', href: '/evenements' },
    ]
})

const estActif = (href) => {
    if (href === '#') return false
    return page.url === href || (href.length > 1 && page.url.startsWith(href))
}

const logout = () => router.post(route('logout'))
</script>

<template>
    <div class="flex min-h-screen bg-page-bg font-sans antialiased">


        <aside :class="[
            'fixed inset-y-0 left-0 z-40 flex flex-col bg-moov-blue text-white shadow-sidebar transition-all duration-300',
            sidebarOpen ? 'w-64' : 'w-20'
        ]">

            <!-- Logo -->
            <div class="border-b border-white/10 px-5 py-6">
                <div v-if="sidebarOpen">
                    <h1 class="text-xl font-extrabold tracking-tight text-white">MOOV AFRICA</h1>
                    <p class="mt-0.5 text-xs font-medium text-white/60">Portail dCIRP</p>
                </div>
                <div v-else class="text-center text-xl font-extrabold">M</div>
            </div>

            <!-- Menu principal -->
            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-5">
                <Link v-for="item in menus" :key="item.label" :href="item.href" :class="[
                    'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold transition',
                    estActif(item.href)
                        ? 'bg-white/15 text-white shadow-sm'
                        : 'text-white/75 hover:bg-white/10 hover:text-white'
                ]">
                    <span class="text-lg w-5 text-center flex-shrink-0">{{ item.icon }}</span>
                    <span v-if="sidebarOpen" class="truncate">{{ item.label }}</span>
                </Link>
            </nav>

            <!-- Profil utilisateur -->
            <div class="border-t border-white/10 p-3">
                <div v-if="sidebarOpen" class="mb-3 flex items-center gap-3 rounded-lg bg-white/5 px-3 py-2.5">
                    <div
                        :class="[couleurRole.bg, 'flex h-9 w-9 flex-shrink-0 items-center justify-center rounded font-bold text-white text-sm']">
                        {{ initials }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-bold text-white">
                            {{ user?.prenom }} {{ user?.nom }}
                        </p>
                        <p class="truncate text-[10px] font-bold tracking-wider text-white/50">
                            {{ couleurRole.label }}
                        </p>
                    </div>
                </div>

                <button @click="logout"
                    class="flex w-full items-center gap-3 rounded-lg border border-white/20 bg-white/5 px-3 py-2.5 text-sm font-semibold text-white/90 transition hover:bg-white/15">
                    <span class="text-base w-5 text-center"></span>
                    <span v-if="sidebarOpen">DÉCONNEXION</span>
                </button>

                <p v-if="sidebarOpen" class="mt-3 text-center text-[10px] text-white/40">
                    MoovFlow • Burkina Faso
                </p>
            </div>
        </aside>

        <!-- ═══════════ CONTENU ═══════════ -->
        <div :class="['flex flex-1 flex-col transition-all duration-300', sidebarOpen ? 'ml-64' : 'ml-20']">

            <!-- Header bar -->
            <header class="sticky top-0 z-30 border-b border-border-soft bg-white px-6 py-3 shadow-sm">
                <div class="flex items-center justify-between">
                    <button @click="sidebarOpen = !sidebarOpen" class="rounded-lg p-2 text-text-sub hover:bg-page-bg">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <div class="flex items-center gap-4">
                        <Link :href="route('evenements.index')"
                            class="text-xs font-semibold text-text-sub hover:text-moov-blue">
                            Voir le site public
                        </Link>
                        <div class="flex items-center gap-2">
                            <div
                                :class="[couleurRole.bg, 'flex h-9 w-9 items-center justify-center rounded-full font-bold text-white text-sm']">
                                {{ initials }}
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Flash messages -->
            <div v-if="flash.success"
                class="border-b border-emerald-200 bg-emerald-50 px-6 py-2.5 text-sm font-medium text-emerald-700">
                ✅ {{ flash.success }}
            </div>
            <div v-if="flash.error"
                class="border-b border-red-200 bg-red-50 px-6 py-2.5 text-sm font-medium text-red-700">
                ❌ {{ flash.error }}
            </div>
            <div v-if="flash.info"
                class="border-b border-blue-200 bg-blue-50 px-6 py-2.5 text-sm font-medium text-blue-700">
                ℹ️ {{ flash.info }}
            </div>

            <!-- Slot principal -->
            <main class="flex-1 p-6">
                <slot />
            </main>
        </div>
    </div>
</template>