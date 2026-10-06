<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdemServicoPagamento extends Model
{
    protected $table = 'ordem_servico_pagamentos';

    protected $fillable = [
        'ordem_servico_id',
        'forma_pagamento',
        'valor'
    ];

    protected $casts = [
        'valor' => 'decimal:2',
    ];

    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(OrdemServico::class, 'ordem_servico_id');
    }
}
