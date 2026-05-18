<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditController extends Controller
{
    /**
     * Page de consultation du journal d'activité.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('view-audit');

        // ─── REQUÊTE PRINCIPALE AVEC FILTRES ───
        $query = Activity::query()
            ->with(['causer', 'subject'])
            ->latest();

        // Filtre : type de log (catégorie)
        if ($request->filled('log_name')) {
            $query->where('log_name', $request->log_name);
        }

        // Filtre : événement (created / updated / deleted)
        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        // Filtre : par utilisateur (causer)
        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->causer_id);
        }

        // Filtre : période
        if ($request->filled('debut')) {
            $query->whereDate('created_at', '>=', $request->debut);
        }
        if ($request->filled('fin')) {
            $query->whereDate('created_at', '<=', $request->fin);
        }

        // Recherche texte (description)
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($sub) use ($q) {
                $sub->where('description', 'like', "%{$q}%")
                    ->orWhere('subject_type', 'like', "%{$q}%");
            });
        }

        // Filtre : seulement les actions importantes (pas les "request" GET)
        if ($request->boolean('important_only', true)) {
            $query->where(function ($sub) {
                $sub->whereIn('log_name', ['user', 'evenement', 'inscription', 'paiement'])
                    ->orWhere(function ($q) {
                        // Garder les requêtes POST/PUT/DELETE en log_name "request"
                        $q->where('log_name', 'request')
                          ->whereIn('event', ['post', 'put', 'patch', 'delete']);
                    });
            });
        }

        $activities = $query->paginate(20)->through(fn (Activity $a) => $this->formatActivity($a));

        // ─── STATISTIQUES ───
        $stats = [
            'total'         => Activity::count(),
            'aujourdhui'    => Activity::whereDate('created_at', today())->count(),
            'cette_semaine' => Activity::where('created_at', '>=', now()->startOfWeek())->count(),
            'causers_actifs'=> Activity::whereDate('created_at', '>=', now()->subDays(7))
                                       ->whereNotNull('causer_id')
                                       ->distinct('causer_id')
                                       ->count('causer_id'),
        ];

        // ─── LISTES POUR FILTRES ───
        $logNamesDisponibles = Activity::distinct()
                                       ->pluck('log_name')
                                       ->filter()
                                       ->values();

        $eventsDisponibles = Activity::distinct()
                                     ->pluck('event')
                                     ->filter()
                                     ->values();

        $causersDisponibles = User::whereIn('id',
                                    Activity::whereNotNull('causer_id')
                                            ->distinct()
                                            ->pluck('causer_id')
                                  )
                                  ->select('id', 'nom', 'prenom', 'email')
                                  ->orderBy('nom')
                                  ->get();

        return Inertia::render('Admin/Audit/Index', [
            'activities'          => $activities,
            'stats'               => $stats,
            'logNamesDisponibles' => $logNamesDisponibles,
            'eventsDisponibles'   => $eventsDisponibles,
            'causersDisponibles'  => $causersDisponibles,
            'filters'             => $request->only([
                'log_name', 'event', 'causer_id', 'debut', 'fin', 'search', 'important_only',
            ]),
        ]);
    }

    /**
     * Détail d'une activité (utilisé par la modale).
     */
    public function show(Activity $activity)
    {
        Gate::authorize('view-audit');

        $activity->load(['causer', 'subject']);

        return response()->json([
            'activity' => $this->formatActivity($activity, true),
        ]);
    }

    /**
     * Export CSV des activités filtrées.
     */
    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('view-audit');

        $query = Activity::query()
            ->with(['causer', 'subject'])
            ->latest();

        // Appliquer les mêmes filtres que index()
        if ($request->filled('log_name')) {
            $query->where('log_name', $request->log_name);
        }
        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }
        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->causer_id);
        }
        if ($request->filled('debut')) {
            $query->whereDate('created_at', '>=', $request->debut);
        }
        if ($request->filled('fin')) {
            $query->whereDate('created_at', '<=', $request->fin);
        }

        $filename = 'audit_trail_' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // BOM UTF-8 pour Excel
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // En-têtes
            fputcsv($handle, [
                'ID', 'Date/Heure', 'Type', 'Événement',
                'Description', 'Auteur', 'Sujet', 'ID Sujet', 'IP',
            ], ';');

            // Lignes
            $query->chunk(500, function ($activities) use ($handle) {
                foreach ($activities as $a) {
                    $causer = $a->causer
                        ? "{$a->causer->prenom} {$a->causer->nom}"
                        : 'Système';

                    $subject = $a->subject_type
                        ? class_basename($a->subject_type)
                        : '—';

                    $ip = $a->properties['ip'] ?? '—';

                    fputcsv($handle, [
                        $a->id,
                        $a->created_at->format('Y-m-d H:i:s'),
                        $a->log_name,
                        $a->event,
                        $a->description,
                        $causer,
                        $subject,
                        $a->subject_id ?? '—',
                        $ip,
                    ], ';');
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    // ═════════════════════════════════════════════════════
    //   HELPER : Formater une activité pour le frontend
    // ═════════════════════════════════════════════════════

    private function formatActivity(Activity $a, bool $detailed = false): array
    {
        $causer = null;
        if ($a->causer) {
            $causer = [
                'id'     => $a->causer->id,
                'nom'    => $a->causer->nom ?? null,
                'prenom' => $a->causer->prenom ?? null,
                'email'  => $a->causer->email ?? null,
            ];
        }

        $subject = null;
        if ($a->subject) {
            $subject = [
                'id'    => $a->subject->id,
                'type'  => class_basename($a->subject_type),
                'label' => $this->getSubjectLabel($a->subject),
            ];
        }

        $data = [
            'id'          => $a->id,
            'log_name'    => $a->log_name,
            'event'       => $a->event,
            'description' => $a->description,
            'subject'     => $subject,
            'subject_type'=> $a->subject_type ? class_basename($a->subject_type) : null,
            'subject_id'  => $a->subject_id,
            'causer'      => $causer,
            'created_at'  => $a->created_at->toIso8601String(),
            'created_at_human' => $a->created_at->locale('fr')->diffForHumans(),
        ];

        // Détails complets (pour la modale)
        if ($detailed) {
            $data['properties'] = $a->properties->toArray();
            $data['changes'] = $this->extractChanges($a);
        } else {
            // En liste, on ajoute juste un résumé des changements
            $data['has_changes'] = !empty($a->properties['attributes'] ?? null);
            $data['ip'] = $a->properties['ip'] ?? null;
        }

        return $data;
    }

    /**
     * Trouve un libellé lisible pour un sujet.
     */
    private function getSubjectLabel($subject): string
    {
        if (!$subject) return '—';

        if (isset($subject->titre)) return $subject->titre;
        if (isset($subject->nom) && isset($subject->prenom)) {
            return $subject->prenom . ' ' . $subject->nom;
        }
        if (isset($subject->nom)) return $subject->nom;
        if (isset($subject->email)) return $subject->email;
        if (isset($subject->reference)) return $subject->reference;

        return 'ID #' . $subject->id;
    }

    /**
     * Extrait les changements (old → new) d'une activité.
     */
    private function extractChanges(Activity $a): array
    {
        $changes = [];
        $attributes = $a->properties['attributes'] ?? [];
        $old = $a->properties['old'] ?? [];

        foreach ($attributes as $key => $newValue) {
            $oldValue = $old[$key] ?? null;
            $changes[] = [
                'field' => $key,
                'old'   => $oldValue,
                'new'   => $newValue,
            ];
        }

        return $changes;
    }
}