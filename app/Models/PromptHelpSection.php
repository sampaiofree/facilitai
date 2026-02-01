<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromptHelpSection extends Model
{
    use HasFactory;

    protected $table = 'prompt_help_section';

    protected $fillable = [
        'prompt_help_tipo_id',
        'name',
        'descricao',
    ];

    public function tipo()
    {
        return $this->belongsTo(PromptHelpTipo::class, 'prompt_help_tipo_id');
    }

    public function prompts()
    {
        return $this->hasMany(PromptHelpPrompt::class, 'prompt_help_section_id');
    }
}
