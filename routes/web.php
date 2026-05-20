<?php

use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AnnuaireController;
use App\Http\Controllers\BenevoleController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CommunicationController;
use App\Http\Controllers\CompetitionController;
use App\Http\Controllers\CrmController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DotationController;
use App\Http\Controllers\EnqueteController;
use App\Http\Controllers\EvenementController;
use App\Http\Controllers\IntervenantController;
use App\Http\Controllers\InscriptionController;
use App\Http\Controllers\LogistiqueController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaiementController;
use App\Http\Controllers\ParticipantController;
use App\Http\Controllers\PresenceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RapportController;
use App\Http\Controllers\RessourceController;
use App\Http\Controllers\SatisfactionController;
use App\Http\Controllers\TarifController;
use App\Http\Controllers\TacheController;
use Illuminate\Support\Facades\Route;


//   ROUTES PUBLIQUES


Route::get('/', fn() => redirect()->route('evenements.index'))->name('home');

Route::get('/evenements', [EvenementController::class, 'index'])->name('evenements.index');
Route::get('/evenements/{evenement}', [EvenementController::class, 'show'])
     ->where('evenement', '[0-9]+')
     ->name('evenements.show');

Route::post('paiements/callback', [PaiementController::class, 'callback'])->name('paiements.callback');


Route::middleware('auth')->group(function () {

     Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

     //  Evenements 
     Route::resource('evenements', EvenementController::class)->except(['index', 'show']);
     Route::get('evenements/{evenement}/inscrire', [InscriptionController::class, 'create'])->name('evenements.inscrire');
     Route::get('evenements/{evenement}/export-paricipants', [InscriptionController::class, 'export'])->name('evenements.export-participants');
     Route::patch('evenements/{evenement}/statut', [EvenementController::class, 'updateStatut'])->name('evenements.updateStatut');


     // Workflow validation événements
     Route::post('/evenements/{evenement}/demander-validation', [\App\Http\Controllers\EvenementController::class, 'demanderValidation'])
          ->name('evenements.demander-validation');
     Route::post('/evenements/{evenement}/valider', [\App\Http\Controllers\EvenementController::class, 'valider'])
          ->name('evenements.valider');
     Route::post('/evenements/{evenement}/rejeter', [\App\Http\Controllers\EvenementController::class, 'rejeter'])
          ->name('evenements.rejeter');

     // Vue staff
     Route::get('/inscriptions', [\App\Http\Controllers\InscriptionController::class, 'index'])
          ->name('inscriptions.index');

     // Vue participant : ses inscriptions
     Route::get('/mes-inscriptions', [\App\Http\Controllers\InscriptionController::class, 'mesInscriptions'])
          ->name('mes-inscriptions.index');

     // Détail
     Route::get('/inscriptions/{inscription}', [\App\Http\Controllers\InscriptionController::class, 'show'])
          ->name('inscriptions.show')
          ->where('inscription', '[0-9]+');

     // NIVEAU 1 : Pré-inscription
     Route::get('/evenements/{evenement}/preinscrire', [\App\Http\Controllers\InscriptionController::class, 'create'])
          ->name('inscriptions.create')
          ->where('evenement', '[0-9]+');
     Route::post('/inscriptions', [\App\Http\Controllers\InscriptionController::class, 'store'])
          ->name('inscriptions.store');

     // NIVEAU 2 : Dossier complet
     Route::get('/inscriptions/{inscription}/dossier-complet', [\App\Http\Controllers\InscriptionController::class, 'dossierComplet'])
          ->name('inscriptions.dossier-complet')
          ->where('inscription', '[0-9]+');
     Route::post('/inscriptions/{inscription}/dossier-complet', [\App\Http\Controllers\InscriptionController::class, 'soumettreDossier'])
          ->name('inscriptions.soumettre-dossier')
          ->where('inscription', '[0-9]+');

     // ACTIONS STAFF
     Route::post('/inscriptions/{inscription}/preselectionner', [\App\Http\Controllers\InscriptionController::class, 'preselectionner'])
          ->name('inscriptions.preselectionner')
          ->where('inscription', '[0-9]+');
     Route::post('/inscriptions/{inscription}/recommander', [\App\Http\Controllers\InscriptionController::class, 'recommander'])
          ->name('inscriptions.recommander')
          ->where('inscription', '[0-9]+');
     Route::post('/inscriptions/{inscription}/valider', [\App\Http\Controllers\InscriptionController::class, 'valider'])
          ->name('inscriptions.valider')
          ->where('inscription', '[0-9]+');
     Route::post('/inscriptions/{inscription}/refuser', [\App\Http\Controllers\InscriptionController::class, 'refuser'])
          ->name('inscriptions.refuser')
          ->where('inscription', '[0-9]+');

     // ACTION PARTICIPANT : Annuler
     Route::post('/inscriptions/{inscription}/annuler', [\App\Http\Controllers\InscriptionController::class, 'annuler'])
          ->name('inscriptions.annuler')
          ->where('inscription', '[0-9]+');

     // -- Annuaire --
     Route::get('/annuaire', [AnnuaireController::class, 'index'])->name('annuaire.index');
     Route::get('/annuaire/{user}', [AnnuaireController::class, 'show'])->name('annuaire.show');

     Route::get('/vivier/benevoles', [\App\Http\Controllers\VivierController::class, 'benevoles'])
          ->name('vivier.benevoles.index');
     Route::post('/vivier/benevoles', [\App\Http\Controllers\VivierController::class, 'storeBenevole'])
          ->name('vivier.benevoles.store');
     Route::delete('/vivier/benevoles/{benevole}', [\App\Http\Controllers\VivierController::class, 'destroyBenevole'])
          ->name('vivier.benevoles.destroy');

     Route::get('/vivier/intervenants', [\App\Http\Controllers\VivierController::class, 'intervenants'])
          ->name('vivier.intervenants.index');
     Route::post('/vivier/intervenants', [\App\Http\Controllers\VivierController::class, 'storeIntervenant'])
          ->name('vivier.intervenants.store');
     Route::delete('/vivier/intervenants/{intervenant}', [\App\Http\Controllers\VivierController::class, 'destroyIntervenant'])
          ->name('vivier.intervenants.destroy');

     // -- Paiements --
     Route::post('inscriptions/{inscription}/paiement', [PaiementController::class, 'initier'])->name('paiements.initier');
     Route::get('paiements/{paiement}', [PaiementController::class, 'show'])->name('paiements.show');

     // -- Participants --
     Route::resource('participants', ParticipantController::class)->only(['index', 'show']);
     Route::post('participants/import', [ParticipantController::class, 'import'])->name('participants.import');

     // -- Tarifs --
     Route::resource('evenements.tarifs', TarifController::class)->shallow()->except(['index', 'show', 'create', 'edit']);

     // -- Presences --
     Route::post('presences/scan', [PresenceController::class, 'scan'])->name('presences.scan');
     Route::get('evenements/{evenement}/presences', [PresenceController::class, 'index'])->name('evenements.presences');

     // -- Taches --
     Route::resource('evenements.taches', TacheController::class)->shallow();
     Route::patch('taches/{tache}/statut', [TacheController::class, 'updateStatut'])->name('taches.updateStatut');

     // -- Budget --
     Route::get('evenements/{evenement}/budget', [BudgetController::class, 'show'])->name('evenements.budget.show');
     Route::post('budgets/{budget}/lignes', [BudgetController::class, 'storeLigne'])->name('budgets.lignes.store');
     Route::patch('lignes-budget/{ligneBudget}', [BudgetController::class, 'updateLigne'])->name('lignes-budget.update');
     Route::delete('lignes-budget/{ligneBudget}', [BudgetController::class, 'destroyLigne'])->name('lignes-budget.destroy');

     // -- Logistique (ancien) --
     Route::prefix('evenements/{evenement}/logistique')->name('logistique.')->group(function () {
          Route::get('ressources', [RessourceController::class, 'index'])->name('ressources.index');
          Route::post('ressources', [RessourceController::class, 'store'])->name('ressources.store');
          Route::put('ressources/{ressource}', [RessourceController::class, 'update'])->name('ressources.update');
          Route::delete('ressources/{ressource}', [RessourceController::class, 'destroy'])->name('ressources.destroy');
          Route::post('ressources/check-availability', [RessourceController::class, 'checkAvailability'])->name('ressources.check');

          Route::get('intervenants', [IntervenantController::class, 'index'])->name('intervenants.index');
          Route::post('intervenants/assign', [IntervenantController::class, 'assignToSession'])->name('intervenants.assign');
          Route::delete('intervenants/{intervenantSession}', [IntervenantController::class, 'removeFromSession'])->name('intervenants.remove');
          Route::get('intervenants/perdiems', [IntervenantController::class, 'perdiems'])->name('intervenants.perdiems');

          Route::get('benevoles', [BenevoleController::class, 'index'])->name('benevoles.index');
          Route::post('benevoles', [BenevoleController::class, 'store'])->name('benevoles.store');
          Route::put('benevoles/{benevoleAffectation}', [BenevoleController::class, 'update'])->name('benevoles.update');
          Route::delete('benevoles/{benevoleAffectation}', [BenevoleController::class, 'destroy'])->name('benevoles.destroy');
          Route::get('benevoles/planning', [BenevoleController::class, 'planning'])->name('benevoles.planning');

          Route::get('dotations', [DotationController::class, 'index'])->name('dotations.index');
          Route::post('dotations', [DotationController::class, 'store'])->name('dotations.store');
          Route::patch('dotations/{dotation}/return', [DotationController::class, 'returnItem'])->name('dotations.return');
          Route::get('dotations/tracking', [DotationController::class, 'tracking'])->name('dotations.tracking');
     });

     // -- Logistique V2 (nouveau) --
     Route::prefix('evenements/{evenement}/logistique-v2')->name('logistique-v2.')->group(function () {
          Route::get('/', [LogistiqueController::class, 'index'])->name('index');
          Route::post('/ressources', [LogistiqueController::class, 'storeRessource'])->name('ressources.store');
          Route::delete('/ressources/{ressourceId}', [LogistiqueController::class, 'destroyRessource'])->name('ressources.destroy');
          Route::post('/dotations', [LogistiqueController::class, 'storeDotation'])->name('dotations.store');
          Route::patch('/dotations/{dotationId}/return', [LogistiqueController::class, 'returnDotation'])->name('dotations.return');
          Route::delete('/dotations/{dotationId}', [LogistiqueController::class, 'destroyDotation'])->name('dotations.destroy');
          Route::post('/benevoles', [LogistiqueController::class, 'storeBenevole'])->name('benevoles.store');
          Route::delete('/benevoles/{benevole}', [LogistiqueController::class, 'destroyBenevole'])->name('benevoles.destroy');
     });

     // Module Competitions 
     Route::prefix('evenements/{evenement}/competition')->name('competition.')->group(function () {
          Route::get('/', [CompetitionController::class, 'index'])->name('index');
          Route::post('/equipes', [CompetitionController::class, 'storeEquipe'])->name('equipes.store');
          Route::delete('/equipes/{equipe}', [CompetitionController::class, 'destroyEquipe'])->name('equipes.destroy');
          Route::post('/phases', [CompetitionController::class, 'storePhase'])->name('phases.store');
          Route::delete('/phases/{phase}', [CompetitionController::class, 'destroyPhase'])->name('phases.destroy');
          Route::post('/phases/{phase}/rencontres', [CompetitionController::class, 'storeRencontre'])->name('rencontres.store');
          Route::patch('/rencontres/{rencontre}/score', [CompetitionController::class, 'updateScore'])->name('rencontres.score');
          Route::delete('/rencontres/{rencontre}', [CompetitionController::class, 'destroyRencontre'])->name('rencontres.destroy');
     });

     // -- Communication --
     Route::prefix('evenements/{evenement}/communication')->name('communication.')->group(function () {
          Route::get('campaigns', [CommunicationController::class, 'campaigns'])->name('campaigns.index');
          Route::get('campaigns/create', [CommunicationController::class, 'createCampaign'])->name('campaigns.create');
          Route::post('campaigns/send', [CommunicationController::class, 'sendCampaign'])->name('campaigns.send');
          Route::get('campaigns/{campaign}', [CommunicationController::class, 'showCampaign'])->name('campaigns.show');
          Route::post('campaigns/{campaign}/send', [CommunicationController::class, 'sendExistingCampaign'])->name('campaigns.send-existing');
          Route::post('campaigns/preview', [CommunicationController::class, 'previewCampaign'])->name('campaigns.preview');

          Route::get('enquetes', [EnqueteController::class, 'index'])->name('enquetes.index');
          Route::get('enquetes/create', [EnqueteController::class, 'create'])->name('enquetes.create');
          Route::post('enquetes', [EnqueteController::class, 'store'])->name('enquetes.store');
          Route::get('enquetes/{enquete}', [EnqueteController::class, 'show'])->name('enquetes.show');
          Route::post('enquetes/{enquete}/respond', [EnqueteController::class, 'respond'])->name('enquetes.respond');
          Route::patch('enquetes/{enquete}/publish', [EnqueteController::class, 'publish'])->name('enquetes.publish');
          Route::patch('enquetes/{enquete}/close', [EnqueteController::class, 'close'])->name('enquetes.close');

          Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
          Route::post('documents', [DocumentController::class, 'store'])->name('documents.store');
          Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
          Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
     });

     // -- Notifications --
     Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
     Route::post('notifications/send', [NotificationController::class, 'send'])->name('notifications.send');
     Route::get('notifications/settings', [NotificationController::class, 'settings'])->name('notifications.settings');
     Route::get('communication/templates', [CommunicationController::class, 'templates'])->name('communication.templates');

     // -- Admin --
     Route::prefix('admin')->name('admin.')->group(function () {
          Route::resource('users', AdminUserController::class);
          Route::post('users/{id}/restore', [AdminUserController::class, 'restore'])
               ->withTrashed()
               ->name('users.restore');
          Route::patch('users/{user}/toggle-active', [AdminUserController::class, 'toggleActive'])->name('users.toggle');
          Route::post('users/{user}/debloquer', [AdminUserController::class, 'debloquer'])->name('users.debloquer');
          Route::post('users/{user}/reset-password', [AdminUserController::class, 'resetPassword'])->name('users.reset-password');

          Route::resource('roles', RoleController::class)->except(['show']);

          Route::get('audit', [\App\Http\Controllers\Admin\AuditController::class, 'index'])->name('audit.index');
          Route::get('audit/export', [\App\Http\Controllers\Admin\AuditController::class, 'export'])->name('audit.export');
          Route::get('audit/{activity}', [\App\Http\Controllers\Admin\AuditController::class, 'show'])->name('audit.show');

          Route::get('security', [\App\Http\Controllers\Admin\SecurityController::class, 'index'])
               ->name('security.index');
          Route::post('security/{user}/debloquer', [\App\Http\Controllers\Admin\SecurityController::class, 'debloquer'])
               ->name('security.debloquer');

          Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
          Route::post('settings/general',       [SettingsController::class, 'updateGeneral'])->name('settings.general');
          Route::post('settings/security',      [SettingsController::class, 'updateSecurity'])->name('settings.security');
          Route::post('settings/notifications', [SettingsController::class, 'updateNotifications'])->name('settings.notifications');
     });

     // -- CRM --
     Route::prefix('crm')->name('crm.')->group(function () {
          Route::get('contacts', [CrmController::class, 'contacts'])->name('contacts.index');
          Route::get('contacts/{user}', [CrmController::class, 'showContact'])->name('contacts.show');
          Route::get('followups', [CrmController::class, 'followups'])->name('followups.index');
          Route::post('followups', [CrmController::class, 'createFollowup'])->name('followups.store');
          Route::patch('followups/{followup}', [CrmController::class, 'updateFollowup'])->name('followups.update');
          Route::get('evenements/{evenement}/b2b', [CrmController::class, 'b2bMeetings'])->name('b2b.index');
          Route::post('evenements/{evenement}/b2b', [CrmController::class, 'createMeeting'])->name('b2b.store');
          Route::get('loyalty', [CrmController::class, 'loyalty'])->name('loyalty.index');
          Route::post('loyalty/invite', [CrmController::class, 'sendPriorityInvitation'])->name('loyalty.invite');
          Route::post('sync-moov', [CrmController::class, 'syncMoov'])->name('sync');
          Route::post('thank-you/{evenement}', [CrmController::class, 'sendThankYou'])->name('thank-you');
     });

     // -- Rapports --
     Route::prefix('rapports')->name('rapports.')->group(function () {
          Route::get('/', [RapportController::class, 'index'])->name('index');
          Route::get('evenements/{evenement}/participation', [RapportController::class, 'participation'])->name('participation');
          Route::get('evenements/{evenement}/financier', [RapportController::class, 'financier'])->name('financier');
          Route::get('evenements/{evenement}/rse', [RapportController::class, 'rse'])->name('rse');
          Route::get('evenements/{evenement}/export', [RapportController::class, 'export'])->name('export');
          Route::get('evenements/{evenement}/satisfaction', [SatisfactionController::class, 'index'])->name('satisfaction');
          Route::get('enquetes/{enquete}/analyse', [SatisfactionController::class, 'analyse'])->name('satisfaction.analyse');
     });


     Route::get('/rapports', [\App\Http\Controllers\RapportController::class, 'index'])
          ->name('rapports.index');
     Route::get('/rapports/export-global', [\App\Http\Controllers\RapportController::class, 'exportGlobal'])
          ->name('rapports.export-global');



     Route::get('/analyses', [\App\Http\Controllers\AnalyseController::class, 'dashboardGlobal'])
          ->name('analyses.global');

     Route::get('/evenements/{evenement}/dashboard', [\App\Http\Controllers\AnalyseController::class, 'dashboardEvenement'])
          ->name('evenements.dashboard')
          ->where('evenement', '[0-9]+');

     Route::get('/rapport-rse', [\App\Http\Controllers\AnalyseController::class, 'rapportRse'])
          ->name('analyses.rapport-rse');

     // -- Profil --
     Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
     Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
     Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

     // ──── MATÉRIEL : Catalogue global ────
     Route::resource('materiels', \App\Http\Controllers\MaterielController::class)
          ->where(['materiel' => '[0-9]+']);

     // ──── PRESTATAIRES : Annuaire global ────
     Route::resource('prestataires', \App\Http\Controllers\PrestataireController::class)
          ->where(['prestataire' => '[0-9]+']);

     // ──── LIEUX : Catalogue global ────
     Route::resource('lieux', \App\Http\Controllers\LieuController::class)
          ->where(['lieu' => '[0-9]+']);

     // ──── LOGISTIQUE par événement ────
     Route::get('/evenements/{evenement}/logistique', [\App\Http\Controllers\LogistiqueController::class, 'show'])
          ->name('evenements.logistique')
          ->where('evenement', '[0-9]+');

     // ──── Matériel - actions ────
     Route::post('/evenements/{evenement}/logistique/materiel', [\App\Http\Controllers\LogistiqueController::class, 'affecterMateriel'])
          ->name('evenements.logistique.materiel.affecter')
          ->where('evenement', '[0-9]+');
     Route::delete('/evenements/{evenement}/logistique/materiel/{materiel}', [\App\Http\Controllers\LogistiqueController::class, 'detacherMateriel'])
          ->name('evenements.logistique.materiel.detacher')
          ->where(['evenement' => '[0-9]+', 'materiel' => '[0-9]+']);
     Route::patch('/evenements/{evenement}/logistique/materiel/{materiel}', [\App\Http\Controllers\LogistiqueController::class, 'updateStatutMateriel'])
          ->name('evenements.logistique.materiel.statut')
          ->where(['evenement' => '[0-9]+', 'materiel' => '[0-9]+']);

     // ──── Prestataires - actions ────
     Route::post('/evenements/{evenement}/logistique/prestataire', [\App\Http\Controllers\LogistiqueController::class, 'affecterPrestataire'])
          ->name('evenements.logistique.prestataire.affecter')
          ->where('evenement', '[0-9]+');
     Route::delete('/evenements/{evenement}/logistique/prestataire/{prestataire}', [\App\Http\Controllers\LogistiqueController::class, 'detacherPrestataire'])
          ->name('evenements.logistique.prestataire.detacher')
          ->where(['evenement' => '[0-9]+', 'prestataire' => '[0-9]+']);
     Route::patch('/evenements/{evenement}/logistique/prestataire/{prestataire}', [\App\Http\Controllers\LogistiqueController::class, 'updateStatutPrestataire'])
          ->name('evenements.logistique.prestataire.statut')
          ->where(['evenement' => '[0-9]+', 'prestataire' => '[0-9]+']);

     // ──── Postes bénévoles - gestion (staff) ────
     Route::post('/evenements/{evenement}/logistique/poste', [\App\Http\Controllers\LogistiqueController::class, 'creerPosteBenevole'])
          ->name('evenements.logistique.poste.creer')
          ->where('evenement', '[0-9]+');
     Route::patch('/postes-benevoles/{poste}', [\App\Http\Controllers\LogistiqueController::class, 'updatePosteBenevole'])
          ->name('postes-benevoles.update')
          ->where('poste', '[0-9]+');
     Route::delete('/postes-benevoles/{poste}', [\App\Http\Controllers\LogistiqueController::class, 'supprimerPosteBenevole'])
          ->name('postes-benevoles.destroy')
          ->where('poste', '[0-9]+');

     // ──── Bénévolat : Côté Participant ────
     Route::post('/postes-benevoles/{poste}/candidater', [\App\Http\Controllers\BenevoleController::class, 'candidater'])
          ->name('postes-benevoles.candidater')
          ->where('poste', '[0-9]+');
     Route::get('/mes-candidatures-benevolat', [\App\Http\Controllers\BenevoleController::class, 'mesCandidatures'])
          ->name('benevolat.mes-candidatures');
     Route::post('/candidatures-benevoles/{candidature}/annuler', [\App\Http\Controllers\BenevoleController::class, 'annulerCandidature'])
          ->name('candidatures-benevoles.annuler')
          ->where('candidature', '[0-9]+');

     // ──── Bénévolat : Côté Staff ────
     Route::get('/postes-benevoles/{poste}/candidatures', [\App\Http\Controllers\BenevoleController::class, 'listeCandidatures'])
          ->name('postes-benevoles.candidatures')
          ->where('poste', '[0-9]+');
     Route::post('/candidatures-benevoles/{candidature}/accepter', [\App\Http\Controllers\BenevoleController::class, 'accepter'])
          ->name('candidatures-benevoles.accepter')
          ->where('candidature', '[0-9]+');
     Route::post('/candidatures-benevoles/{candidature}/refuser', [\App\Http\Controllers\BenevoleController::class, 'refuser'])
          ->name('candidatures-benevoles.refuser')
          ->where('candidature', '[0-9]+');

     // ──── VUE GÉNÉRALE LOGISTIQUE ────
     Route::get('/logistique', [\App\Http\Controllers\LogistiqueGeneraleController::class, 'index'])
          ->name('logistique.index');



     Route::get(
          'evenements/{evenement}/certificats',
          [\App\Http\Controllers\CertificatController::class, 'index']
     )
          ->name('certificats.index');
     Route::post(
          'evenements/{evenement}/certificats/generer',
          [\App\Http\Controllers\CertificatController::class, 'generer']
     )
          ->name('certificats.generer');
     Route::get(
          'certificats/{certificat}/telecharger',
          [\App\Http\Controllers\CertificatController::class, 'telecharger']
     )
          ->name('certificats.telecharger');
     Route::post(
          'certificats/{certificat}/envoyer-email',
          [\App\Http\Controllers\CertificatController::class, 'envoyerEmail']
     )
          ->name('certificats.envoyer-email');

     // Participant
     Route::get(
          'mes-certificats',
          [\App\Http\Controllers\CertificatController::class, 'mesCertificats']
     )
          ->name('mes-certificats.index');
     Route::get(
          'mes-certificats/{certificat}/telecharger',
          [\App\Http\Controllers\CertificatController::class, 'telechargerMien']
     )
          ->name('mes-certificats.telecharger');
});

require __DIR__ . '/auth.php';
