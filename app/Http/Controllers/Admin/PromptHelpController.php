<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PromptHelpPrompt;
use App\Models\PromptHelpSection;
use App\Models\PromptHelpTipo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PromptHelpController extends Controller
{
    public function index(): View
    {
        $tipos = PromptHelpTipo::orderBy('name')->get();
        $sections = PromptHelpSection::with('tipo')->orderBy('name')->get();
        $prompts = PromptHelpPrompt::with('section.tipo')->orderBy('name')->get();

        return view('admin.prompt-ajuda.index', compact('tipos', 'sections', 'prompts'));
    }

    public function storeTipo(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
        ]);

        PromptHelpTipo::create($data);

        return redirect()
            ->route('adm.prompt-ajuda.index')
            ->with('success', 'Tipo criado com sucesso.');
    }

    public function updateTipo(Request $request, PromptHelpTipo $tipo): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
        ]);

        $tipo->update($data);

        return redirect()
            ->route('adm.prompt-ajuda.index')
            ->with('success', 'Tipo atualizado com sucesso.');
    }

    public function destroyTipo(PromptHelpTipo $tipo): RedirectResponse
    {
        $tipo->delete();

        return redirect()
            ->route('adm.prompt-ajuda.index')
            ->with('success', 'Tipo removido com sucesso.');
    }

    public function storeSection(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'prompt_help_tipo_id' => ['required', 'exists:prompt_help_tipo,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('prompt_help_section', 'name')
                    ->where('prompt_help_tipo_id', $request->input('prompt_help_tipo_id')),
            ],
            'descricao' => ['nullable', 'string'],
        ]);

        PromptHelpSection::create($data);

        return redirect()
            ->route('adm.prompt-ajuda.index')
            ->with('success', 'Seção criada com sucesso.');
    }

    public function updateSection(Request $request, PromptHelpSection $section): RedirectResponse
    {
        $data = $request->validate([
            'prompt_help_tipo_id' => ['required', 'exists:prompt_help_tipo,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('prompt_help_section', 'name')
                    ->where('prompt_help_tipo_id', $request->input('prompt_help_tipo_id'))
                    ->ignore($section->id),
            ],
            'descricao' => ['nullable', 'string'],
        ]);

        $section->update($data);

        return redirect()
            ->route('adm.prompt-ajuda.index')
            ->with('success', 'Seção atualizada com sucesso.');
    }

    public function destroySection(PromptHelpSection $section): RedirectResponse
    {
        $section->delete();

        return redirect()
            ->route('adm.prompt-ajuda.index')
            ->with('success', 'Seção removida com sucesso.');
    }

    public function storePrompt(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'prompt_help_section_id' => ['required', 'exists:prompt_help_section,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('prompt_help_prompts', 'name')
                    ->where('prompt_help_section_id', $request->input('prompt_help_section_id')),
            ],
            'descricao' => ['nullable', 'string'],
            'prompt' => ['required', 'string'],
        ]);

        PromptHelpPrompt::create($data);

        return redirect()
            ->route('adm.prompt-ajuda.index')
            ->with('success', 'Prompt criado com sucesso.');
    }

    public function updatePrompt(Request $request, PromptHelpPrompt $prompt): RedirectResponse
    {
        $data = $request->validate([
            'prompt_help_section_id' => ['required', 'exists:prompt_help_section,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('prompt_help_prompts', 'name')
                    ->where('prompt_help_section_id', $request->input('prompt_help_section_id'))
                    ->ignore($prompt->id),
            ],
            'descricao' => ['nullable', 'string'],
            'prompt' => ['required', 'string'],
        ]);

        $prompt->update($data);

        return redirect()
            ->route('adm.prompt-ajuda.index')
            ->with('success', 'Prompt atualizado com sucesso.');
    }

    public function destroyPrompt(PromptHelpPrompt $prompt): RedirectResponse
    {
        $prompt->delete();

        return redirect()
            ->route('adm.prompt-ajuda.index')
            ->with('success', 'Prompt removido com sucesso.');
    }
}
