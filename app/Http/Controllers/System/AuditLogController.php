<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class AuditLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = AuditLog::with('user');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('event', 'like', "%{$search}%")
                  ->orWhere('auditable_type', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhere('tags', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($u) use ($search) {
                      $u->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('event')) {
            $query->where('event', $request->input('event'));
        }

        if ($request->filled('auditable_type')) {
            $query->where('auditable_type', $request->input('auditable_type'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $allowedSorts = ['created_at', 'event', 'auditable_type', 'user_id', 'ip_address'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $logs = $query->paginate($request->input('per_page', 15))
            ->withQueryString();

        return Inertia::render('System/Logs', [
            'logs' => $logs,
            'filters' => $request->all(),
            'events' => AuditLog::select('event')->distinct()->pluck('event'),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(AuditLog $auditLog)
    {
        $auditLog->load('user');

        return Inertia::render('System/Logs', [
            'log' => $auditLog,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AuditLog $auditLog)
    {
        $auditLog->delete();

        return Redirect::route('system.logs')
            ->with('success', 'Audit log deleted successfully.');
    }

    public function clear(Request $request)
    {
        AuditLog::where('user_id', auth()->id())->delete();

        return Redirect::route('system.logs')
            ->with('success', 'Logs d\'audit effacés.');
    }
}
