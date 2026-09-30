<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Evenement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    /**
     * Affiche la bibliothèque documentaire d'un événement.
     */
    public function index(Request $request, Evenement $evenement): Response
    {
        abort_unless($request->user()?->can('communication.view'), 403);

        $filters = [
            'type' => $request->string('type')->toString(),
            'acces' => $request->string('acces')->toString(),
        ];

        $documents = $evenement->documents()
            ->with('uploader:id,name')
            ->when($filters['type'] !== '', fn ($builder) => $builder->where('type', $filters['type']))
            ->when($filters['acces'] !== '', fn ($builder) => $builder->where('acces', $filters['acces']))
            ->latest()
            ->get()
            ->map(fn (Document $document): array => [
                'id' => $document->id,
                'titre' => $document->titre,
                'type' => $document->type,
                'acces' => $document->acces,
                'date_upload' => optional($document->created_at)?->toIso8601String(),
                'taille' => Storage::disk('public')->exists($document->fichier_path)
                    ? Storage::disk('public')->size($document->fichier_path)
                    : 0,
                'download_url' => route('communication.documents.download', [
                    'evenement' => $evenement->id,
                    'document' => $document->id,
                ]),
                'uploaded_by' => $document->uploader?->name,
            ])
            ->all();

        return Inertia::render('Communication/Documents/Index', [
            'evenement' => [
                'id' => $evenement->id,
                'titre' => $evenement->titre,
            ],
            'documents' => $documents,
            'filters' => $filters,
            'types' => ['presentation', 'briefing', 'convention', 'video', 'autre'],
            'accesOptions' => ['public', 'participants', 'organisateurs'],
        ]);
    }

    /**
     * Enregistre un nouveau document avec son fichier.
     */
    public function store(Request $request, Evenement $evenement): RedirectResponse
    {
        abort_unless($request->user()?->can('communication.manage'), 403);

        $validated = $request->validate([
            'titre' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['presentation', 'briefing', 'convention', 'video', 'autre'])],
            'acces' => ['required', Rule::in(['public', 'participants', 'organisateurs'])],
            'fichier' => ['required', 'file', 'max:20480'],
        ]);

        $path = $request->file('fichier')->store('documents', 'public');

        $evenement->documents()->create([
            'titre' => $validated['titre'],
            'fichier_path' => $path,
            'type' => $validated['type'],
            'acces' => $validated['acces'],
            'uploaded_by' => $request->user()?->id,
        ]);

        return back()->with('success', 'Document téléversé avec succès.');
    }

    /**
     * Télécharge un document si l'utilisateur dispose des droits.
     */
    public function download(Request $request, Document $document): StreamedResponse
    {
        abort_unless($this->canAccessDocument($request->user(), $document), 403);
        abort_unless(Storage::disk('public')->exists($document->fichier_path), 404);

        return Storage::disk('public')->download(
            $document->fichier_path,
            $document->titre.'.'.pathinfo($document->fichier_path, PATHINFO_EXTENSION)
        );
    }

    /**
     * Supprime un document et son fichier associé.
     */
    public function destroy(Document $document): RedirectResponse
    {
        abort_unless(request()->user()?->can('communication.manage'), 403);

        if ($document->fichier_path) {
            Storage::disk('public')->delete($document->fichier_path);
        }

        $document->delete();

        return back()->with('success', 'Document supprimé avec succès.');
    }

    /**
     * Vérifie les droits d'accès documentaires.
     */
    private function canAccessDocument($user, Document $document): bool
    {
        if ($document->acces === 'public') {
            return true;
        }

        if (! $user) {
            return false;
        }

        if ($user->hasRole('responsable_dcirp') || $user->hasRole('organisateur')) {
            return true;
        }

        if ($document->acces === 'participants') {
            return $document->evenement()
                ->whereHas('inscriptions', fn ($query) => $query->where('user_id', $user->id))
                ->exists();
        }

        return false;
    }
}