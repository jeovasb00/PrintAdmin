<?php

namespace App\Http\Controllers;

use App\Models\Estoque;
use Illuminate\Http\Request;

class EstoqueController extends Controller
{
    // read: lista todos os itens do estoque (GET /api/estoque)
    public function index()
    {
        $itens = Estoque::all();
        return response()->json($itens, 200);
    }

    // create: cria um item no estoque (POST /api/estoque) 
    public function store(Request $request)
    {
        // faz validacao dos dados enviados
        $request->validate([
            'nome' => 'required|string',
            'quantidade_estoque' => 'required|integer'
        ]);

        // salva no banco
        $item = Estoque::create($request->all());

        // retorna o item criado com status 201
        return response()->json($item, 201);
    }

    // read: le um unico item com id unico (GET /api/estoque/{estoque})
    public function show(Estoque $estoque)
    {
        return response()->json($estoque, 200);
    }

    // update: atualiza um item existente (PUT/PATCH /api/estoque/{estoque})
    public function update(Request $request, Estoque $estoque)
    {
        $request->validate([
            'nome' => 'sometimes|string',
            'quantidade_estoque' => 'sometimes|integer'
        ]);

        $estoque->update($request->all());

        return response()->json($estoque, 200);
    }

    // delete: apaga um item (DELETE /api/estoque/{id})
    public function destroy(Estoque $estoque)
    {
        $estoque->delete();
        return response()->json(['mensagem' => 'Item removido com sucesso'], 200);
    }
}
