<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeadEmpresa;
use Illuminate\Http\Request;

class LeadEmpresaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $leads = LeadEmpresa::latest()->paginate(20); // Paginação para não sobrecarregar
        return view('admin.leads.index', compact('leads'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(LeadEmpresa $leadEmpresa)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LeadEmpresa $leadEmpresa)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LeadEmpresa $leadEmpresa)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LeadEmpresa $leadEmpresa)
    {
        //
    }
}
