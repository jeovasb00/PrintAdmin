<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estoque extends Model
{
    // nome da tabela
    protected $table = 'estoque';

    // colunas que permitirao salvamento via formulario
    protected $fillable = [
        'nome',
        'quantidade_estoque'
    ];
}
