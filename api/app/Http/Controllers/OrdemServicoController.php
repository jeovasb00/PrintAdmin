<?php

namespace App\Http\Controllers;

use App\Models\Estoque;
use App\Models\OrdemServico;
use App\Models\OrdemServicoItem;
use App\Models\OrdemServicoPagamento;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrdemServicoController extends Controller
{
    /**
     * Listar ordens de serviço com filtros opcionais
     */
    public function index(Request $request)
    {
        $query = OrdemServico::with(['itens.estoque', 'pagamentos'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('busca')) {
            $busca = $request->busca;
            $query->where(function ($q) use ($busca) {
                $q->where('cliente_nome', 'like', "%{$busca}%")
                  ->orWhere('cliente_contato', 'like', "%{$busca}%")
                  ->orWhere('descricao', 'like', "%{$busca}%");
            });
        }

        if ($request->filled('data')) {
            $query->whereDate('created_at', $request->data);
        }

        return response()->json($query->get());
    }

    /**
     * Criar uma nova ordem de serviço
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'cliente_nome' => 'required|string|max:255',
            'cliente_contato' => 'nullable|string|max:100',
            'descricao' => 'required|string',
            'valor_servico' => 'required|numeric|min:0',
            'taxa_entrega' => 'nullable|numeric|min:0',
            'status' => 'required|in:pendente,em_producao,aguardando_pagamento,pronto_retirada,entrega,concluido,cancelado',
            'itens' => 'nullable|array',
            'itens.*.estoque_id' => 'required|exists:estoque,id',
            'itens.*.quantidade' => 'required|integer|min:1',
            'pagamentos' => 'nullable|array',
            'pagamentos.*.forma_pagamento' => 'required|string|in:pix,dinheiro,cartao_credito,cartao_debito,a_prazo',
            'pagamentos.*.valor' => 'required|numeric|min:0.01',
        ]);

        $valorServico = (float) $validated['valor_servico'];
        $taxaEntrega = (float) ($validated['taxa_entrega'] ?? 0);
        $valorTotal = $valorServico + $taxaEntrega;

        // Se for cadastrada diretamente como concluída, verificar estoque de todos os insumos
        if ($validated['status'] === 'concluido' && !empty($validated['itens'])) {
            $this->validarDisponibilidadeEstoque($validated['itens']);
        }

        return DB::transaction(function () use ($validated, $valorServico, $taxaEntrega, $valorTotal) {
            $isConcluido = $validated['status'] === 'concluido';

            $ordem = OrdemServico::create([
                'cliente_nome' => $validated['cliente_nome'],
                'cliente_contato' => $validated['cliente_contato'] ?? null,
                'descricao' => $validated['descricao'],
                'valor_servico' => $valorServico,
                'taxa_entrega' => $taxaEntrega,
                'valor_total' => $valorTotal,
                'status' => $validated['status'],
                'estoque_baixado' => $isConcluido,
                'data_conclusao' => $isConcluido ? Carbon::now() : null,
            ]);

            // Salvar itens / insumos
            if (!empty($validated['itens'])) {
                foreach ($validated['itens'] as $item) {
                    OrdemServicoItem::create([
                        'ordem_servico_id' => $ordem->id,
                        'estoque_id' => $item['estoque_id'],
                        'quantidade' => $item['quantidade'],
                    ]);

                    // Se já estiver concluído, baixar o estoque imediatamente
                    if ($isConcluido) {
                        Estoque::where('id', $item['estoque_id'])->decrement('quantidade_estoque', $item['quantidade']);
                    }
                }
            }

            // Salvar formas de pagamento
            if (!empty($validated['pagamentos'])) {
                foreach ($validated['pagamentos'] as $pagamento) {
                    OrdemServicoPagamento::create([
                        'ordem_servico_id' => $ordem->id,
                        'forma_pagamento' => $pagamento['forma_pagamento'],
                        'valor' => $pagamento['valor'],
                    ]);
                }
            }

            return response()->json($ordem->load(['itens.estoque', 'pagamentos']), 201);
        });
    }

    /**
     * Exibir detalhes de uma ordem de serviço
     */
    public function show($id)
    {
        $ordem = OrdemServico::with(['itens.estoque', 'pagamentos'])->findOrFail($id);
        return response()->json($ordem);
    }

    /**
     * Atualizar ordem de serviço e controlar baixa/estorno de estoque
     */
    public function update(Request $request, $id)
    {
        $ordem = OrdemServico::with(['itens', 'pagamentos'])->findOrFail($id);

        $validated = $request->validate([
            'cliente_nome' => 'required|string|max:255',
            'cliente_contato' => 'nullable|string|max:100',
            'descricao' => 'required|string',
            'valor_servico' => 'required|numeric|min:0',
            'taxa_entrega' => 'nullable|numeric|min:0',
            'status' => 'required|in:pendente,em_producao,aguardando_pagamento,pronto_retirada,entrega,concluido,cancelado',
            'itens' => 'nullable|array',
            'itens.*.estoque_id' => 'required|exists:estoque,id',
            'itens.*.quantidade' => 'required|integer|min:1',
            'pagamentos' => 'nullable|array',
            'pagamentos.*.forma_pagamento' => 'required|string|in:pix,dinheiro,cartao_credito,cartao_debito,a_prazo',
            'pagamentos.*.valor' => 'required|numeric|min:0.01',
        ]);

        $valorServico = (float) $validated['valor_servico'];
        $taxaEntrega = (float) ($validated['taxa_entrega'] ?? 0);
        $valorTotal = $valorServico + $taxaEntrega;

        $novoStatus = $validated['status'];
        $itensNovos = $validated['itens'] ?? [];

        // Validação de estoque ao transitar para 'concluido'
        if ($novoStatus === 'concluido' && !$ordem->estoque_baixado && !empty($itensNovos)) {
            $this->validarDisponibilidadeEstoque($itensNovos);
        }

        return DB::transaction(function () use ($ordem, $validated, $valorServico, $taxaEntrega, $valorTotal, $novoStatus, $itensNovos) {
            $eraEstoqueBaixado = $ordem->estoque_baixado;

            // Se estava baixado e agora foi cancelado ou voltou para status não concluído -> Estornar estoque
            if ($eraEstoqueBaixado && $novoStatus !== 'concluido') {
                foreach ($ordem->itens as $itemAntigo) {
                    Estoque::where('id', $itemAntigo->estoque_id)->increment('quantidade_estoque', $itemAntigo->quantidade);
                }
                $ordem->estoque_baixado = false;
                $ordem->data_conclusao = null;
            }

            // Atualiza os dados principais
            $ordem->update([
                'cliente_nome' => $validated['cliente_nome'],
                'cliente_contato' => $validated['cliente_contato'] ?? null,
                'descricao' => $validated['descricao'],
                'valor_servico' => $valorServico,
                'taxa_entrega' => $taxaEntrega,
                'valor_total' => $valorTotal,
                'status' => $novoStatus,
            ]);

            // Se não estava baixado e agora virou 'concluido'
            if (!$eraEstoqueBaixado && $novoStatus === 'concluido') {
                $ordem->estoque_baixado = true;
                $ordem->data_conclusao = Carbon::now();
                $ordem->save();
            }

            // Recriar itens da OS
            $ordem->itens()->delete();
            if (!empty($itensNovos)) {
                foreach ($itensNovos as $item) {
                    OrdemServicoItem::create([
                        'ordem_servico_id' => $ordem->id,
                        'estoque_id' => $item['estoque_id'],
                        'quantidade' => $item['quantidade'],
                    ]);

                    // Se a OS está concluída agora, debitar do estoque
                    if ($ordem->estoque_baixado) {
                        Estoque::where('id', $item['estoque_id'])->decrement('quantidade_estoque', $item['quantidade']);
                    }
                }
            }

            // Recriar pagamentos
            $ordem->pagamentos()->delete();
            if (!empty($validated['pagamentos'])) {
                foreach ($validated['pagamentos'] as $pagamento) {
                    OrdemServicoPagamento::create([
                        'ordem_servico_id' => $ordem->id,
                        'forma_pagamento' => $pagamento['forma_pagamento'],
                        'valor' => $pagamento['valor'],
                    ]);
                }
            }

            $ordem->save();

            return response()->json($ordem->load(['itens.estoque', 'pagamentos']));
        });
    }

    /**
     * Excluir ordem de serviço (estorna estoque se já havia sido concluída)
     */
    public function destroy($id)
    {
        $ordem = OrdemServico::with('itens')->findOrFail($id);

        DB::transaction(function () use ($ordem) {
            // Se o estoque foi baixado, estornar antes de excluir
            if ($ordem->estoque_baixado) {
                foreach ($ordem->itens as $item) {
                    Estoque::where('id', $item->estoque_id)->increment('quantidade_estoque', $item->quantidade);
                }
            }

            $ordem->delete();
        });

        return response()->json(['message' => 'Ordem de serviço removida com sucesso.']);
    }

    /**
     * Relatório de Resumo Financeiro Diário
     */
    public function resumoDiario(Request $request)
    {
        $dataConsulta = $request->input('data', Carbon::today()->toDateString());

        // Ordens concluídas na data especificada (usando data_conclusao ou created_at)
        $ordens = OrdemServico::with('pagamentos')
            ->where('status', 'concluido')
            ->where(function ($q) use ($dataConsulta) {
                $q->whereDate('data_conclusao', $dataConsulta)
                  ->orWhere(function ($sub) use ($dataConsulta) {
                      $sub->whereNull('data_conclusao')
                          ->whereDate('updated_at', $dataConsulta);
                  });
            })
            ->get();

        $totalFaturado = $ordens->sum('valor_total');
        $totalServicos = $ordens->sum('valor_servico');
        $totalTaxaEntrega = $ordens->sum('taxa_entrega');
        $totalOrdensConcluidas = $ordens->count();

        // Agrupamento por forma de pagamento
        $totaisPorForma = [
            'pix' => 0,
            'dinheiro' => 0,
            'cartao_credito' => 0,
            'cartao_debito' => 0,
            'a_prazo' => 0,
        ];

        foreach ($ordens as $ordem) {
            foreach ($ordem->pagamentos as $pagamento) {
                $forma = $pagamento->forma_pagamento;
                if (isset($totaisPorForma[$forma])) {
                    $totaisPorForma[$forma] += (float) $pagamento->valor;
                } else {
                    $totaisPorForma[$forma] = (float) $pagamento->valor;
                }
            }
        }

        return response()->json([
            'data' => $dataConsulta,
            'total_faturado' => round($totalFaturado, 2),
            'total_servicos' => round($totalServicos, 2),
            'total_taxa_entrega' => round($totalTaxaEntrega, 2),
            'total_ordens_concluidas' => $totalOrdensConcluidas,
            'por_forma_pagamento' => [
                ['forma' => 'pix', 'nome' => 'Pix', 'valor' => round($totaisPorForma['pix'], 2)],
                ['forma' => 'dinheiro', 'nome' => 'Dinheiro', 'valor' => round($totaisPorForma['dinheiro'], 2)],
                ['forma' => 'cartao_credito', 'nome' => 'Cartão de Crédito', 'valor' => round($totaisPorForma['cartao_credito'], 2)],
                ['forma' => 'cartao_debito', 'nome' => 'Cartão de Débito', 'valor' => round($totaisPorForma['cartao_debito'], 2)],
                ['forma' => 'a_prazo', 'nome' => 'A Prazo / Fiado', 'valor' => round($totaisPorForma['a_prazo'], 2)],
            ],
            'ordens' => $ordens,
        ]);
    }

    /**
     * Validador auxiliar de disponibilidade de estoque
     */
    private function validarDisponibilidadeEstoque(array $itens): void
    {
        foreach ($itens as $item) {
            $estoque = Estoque::find($item['estoque_id']);
            if (!$estoque) {
                throw ValidationException::withMessages([
                    'itens' => ["Item de estoque ID {$item['estoque_id']} não encontrado."],
                ]);
            }

            if ($estoque->quantidade_estoque < $item['quantidade']) {
                throw ValidationException::withMessages([
                    'itens' => ["Estoque insuficiente para '{$estoque->nome}'. Disponível: {$estoque->quantidade_estoque}, Solicitado: {$item['quantidade']}."],
                ]);
            }
        }
    }
}
