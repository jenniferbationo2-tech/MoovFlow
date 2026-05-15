<script setup>
import { ref, computed } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'

const props = defineProps({
    evenement: Object,
    budget: Object,
})

// ══════════════════════════════════════
//  USER & ROLES (déclarés EN PREMIER)
// ══════════════════════════════════════
const page = usePage()
const user = computed(() => page.props.auth?.user ?? null)
const userId = computed(() => page.props.auth?.user?.id)
const userRoles = computed(() => page.props.auth?.user?.roles?.map(r => r.name) ?? [])
const roles = computed(() => user.value?.roles ?? [])

const estStaff = computed(() =>
    roles.value.some(r => ['admin', 'responsable_dcirp', 'organisateur'].includes(r))
)
const Layout = computed(() => estStaff.value ? DashboardLayout : PublicLayout)

const estOrganisateur = computed(() =>
    roles.value.some(r => ['organisateur', 'admin', 'responsable_dcirp'].includes(r))
)
const peutSInscrire = computed(() =>
    !user.value || roles.value.includes('participant') || roles.value.length === 0
)

// ══════════════════════════════════════
//  PERMISSIONS WORKFLOW
// ══════════════════════════════════════
const estAdmin = computed(() => userRoles.value.includes('admin'))
const estResponsable = computed(() =>
    userRoles.value.includes('responsable_dcirp') || userRoles.value.includes('admin')
)
const estCreateur = computed(() => props.evenement?.created_by === userId.value)

// ══════════════════════════════════════
//  WORKFLOW ACTIONS
// ══════════════════════════════════════
const demanderValidation = () => {
    if (confirm('Envoyer cet événement en validation au responsable dCIRP ?')) {
        router.post(`/evenements/${props.evenement.id}/demander-validation`, {}, {
            preserveScroll: true,
        })
    }
}

const valider = () => {
    if (confirm('Valider et publier cet événement ?')) {
        router.post(`/evenements/${props.evenement.id}/valider`, {}, {
            preserveScroll: true,
        })
    }
}

const modalRejetOuvert = ref(false)
const motifRejet = ref('')

const ouvrirModalRejet = () => {
    motifRejet.value = ''
    modalRejetOuvert.value = true
}

const confirmerRejet = () => {
    if (motifRejet.value.trim().length < 10) {
        alert('Le motif doit contenir au moins 10 caractères')
        return
    }
    router.post(`/evenements/${props.evenement.id}/rejeter`, {
        motif_rejet: motifRejet.value,
    }, {
        onSuccess: () => modalRejetOuvert.value = false,
        preserveScroll: true,
    })
}

const supprimer = () => {
    if (confirm('Archiver cet événement ?')) {
        router.delete(`/evenements/${props.evenement.id}`)
    }
}

// ══════════════════════════════════════
//  HELPERS UI
// ══════════════════════════════════════
const typeCode = computed(() => props.evenement.type_evenement?.code)

const couleurType = (code) => ({
    BARA_MOUSSO: { bg: 'bg-amber-50', text: 'text-amber-700', accent: 'bg-amber-600' },
    CONF: { bg: 'bg-rose-50', text: 'text-rose-700', accent: 'bg-rose-600' },
    SPORT: { bg: 'bg-blue-50', text: 'text-blue-700', accent: 'bg-blue-600' },
    CHALLENGE: { bg: 'bg-violet-50', text: 'text-violet-700', accent: 'bg-violet-600' },
    FORMATION: { bg: 'bg-emerald-50', text: 'text-emerald-700', accent: 'bg-emerald-600' },
    HACK: { bg: 'bg-orange-50', text: 'text-orange-700', accent: 'bg-orange-600' },
    SALON: { bg: 'bg-indigo-50', text: 'text-indigo-700', accent: 'bg-indigo-600' },
}[code] || { bg: 'bg-slate-50', text: 'text-slate-700', accent: 'bg-slate-600' })

const couleurStatut = (statut) => ({
    publie: { bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500', label: 'Publié' },
    en_validation: { bg: 'bg-amber-50', text: 'text-amber-700', dot: 'bg-amber-500', label: 'En validation' },
    en_cours: { bg: 'bg-blue-50', text: 'text-blue-700', dot: 'bg-blue-500', label: 'En cours' },
    termine: { bg: 'bg-slate-100', text: 'text-slate-600', dot: 'bg-slate-400', label: 'Terminé' },
    brouillon: { bg: 'bg-amber-50', text: 'text-amber-700', dot: 'bg-amber-500', label: 'Brouillon' },
    annule: { bg: 'bg-red-50', text: 'text-red-700', dot: 'bg-red-500', label: 'Annulé' },
}[statut] || { bg: 'bg-slate-100', text: 'text-slate-600', dot: 'bg-slate-400', label: statut })

const labelType = (code) => ({
    BARA_MOUSSO: 'Concours Bara Mousso',
    CONF: 'Conférence',
    SPORT: 'Tournoi sportif',
    CHALLENGE: 'Challenge Innovation',
    FORMATION: 'Formation numérique',
    HACK: 'Hackathon',
    SALON: 'Salon professionnel',
}[code] || 'Événement')

const formaterDate = (d) => {
    if (!d) return '—'
    return new Date(d).toLocaleDateString('fr-FR', {
        day: '2-digit', month: 'long', year: 'numeric'
    })
}

const formaterHeure = (d) => {
    if (!d) return ''
    return new Date(d).toLocaleTimeString('fr-FR', {
        hour: '2-digit', minute: '2-digit'
    })
}

const lienInscription = computed(() => {
    if (!user.value) return '/login'
    return `/evenements/${props.evenement.id}/inscrire`
})
</script>