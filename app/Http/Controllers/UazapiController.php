<?php

namespace App\Http\Controllers;

use App\Services\UazapiService;
use Illuminate\Http\Request;

class UazapiController extends Controller
{
    /**
     * Forward a request payload to the Uazapi endpoint defined in the form input.
     */
    public function forward(Request $request, UazapiService $uazapiService)
    {
        $validated = $request->validate([
            'endpoint' => 'required|string',
            'method' => 'sometimes|string|in:get,post,put,patch,delete',
            'payload' => 'sometimes|array',
            'query' => 'sometimes|array',
        ]);

        $method = strtoupper($validated['method'] ?? 'POST');

        $result = $uazapiService->request(
            $method,
            $validated['endpoint'],
            $validated['payload'] ?? [],
            $validated['query'] ?? []
        );

        return response()->json($result);
    }
}
