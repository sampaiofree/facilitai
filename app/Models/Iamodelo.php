<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Iamodelo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'iamodelos';

    protected $fillable = ['iaplataforma_id', 'nome', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];

    public function iaplataforma()
    {
        return $this->belongsTo(Iaplataforma::class);
    }
}
