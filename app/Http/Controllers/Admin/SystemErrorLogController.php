<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemErrorLog;
use Illuminate\Http\Request;

class SystemErrorLogController extends Controller
{
    public function index(Request $request)
    {
        $query = SystemErrorLog::query()->orderByDesc('created_at');

        if ($request->filled('context')) {
            $query->where('context', 'like', '%'.$request->input('context').'%');
        }

        if ($request->filled('instance_id')) {
            $query->where('instance_id', $request->input('instance_id'));
        }

        if ($request->filled('conversation_id')) {
            $query->where('conversation_id', 'like', '%'.$request->input('conversation_id').'%');
        }

        if ($request->filled('function_name')) {
            $query->where('function_name', $request->input('function_name'));
        }

        if ($request->filled('message')) {
            $query->where('message', 'like', '%'.$request->input('message').'%');
        }

        $logs = $query->paginate(50)->withQueryString();

        return view('admin.system_error_logs.index', [
            'logs' => $logs,
            'filters' => $request->only(['context', 'instance_id', 'conversation_id', 'function_name', 'message']),
        ]);
    }
}
