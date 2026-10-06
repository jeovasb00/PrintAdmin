<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../services/api'
import { RouterLink } from 'vue-router'

const loading = ref(true)
const itens = ref([])
const ordens = ref([])
const dataFinanceiro = ref(new Date().toISOString().split('T')[0])
const resumoFinanceiro = ref({
  total_faturado: 0,
  total_servicos: 0,
  total_taxa_entrega: 0,
  total_ordens_concluidas: 0,
  por_forma_pagamento: [],
})

const totalItens = computed(() => itens.value.length)
const totalEstoque = computed(() =>
  itens.value.reduce((acc, item) => acc + Number(item.quantidade_estoque || 0), 0)
)
const itensBaixos = computed(() =>
  itens.value.filter((item) => Number(item.quantidade_estoque) <= 5)
)

const ordensEmAberto = computed(() => {
  return ordens.value.filter(o => o.status !== 'concluido' && o.status !== 'cancelado')
})

const formatarDinheiro = (val) => {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(val || 0)
}

const getStatusBadge = (status) => {
  const map = {
    pendente: { label: 'Pendente', class: 'bg-amber-100 text-amber-800' },
    em_producao: { label: 'Em Produção', class: 'bg-blue-100 text-blue-800' },
    aguardando_pagamento: { label: 'Aguard. Pagamento', class: 'bg-orange-100 text-orange-800' },
    pronto_retirada: { label: 'Pronto Retirada', class: 'bg-purple-100 text-purple-800' },
    entrega: { label: 'Saiu Entrega', class: 'bg-cyan-100 text-cyan-800' },
    concluido: { label: 'Concluído', class: 'bg-emerald-100 text-emerald-800' },
    cancelado: { label: 'Cancelado', class: 'bg-red-100 text-red-800' },
  }
  return map[status] || { label: status, class: 'bg-slate-100 text-slate-700' }
}

const carregarDadosFinanceiro = async () => {
  try {
    const res = await api.get(`/financeiro/resumo-diario?data=${dataFinanceiro.value}`)
    resumoFinanceiro.value = res.data
  } catch (err) {
    console.error('Erro ao buscar financeiro:', err)
  }
}

