<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromptHelpTipo extends Model
{
    use HasFactory;

    protected $table = 'prompt_help_tipo';

    protected $fillable = [
        'name',
        'descricao',
    ];

    public function sections()
    {
        return $this->hasMany(PromptHelpSection::class, 'prompt_help_tipo_id');
    }
}
