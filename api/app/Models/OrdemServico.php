<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrdemServico extends Model
{
    protected $table = 'ordens_servico';

    protected $fillable = [
        'cliente_nome',
        'cliente_contato',
        'descricao',
        'valor_servico',
        'taxa_entrega',
        'valor_total',
        'status',
        'estoque_baixado',
        'data_conclusao',
    ];

    protected $casts = [
        'valor_servico' => 'decimal:2',
        'taxa_entrega' => 'decimal:2',
        'valor_total' => 'decimal:2',
        'estoque_baixado' => 'boolean',
        'data_conclusao' => 'datetime',
    ];

    public function itens(): HasMany
    {
        return $this->hasMany(OrdemServicoItem::class, 'ordem_servico_id');
    }

    public function pagamentos(): HasMany
    {
        return $this->hasMany(OrdemServicoPagamento::class, 'ordem_servico_id');
    }
}
