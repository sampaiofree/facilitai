<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HotmarlWebhook;
use App\Models\Instance;
use Illuminate\Http\Request;

class InstanceReportController extends Controller
{
    private const PER_PAGE = 200;

    public function index(Request $request)
    {
        $instances = Instance::with([
            'user',
            'defaultAssistant',
            'defaultAssistantByOpenAi',
        ])
        ->orderByDesc('id')
        ->paginate(self::PER_PAGE);

        $userEmails = $instances->getCollection()
            ->pluck('user.email')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $hotmartWebhooks = collect();

        if (!empty($userEmails)) {
            $hotmartWebhooks = HotmarlWebhook::query()
                ->whereIn('buyer_email', $userEmails)
                ->whereIn('event', ['PURCHASE_COMPLETE', 'PURCHASE_APPROVED'])
                ->orderByDesc('id')
                ->get()
                ->unique('buyer_email')
                ->keyBy('buyer_email');
        }

        return view('admin.instances.index', [
            'instances' => $instances,
            'hotmartWebhooks' => $hotmartWebhooks,
        ]);
    }
}
