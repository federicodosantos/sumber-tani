<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of the activity logs.
     */
    public function index(Request $request)
    {
        $allowedSorts = [
            'created_at' => 'activity_log.created_at',
            'description' => 'activity_log.description',
            'module' => 'activity_log.subject_type',
            'causer' => 'users.username',
        ];

        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc');
        $direction = in_array($direction, ['asc', 'desc']) ? $direction : 'desc';

        $query = Activity::query()
            ->leftJoin('users', 'activity_log.causer_id', '=', 'users.id')
            ->select('activity_log.*', 'users.username as causer_username', 'users.role as causer_role')
            ->with('causer');

        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->where('activity_log.description', 'like', "%{$search}%")
                    ->orWhere('activity_log.event', 'like', "%{$search}%")
                    ->orWhere('activity_log.subject_type', 'like', "%{$search}%")
                    ->orWhere('users.username', 'like', "%{$search}%");
            });
        }

        $sortColumn = $allowedSorts[$sort] ?? 'activity_log.created_at';
        $query->orderBy($sortColumn, $direction);

        $activities = $query->paginate(10)->withQueryString();

        return view('activity-log.index', [
            'activities' => $activities,
            'currentSort' => $sort,
            'direction' => $direction,
        ]);
    }

    /**
     * Display details for a single activity log.
     */
    public function show(Activity $activity)
    {
        $activity->load('causer');

        return view('activity-log.show', [
            'activity' => $activity,
            'module' => $this->moduleName($activity->subject_type),
        ]);
    }

    private function moduleName(?string $subjectType): string
    {
        return $subjectType ? Str::headline(class_basename($subjectType)) : '-';
    }
}