const carregarDashboard = async () => {
  loading.value = true
  try {
    const [resEstoque, resOrdens] = await Promise.all([
      api.get('/estoque'),
      api.get('/ordens-servico'),
    ])
    itens.value = resEstoque.data
    ordens.value = resOrdens.data
    await carregarDadosFinanceiro()
  } catch (err) {
    console.error('Erro ao buscar dados para dashboard:', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  carregarDashboard()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header de Boas-Vindas & Ações Rápidas -->
    <div class="bg-gradient-to-r from-blue-700 via-blue-800 to-indigo-900 rounded-2xl p-6 sm:p-8 text-white shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-6">
      <div>
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-600/50 text-blue-100 text-xs font-medium backdrop-blur-xs mb-3">
          <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
          Painel Administrativo Conectado
        </span>
        <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">Painel da Copiadora</h1>
        <p class="text-blue-200 text-sm mt-1 max-w-xl">
          Controle diário de faturamento, ordens de serviço em andamento e reposição de insumos.
        </p>
      </div>

      <div class="flex flex-wrap items-center gap-3">
        <RouterLink
          to="/servicos"
          class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600/60 hover:bg-blue-600 text-white text-sm font-semibold border border-blue-400/40 transition cursor-pointer"
        >
          <span>🖩</span>
          <span>Calculadora Rápida</span>
        </RouterLink>

        <RouterLink
          to="/ordens-servico"
          class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white text-blue-900 text-sm font-semibold hover:bg-blue-50 transition shadow-xs cursor-pointer"
        >
          <span>📋</span>
          <span>Ordens de Serviço</span>
        </RouterLink>
      </div>
    </div>

    <!-- SEÇÃO: FECHAMENTO DE CAIXA / FATURAMENTO DO DIA -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200 p-6 space-y-5">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-4 border-b border-slate-100">
        <div>
          <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
            <span>💵</span>
            <span>Controle Financeiro / Fechamento Diário</span>
          </h2>
          <p class="text-xs text-slate-500 mt-0.5">Soma dos valores das Ordens de Serviço concluídas e pagas na data</p>
        </div>

        <div class="flex items-center gap-2">
          <span class="text-xs font-semibold text-slate-500">Data de Consulta:</span>
          <input
            v-model="dataFinanceiro"
            type="date"
            @change="carregarDadosFinanceiro"
            class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer"
          />
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Card Faturamento Total -->
        <div class="md:col-span-1 bg-slate-900 text-white p-5 rounded-2xl flex flex-col justify-between shadow-md">
          <div>
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Faturamento do Dia</span>
            <span class="text-3xl font-extrabold text-emerald-400 mt-2 block tracking-tight">
              {{ formatarDinheiro(resumoFinanceiro.total_faturado) }}
            </span>
          </div>

          <div class="pt-4 mt-4 border-t border-slate-800 text-xs text-slate-300 flex items-center justify-between">
            <span>OS Concluídas:</span>
            <strong class="text-white text-sm font-bold">{{ resumoFinanceiro.total_ordens_concluidas }}</strong>
          </div>
        </div>

        <!-- Detalhamento por Forma de Pagamento (3 Colunas) -->
        <div class="md:col-span-3 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
          <div
            v-for="fp in resumoFinanceiro.por_forma_pagamento"
            :key="fp.forma"
            class="bg-slate-50 border border-slate-200 rounded-xl p-3.5 flex flex-col justify-between"
          >
            <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">{{ fp.nome }}</span>
            <span class="text-lg font-bold text-slate-900 mt-1 block font-mono">
              {{ formatarDinheiro(fp.valor) }}
            </span>
            <span class="text-[10px] text-slate-400 mt-1 block">
              {{ resumoFinanceiro.total_faturado > 0 ? `${((fp.valor / resumoFinanceiro.total_faturado) * 100).toFixed(0)}% do total` : '0%' }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Cards de Métricas Operacionais -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200 flex items-center justify-between">
        <div>
          <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">OS em Andamento</span>
          <p class="text-2xl font-bold text-blue-600 mt-1">{{ ordensEmAberto.length }}</p>
          <span class="text-xs text-slate-400 mt-0.5 block">Precisam de atenção</span>
        </div>
        <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl font-bold">
          📋
        </div>
      </div>

      <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200 flex items-center justify-between">
        <div>
          <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Volume de Insumos</span>
          <p class="text-2xl font-bold text-emerald-600 mt-1">{{ totalEstoque }}</p>
          <span class="text-xs text-slate-400 mt-0.5 block">Unidades em estoque</span>
        </div>
        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-bold">
          📦
        </div>
      </div>

      <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200 flex items-center justify-between">
        <div>
          <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Estoque Baixo</span>
          <p :class="['text-2xl font-bold mt-1', itensBaixos.length > 0 ? 'text-rose-600' : 'text-slate-700']">
            {{ itensBaixos.length }}
          </p>
          <span class="text-xs text-slate-400 mt-0.5 block">&le; 5 unidades</span>
        </div>
        <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl font-bold">
          ⚠️
        </div>
      </div>

    </div>

    <!-- Grid de Seções Secundárias (OS em Aberto + Alertas de Insumos) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      
      <!-- Ordens de Serviço Recentes / Em Aberto -->
      <div class="bg-white rounded-2xl shadow-xs border border-slate-200 p-6 space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-base font-bold text-slate-900">📋 Pedidos / OS em Fila de Atendimento</h2>
            <p class="text-xs text-slate-500">Últimos pedidos que aguardam produção ou entrega</p>
          </div>
          <RouterLink to="/ordens-servico" class="text-xs font-semibold text-blue-600 hover:text-blue-700">
            Ver todas &rarr;
          </RouterLink>
        </div>

        <div v-if="loading" class="py-8 text-center text-sm text-slate-400">Carregando...</div>
        
        <div v-else-if="ordensEmAberto.length === 0" class="py-8 text-center text-sm text-emerald-600 bg-emerald-50 rounded-xl">
          ✨ Não há pedidos pendentes no momento!
        </div>

        <div v-else class="divide-y divide-slate-100 max-h-80 overflow-y-auto">
          <div
            v-for="ordem in ordensEmAberto.slice(0, 5)"
            :key="ordem.id"
            class="py-3 flex items-center justify-between gap-3"
          >
            <div>
              <div class="flex items-center gap-2">
                <span class="font-mono text-xs font-bold text-slate-800">#{{ ordem.id }}</span>
                <span class="text-sm font-semibold text-slate-900">{{ ordem.cliente_nome }}</span>
              </div>
              <p class="text-xs text-slate-500 truncate max-w-xs mt-0.5">{{ ordem.descricao }}</p>
            </div>

            <div class="flex items-center gap-3 shrink-0">
              <span class="font-bold text-xs text-slate-900">{{ formatarDinheiro(ordem.valor_total) }}</span>
              <span :class="['px-2 py-0.5 rounded-full text-[11px] font-semibold', getStatusBadge(ordem.status).class]">
                {{ getStatusBadge(ordem.status).label }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Lista de Itens Críticos de Estoque -->
      <div class="bg-white rounded-2xl shadow-xs border border-slate-200 p-6 space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-base font-bold text-slate-900">⚠️ Insumos em Nível Baixo</h2>
            <p class="text-xs text-slate-500">Itens que precisam de reposição para não travar a produção</p>
          </div>
          <RouterLink to="/estoque" class="text-xs font-semibold text-blue-600 hover:text-blue-700">
            Gerenciar Estoque &rarr;
          </RouterLink>
        </div>

        <div v-if="loading" class="py-8 text-center text-sm text-slate-400">Carregando...</div>

        <div v-else-if="itensBaixos.length === 0" class="py-8 text-center text-sm text-emerald-600 bg-emerald-50 rounded-xl">
          ✨ Todos os insumos estão com estoque suficiente!
        </div>

        <div v-else class="divide-y divide-slate-100 max-h-80 overflow-y-auto">
          <div
            v-for="item in itensBaixos"
            :key="item.id"
            class="py-3 flex items-center justify-between"
          >
            <div class="flex items-center gap-3">
              <span class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center text-xs font-mono font-bold">
                #{{ item.id }}
              </span>
              <div>
                <p class="text-sm font-semibold text-slate-800">{{ item.nome }}</p>
                <span class="text-xs text-rose-500 font-medium">Reposição recomendada</span>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-700">
                Restam {{ item.quantidade_estoque }} un
              </span>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>
