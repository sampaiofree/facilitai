<?php

namespace App\Http\Controllers;

use App\Models\ProxyIpBan;

class ProxyBanController extends Controller
{
    public function index()
    {
        $bans = ProxyIpBan::orderByDesc('created_at')->paginate(20);

        return view('proxy-ban.index', compact('bans'));
    }
}
