<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        $search = $request->query('search');
        $actionFilter = $request->query('action', 'all');

        $query = AuditLog::with(['user'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('action', 'like', "%{$search}%")
                        ->orWhere('model_type', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($actionFilter !== 'all', function ($query) use ($actionFilter) {
                $query->where('action', $actionFilter);
            })
            ->orderBy('created_at', 'desc');

        $logs = $query->paginate(25);

        return Inertia::render('admin/AuditLogs', [
            'logs' => $logs,
            'filters' => [
                'search' => $search,
                'action' => $actionFilter,
            ],
        ]);
    }
}
