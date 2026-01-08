<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebhookRequest;
use Illuminate\Http\Request;

class WebhookRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = WebhookRequest::query()->orderByDesc('created_at');

        if ($request->filled('instance_id')) {
            $query->where('instance_id', $request->input('instance_id'));
        }

        if ($request->filled('contact')) {
            $query->where('contact', 'like', '%'.$request->input('contact').'%');
        }

        if ($request->filled('message_type')) {
            $query->where('message_type', $request->input('message_type'));
        }

        if ($request->filled('from_me')) {
            $query->where('from_me', (bool) $request->boolean('from_me'));
        }

        if ($request->filled('event_id')) {
            $query->where('event_id', 'like', '%'.$request->input('event_id').'%');
        }

        $requests = $query->paginate(50)->withQueryString();

        return view('admin.webhook_requests.index', [
            'requests' => $requests,
            'filters' => $request->only(['instance_id', 'contact', 'message_type', 'from_me', 'event_id']),
        ]);
    }

    public function destroyAll()
    {
        WebhookRequest::query()->delete();

        return redirect()
            ->route('admin.webhook-requests.index')
            ->with('success', 'Logs de webhook removidos com sucesso.');
    }
}
