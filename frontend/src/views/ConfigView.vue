<script setup>
import { ref } from 'vue'

const apiUrl = ref('http://127.0.0.1:8000/api/estoque')
const nomeLoja = ref('Copiadora & Gráfica Express')
const notificacaoEstoque = ref(true)
const limiteEstoqueBaixo = ref(5)
const salvo = ref(false)

const salvarConfig = () => {
  salvo.value = true
  setTimeout(() => {
    salvo.value = false
  }, 3000)
}
</script>

<template>
  <div class="space-y-6 max-w-4xl">
    <div>
      <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Configurações do Sistema</h1>
      <p class="text-sm text-slate-500 mt-1">Parâmetros de funcionamento, integração com API e alertas</p>
    </div>

    <div v-if="salvo" class="p-4 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 text-sm font-semibold flex items-center gap-2">
      <span>✅</span> Configurações salvas localmente com sucesso!
    </div>

    <div class="bg-white rounded-2xl shadow-xs border border-slate-200 p-6 space-y-6">
      <div class="space-y-4">
        <h2 class="text-base font-bold text-slate-800 pb-2 border-b border-slate-100">Geral da Empresa</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Nome do Estabelecimento</label>
            <input
              v-model="nomeLoja"
              type="text"
              class="w-full px-3.5 py-2 rounded-lg bg-slate-50 border border-slate-300 text-sm text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Endereço da API Laravel</label>
            <input
              v-model="apiUrl"
              type="text"
              class="w-full px-3.5 py-2 rounded-lg bg-slate-50 border border-slate-300 text-sm text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-xs"
            />
          </div>
        </div>
      </div>

      <div class="space-y-4 pt-4 border-t border-slate-100">
        <h2 class="text-base font-bold text-slate-800 pb-2 border-b border-slate-100">Alertas de Estoque</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
          <div>
            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Limite para Alerta de Estoque Baixo (unidades)</label>
            <input
              v-model.number="limiteEstoqueBaixo"
              type="number"
              min="1"
              class="w-full px-3.5 py-2 rounded-lg bg-slate-50 border border-slate-300 text-sm text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div class="flex items-center gap-3 pt-4 md:pt-0">
            <input
              id="notif"
              v-model="notificacaoEstoque"
              type="checkbox"
              class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500"
            />
            <label for="notif" class="text-sm font-medium text-slate-700">Ativar avisos visuais no painel</label>
          </div>
        </div>
      </div>

      <div class="pt-4 border-t border-slate-100">
        <button
          @click="salvarConfig"
          class="px-5 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm shadow-xs transition cursor-pointer"
        >
          Salvar Preferências
        </button>
      </div>
    </div>
  </div>
</template>
