<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class AuditController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('admin.audit'), 403);

        $filters = [
            'user' => $request->string('user')->toString(),
            'type' => $request->string('type')->toString(),
            'date' => $request->string('date')->toString(),
        ];

        $activities = Activity::query()
            ->with('causer')
            ->when($filters['user'] !== '', fn ($builder) => $builder->where('causer_id', $filters['user']))
            ->when($filters['type'] !== '', function ($builder) use ($filters): void {
                $builder
                    ->where('log_name', $filters['type'])
                    ->orWhere('event', $filters['type']);
            })
            ->when($filters['date'] !== '', fn ($builder) => $builder->whereDate('created_at', $filters['date']))
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Activity $activity): array => $this->mapActivity($activity));

        return Inertia::render('Admin/Audit/Index', [
            'activities' => $activities,
            'filters' => $filters,
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function show(Request $request, Activity $activity): Response
    {
        abort_unless($request->user()?->can('admin.audit'), 403);

        $activity->load('causer');

        return Inertia::render('Admin/Audit/Show', [
            'activity' => $this->mapActivity($activity),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapActivity(Activity $activity): array
    {
        return [
            'id' => $activity->id,
            'log_name' => $activity->log_name,
            'description' => $activity->description,
            'event' => $activity->event,
            'subject_type' => $activity->subject_type,
            'subject_id' => $activity->subject_id,
            'created_at' => optional($activity->created_at)?->toIso8601String(),
            'causer' => $activity->causer ? [
                'id' => $activity->causer->id,
                'name' => $activity->causer->name,
                'email' => $activity->causer->email,
            ] : null,
            'properties' => $activity->properties?->toArray() ?? [],
        ];
    }
}