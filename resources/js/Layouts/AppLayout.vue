<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const page = usePage();
const isMobileOpen = ref(false);
const showUserMenu = ref(false);
const visibleFlash = ref({ type: null, message: '' });
let flashTimer = null;

const user = computed(() => page.props.auth?.user ?? null);
const roles = computed(() => user.value?.roles ?? []);
const canManageAdmin = computed(() => roles.value.includes('admin') || roles.value.includes('responsable_dcirp'));
const appName = computed(() => page.props.appName ?? 'MoovFlow');

const navigationGroups = computed(() => [
    [
        { label: 'Tableau de bord', route: 'dashboard', icon: '▦' },
        { label: 'Evenements', route: 'evenements.index', icon: '◷' },
        { label: 'Inscriptions', route: 'inscriptions.index', icon: '◫' },
        { label: 'Participants', route: 'participants.index', icon: '◉' },
    ],
    [
        { label: 'Logistique', route: 'evenements.index', icon: '⬒' },
        { label: 'Communication', route: 'notifications.index', icon: '◌' },
        { label: 'Rapports', route: 'rapports.index', icon: '◲' },
        { label: 'CRM', route: 'crm.contacts.index', icon: '◎' },
    ],
    canManageAdmin.value ? [
        { label: 'Administration', route: 'admin.users.index', icon: '⚙' },
    ] : [],
]);

const flatNavigation = computed(() => navigationGroups.value.flat().filter(Boolean));

const currentLabel = computed(() => {
    const currentItem = flatNavigation.value.find((item) => route().current(item.route));
    return currentItem?.label ?? 'Tableau de bord';
});

const breadcrumbs = computed(() => {
    const segment = page.component?.split('/') ?? [];
    const section = flatNavigation.value.find((item) => item.label.toLowerCase() === (segment[0] ?? '').toLowerCase());
    const labels = {
        Dashboard: 'Tableau de bord',
        Evenements: 'Evenements',
        Inscriptions: 'Inscriptions',
        Participants: 'Participants',
        Admin: 'Administration',
        Users: 'Utilisateurs',
        Roles: 'Roles',
        Settings: 'Parametres',
        Communication: 'Communication',
        CRM: 'CRM',
        Rapports: 'Rapports',
        Profile: 'Profil',
        Create: 'Creer',
        Edit: 'Modifier',
        Show: 'Details',
        Index: 'Liste',
    };

    const result = [];

    if (section) {
        result.push(section.label);
    }

    for (const part of segment.slice(section ? 1 : 0)) {
        const translated = labels[part] ?? part;
        if (translated !== result[result.length - 1]) {
            result.push(translated);
        }
    }

    return result.length ? result : [currentLabel.value];
});

const initials = computed(() => {
    const source = user.value?.name || user.value?.email || 'MF';
    return source
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase();
});

const triggerFlash = () => {
    const success = page.props.flash?.success;
    const error = page.props.flash?.error;
    const message = success || error;

    if (!message) {
        return;
    }

    visibleFlash.value = {
        type: success ? 'success' : 'error',
        message,
    };

    clearTimeout(flashTimer);
    flashTimer = setTimeout(() => {
        visibleFlash.value = { type: null, message: '' };
    }, 3000);
};

const isActive = (item) => route().current(item.route) || route().current(`${item.route.replace('.index', '')}.*`);

const closeMenus = () => {
    isMobileOpen.value = false;
    showUserMenu.value = false;
};

watch(() => page.props.flash, triggerFlash, { deep: true, immediate: true });

onMounted(() => {
    window.addEventListener('resize', closeMenus);
});

onBeforeUnmount(() => {
    clearTimeout(flashTimer);
    window.removeEventListener('resize', closeMenus);
});
</script>

