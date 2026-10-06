<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EstoqueController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrdemServicoController;

// Rotas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rotas protegidas (necessitam do Bearer Token no cabeçalho Authorization)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::apiResource('estoque', EstoqueController::class);
    
    // Ordens de Serviço & Relatório Financeiro
    Route::get('/financeiro/resumo-diario', [OrdemServicoController::class, 'resumoDiario']);
    Route::apiResource('ordens-servico', OrdemServicoController::class);
});