<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Iaplataforma extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'iaplataformas';

    protected $fillable = ['nome', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];

    public function credentials()
    {
        return $this->hasMany(Credential::class);
    }
}
