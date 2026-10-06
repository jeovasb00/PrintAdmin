<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ordens_servico', function (Blueprint $table) {
            $table->id();
            $table->string('cliente_nome');
            $table->string('cliente_contato')->nullable();
            $table->text('descricao');
            $table->decimal('valor_servico', 10, 2)->default(0);
            $table->decimal('taxa_entrega', 10, 2)->default(0);
            $table->decimal('valor_total', 10, 2)->default(0);
            $table->enum('status', [
                'pendente',
                'em_producao',
                'aguardando_pagamento',
                'pronto_retirada',
                'entrega',
                'concluido',
                'cancelado'
            ])->default('pendente');
            $table->boolean('estoque_baixado')->default(false);
            $table->timestamp('data_conclusao')->nullable();
            $table->timestamps();
        });

        Schema::create('ordem_servico_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ordem_servico_id')->constrained('ordens_servico')->onDelete('cascade');
            $table->foreignId('estoque_id')->constrained('estoque')->onDelete('cascade');
            $table->integer('quantidade');
            $table->timestamps();
        });

        Schema::create('ordem_servico_pagamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ordem_servico_id')->constrained('ordens_servico')->onDelete('cascade');
            $table->string('forma_pagamento'); // pix, dinheiro, cartao_credito, cartao_debito, a_prazo
            $table->decimal('valor', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ordem_servico_pagamentos');
        Schema::dropIfExists('ordem_servico_itens');
        Schema::dropIfExists('ordens_servico');
    }
};