<template>
    <div class="min-h-screen bg-[#F8FAFC] text-slate-800">
        <div
            v-if="isMobileOpen"
            class="fixed inset-0 z-40 bg-slate-950/50 backdrop-blur-sm lg:hidden"
            @click="isMobileOpen = false"
        />

        <transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="translate-y-2 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-2 opacity-0"
        >
            <div
                v-if="visibleFlash.message"
                class="fixed right-4 top-4 z-[70] w-full max-w-sm rounded-2xl border px-4 py-4 shadow-lg"
                :class="visibleFlash.type === 'success'
                    ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                    : 'border-red-200 bg-red-50 text-red-800'"
            >
                <div class="flex items-start gap-3">
                    <div
                        class="mt-0.5 flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold"
                        :class="visibleFlash.type === 'success'
                            ? 'bg-emerald-100 text-emerald-700'
                            : 'bg-red-100 text-red-700'"
                    >
                        {{ visibleFlash.type === 'success' ? '✓' : '!' }}
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold">
                            {{ visibleFlash.type === 'success' ? 'Operation reussie' : 'Attention' }}
                        </p>
                        <p class="mt-1 text-sm">{{ visibleFlash.message }}</p>
                    </div>
                </div>
            </div>
        </transition>

        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-[260px] flex-col overflow-hidden bg-gradient-to-b from-[#0066B3] to-[#004A82] text-white shadow-2xl transition-transform duration-300 lg:translate-x-0"
            :class="isMobileOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="border-b border-white/10 px-6 py-6">
                <Link :href="route('dashboard')" class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/12 text-lg font-bold shadow-inner shadow-white/10">
                        <img src="../Components/ApplicationLogo.vue"/>
                    </div>
                    <div>
                        <p class="font-['Outfit'] text-xl font-bold tracking-wide">{{ appName }}</p>
                    </div>
                </Link>
            </div>

            <div class="flex-1 overflow-y-auto px-4 py-6">
                <div
                    v-for="(group, groupIndex) in navigationGroups"
                    :key="`group-${groupIndex}`"
                    class="mb-5"
                >
                    <div v-if="groupIndex !== 0" class="mb-4 border-t border-white/10" />
                    <nav class="space-y-1.5">
                        <Link
                            v-for="item in group"
                            :key="item.route"
                            :href="route(item.route)"
                            class="group flex items-center gap-3 rounded-2xl border-l-4 px-4 py-3 text-sm font-medium transition duration-200"
                            :class="isActive(item)
                                ? 'border-white bg-white/10 text-white shadow-lg shadow-slate-950/10'
                                : 'border-transparent text-white/80 hover:bg-white/5 hover:text-white'"
                            @click="isMobileOpen = false"
                        >
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/10 text-sm transition group-hover:bg-white/15">
                                {{ item.icon }}
                            </span>
                            <span>{{ item.label }}</span>
                        </Link>
                    </nav>
                </div>
            </div>

            <div class="border-t border-white/10 p-4">
                <div class="rounded-2xl bg-white/10 p-4 backdrop-blur-sm">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-sm font-bold text-[#0066B3]">
                            {{ initials }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-white">{{ user?.name ?? 'Utilisateur' }}</p>
                            <p class="truncate text-xs text-white/70">{{ roles[0] ?? 'Compte actif' }}</p>
                        </div>
                    </div>

                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="mt-4 inline-flex w-full items-center justify-center rounded-2xl border border-white/15 bg-white/10 px-4 py-3 text-sm font-semibold text-white transition hover:bg-white/15"
                    >
                        Deconnexion
                    </Link>
                </div>
            </div>
        </aside>

        <div class="lg:pl-[260px]">
            <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/90 shadow-sm backdrop-blur-md">
                <div class="mx-auto flex max-w-[1600px] items-center justify-between gap-4 px-4 py-4 sm:px-6">
                    <div class="flex min-w-0 items-center gap-3">
                        <button
                            type="button"
                            class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:border-[#0066B3]/30 hover:text-[#0066B3] lg:hidden"
                            @click="isMobileOpen = true"
                        >
                            ☰
                        </button>

                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
                                <span v-for="(crumb, index) in breadcrumbs" :key="`${crumb}-${index}`" class="inline-flex items-center gap-2">
                                    <span>{{ crumb }}</span>
                                    <span v-if="index < breadcrumbs.length - 1" class="text-slate-300">/</span>
                                </span>
                            </div>
                            <div v-if="$slots.header" class="mt-1">
                                <slot name="header" />
                            </div>
                            <h1 v-else class="font-['Outfit'] text-2xl font-bold text-slate-900">{{ currentLabel }}</h1>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            class="relative inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-[#0066B3]/30 hover:text-[#0066B3]"
                        >
                            Notifications
                            <span class="absolute right-2 top-2 h-2.5 w-2.5 rounded-full bg-[#FF9800]" />
                        </button>

                        <div class="relative">
                            <button
                                type="button"
                                class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-2 shadow-sm transition hover:border-[#0066B3]/30"
                                @click="showUserMenu = !showUserMenu"
                            >
                                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-[#0066B3] to-[#00A651] text-sm font-bold text-white">
                                    {{ initials }}
                                </div>
                                <div class="hidden text-left sm:block">
                                    <p class="max-w-40 truncate text-sm font-semibold text-slate-900">{{ user?.name ?? 'Utilisateur' }}</p>
                                    <p class="text-xs text-slate-500">{{ roles[0] ?? 'Compte' }}</p>
                                </div>
                            </button>

                            <div
                                v-if="showUserMenu"
                                class="absolute right-0 top-[calc(100%+0.5rem)] z-50 w-60 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl"
                            >
                                <Link
                                    :href="route('profile.edit')"
                                    class="flex items-center rounded-xl px-4 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50 hover:text-[#0066B3]"
                                    @click="showUserMenu = false"
                                >
                                    Profil
                                </Link>
                                <Link
                                    :href="route('logout')"
                                    method="post"
                                    as="button"
                                    class="flex w-full items-center rounded-xl px-4 py-3 text-left text-sm font-medium text-red-600 transition hover:bg-red-50"
                                    @click="showUserMenu = false"
                                >
                                    Deconnexion
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="mx-auto max-w-[1600px] px-4 py-6 sm:px-6">
                <slot />
            </main>
        </div>
    </div>
</template>