<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromptHelpPrompt extends Model
{
    use HasFactory;

    protected $table = 'prompt_help_prompts';

    protected $fillable = [
        'prompt_help_section_id',
        'name',
        'descricao',
        'prompt',
    ];

    public function section()
    {
        return $this->belongsTo(PromptHelpSection::class, 'prompt_help_section_id');
    }
}
