<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '../services/api'

const route = useRoute()

// 1. ESTADOS
const ordens = ref([])
const estoqueItens = ref([])
const loading = ref(false)
const salvando = ref(false)
const modalAberto = ref(false)
const modalExclusaoAberto = ref(false)
const ordemParaExcluir = ref(null)

const filtroStatus = ref('')
const filtroBusca = ref('')
const mensagemAlerta = ref({ texto: '', tipo: '' })

// Formulário de Cadastro / Edição da OS
const form = reactive({
  id: null,
  cliente_nome: '',
  cliente_contato: '',
  descricao: '',
  valor_servico: '',
  taxa_entrega: 0,
  status: 'pendente',
  itens: [], // [{ estoque_id: null, quantidade: 1 }]
  pagamentos: [], // [{ forma_pagamento: 'pix', valor: 0 }]
})

// Opções de Status disponíveis
const statusOpcoes = [
  { valor: 'pendente', label: 'Pendente / Fila', badgeClass: 'bg-amber-100 text-amber-800 border-amber-200' },
  { valor: 'em_producao', label: 'Em Produção', badgeClass: 'bg-blue-100 text-blue-800 border-blue-200' },
  { valor: 'aguardando_pagamento', label: 'Aguardando Pagamento', badgeClass: 'bg-orange-100 text-orange-800 border-orange-200' },
  { valor: 'pronto_retirada', label: 'Pronto p/ Retirada', badgeClass: 'bg-purple-100 text-purple-800 border-purple-200' },
  { valor: 'entrega', label: 'Saiu p/ Entrega', badgeClass: 'bg-cyan-100 text-cyan-800 border-cyan-200' },
  { valor: 'concluido', label: 'Concluído', badgeClass: 'bg-emerald-100 text-emerald-800 border-emerald-200' },
  { valor: 'cancelado', label: 'Cancelado', badgeClass: 'bg-red-100 text-red-800 border-red-200' },
]

// Opções de Formas de Pagamento
const formasPagamentoOpcoes = [
  { valor: 'pix', label: 'Pix' },
  { valor: 'dinheiro', label: 'Dinheiro' },
  { valor: 'cartao_credito', label: 'Cartão de Crédito' },
  { valor: 'cartao_debito', label: 'Cartão de Débito' },
  { valor: 'a_prazo', label: 'A Prazo / Fiado' },
]

// 2. COMPUTADAS
const valorTotalCalculado = computed(() => {
  const servico = parseFloat(form.valor_servico) || 0
  const taxa = parseFloat(form.taxa_entrega) || 0
  return servico + taxa
})

const totalPagoCalculado = computed(() => {
  return form.pagamentos.reduce((acc, p) => acc + (parseFloat(p.valor) || 0), 0)
})

const saldoRestantePagamento = computed(() => {
  return Math.max(0, valorTotalCalculado.value - totalPagoCalculado.value)
})

const ordensFiltradas = computed(() => {
  return ordens.value.filter((ordem) => {
    const matchStatus = !filtroStatus.value || ordem.status === filtroStatus.value
    const busca = filtroBusca.value.toLowerCase()
    const matchBusca =
      !busca ||
      ordem.cliente_nome.toLowerCase().includes(busca) ||
      (ordem.cliente_contato && ordem.cliente_contato.toLowerCase().includes(busca)) ||
      ordem.descricao.toLowerCase().includes(busca)
    return matchStatus && matchBusca
  })
})

const resumoContadores = computed(() => {
  return {
    total: ordens.value.length,
    pendentes: ordens.value.filter(o => o.status === 'pendente' || o.status === 'em_producao').length,
    aguardando_pag: ordens.value.filter(o => o.status === 'aguardando_pagamento').length,
    prontos: ordens.value.filter(o => o.status === 'pronto_retirada' || o.status === 'entrega').length,
    concluidos: ordens.value.filter(o => o.status === 'concluido').length,
  }
})

// 3. MÉTODOS
const exibirAlerta = (texto, tipo = 'sucesso') => {
  mensagemAlerta.value = { texto, tipo }
  setTimeout(() => {
    mensagemAlerta.value = { texto: '', tipo: '' }
  }, 4500)
}

const formatarDinheiro = (valor) => {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(valor || 0)
}

const formatarData = (dataStr) => {
  if (!dataStr) return '-'
  return new Date(dataStr).toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const getStatusInfo = (status) => {
  return statusOpcoes.find((s) => s.valor === status) || { label: status, badgeClass: 'bg-slate-100 text-slate-700' }
}

const carregarDados = async () => {
  loading.value = true
  try {
    const [resOrdens, resEstoque] = await Promise.all([
      api.get('/ordens-servico'),
      api.get('/estoque'),
    ])
    ordens.value = resOrdens.data
    estoqueItens.value = resEstoque.data
  } catch (err) {
    console.error('Erro ao carregar dados:', err)
    exibirAlerta('Não foi possível carregar as Ordens de Serviço.', 'erro')
  } finally {
    loading.value = false
  }
}

// Insumos no Formulário
const adicionarItemInsumo = () => {
  form.itens.push({
    estoque_id: estoqueItens.value[0]?.id || '',
    quantidade: 1,
  })
}

const removerItemInsumo = (index) => {
  form.itens.splice(index, 1)
}

const getEstoqueDisponivel = (estoqueId) => {
  const item = estoqueItens.value.find(i => i.id == estoqueId)
  return item ? item.quantidade_estoque : 0
}

// Pagamentos no Formulário
const adicionarPagamento = () => {
  const restante = saldoRestantePagamento.value
  form.pagamentos.push({
    forma_pagamento: 'pix',
    valor: restante > 0 ? restante : '',
  })
}

const removerPagamento = (index) => {
  form.pagamentos.splice(index, 1)
}

// Abrir Modal
const abrirModalNovo = (dadosIniciais = null) => {
  form.id = null
  form.cliente_nome = dadosIniciais?.cliente_nome || ''
  form.cliente_contato = dadosIniciais?.cliente_contato || ''
  form.descricao = dadosIniciais?.descricao || ''
  form.valor_servico = dadosIniciais?.valor_servico || ''
  form.taxa_entrega = dadosIniciais?.taxa_entrega || 0
  form.status = 'pendente'
  form.itens = []
  form.pagamentos = [
    { forma_pagamento: 'pix', valor: dadosIniciais?.valor_servico || '' }
  ]
  modalAberto.value = true
}

const abrirModalEditar = (ordem) => {
  form.id = ordem.id
  form.cliente_nome = ordem.cliente_nome
  form.cliente_contato = ordem.cliente_contato || ''
  form.descricao = ordem.descricao
  form.valor_servico = parseFloat(ordem.valor_servico)
  form.taxa_entrega = parseFloat(ordem.taxa_entrega) || 0
  form.status = ordem.status
  form.itens = (ordem.itens || []).map(item => ({
    estoque_id: item.estoque_id,
    quantidade: item.quantidade,
  }))
  form.pagamentos = (ordem.pagamentos || []).map(p => ({
    forma_pagamento: p.forma_pagamento,
    valor: parseFloat(p.valor),
  }))
  if (form.pagamentos.length === 0) {
    form.pagamentos.push({ forma_pagamento: 'pix', valor: ordem.valor_total })
  }
  modalAberto.value = true
}

const fecharModal = () => {
  modalAberto.value = false
}

// Salvar OS
const salvarOrdem = async () => {
  if (!form.cliente_nome || !form.descricao || form.valor_servico === '') {
    exibirAlerta('Preencha os campos obrigatórios (Cliente, Descrição e Valor).', 'erro')
    return
  }

  // Validar se os insumos têm estoque suficiente caso vá concluir
  for (const item of form.itens) {
    if (!item.estoque_id || item.quantidade <= 0) {
      exibirAlerta('Informe insumos e quantidades válidas.', 'erro')
      return
    }
  }

  salvando.value = true
  try {
    const payload = {
      cliente_nome: form.cliente_nome,
      cliente_contato: form.cliente_contato,
      descricao: form.descricao,
      valor_servico: parseFloat(form.valor_servico),
      taxa_entrega: parseFloat(form.taxa_entrega) || 0,
      status: form.status,
      itens: form.itens.map(i => ({ estoque_id: parseInt(i.estoque_id), quantidade: parseInt(i.quantidade) })),
      pagamentos: form.pagamentos
        .filter(p => parseFloat(p.valor) > 0)
        .map(p => ({ forma_pagamento: p.forma_pagamento, valor: parseFloat(p.valor) })),
    }

    if (form.id) {
      await api.put(`/ordens-servico/${form.id}`, payload)
      exibirAlerta('Ordem de Serviço atualizada com sucesso!')
    } else {
      await api.post('/ordens-servico', payload)
      exibirAlerta('Ordem de Serviço criada com sucesso!')
    }

    fecharModal()
    await carregarDados()
  } catch (err) {
    console.error('Erro ao salvar OS:', err)
    const msg = err.response?.data?.message || err.response?.data?.errors?.itens?.[0] || 'Erro ao salvar Ordem de Serviço.'
    exibirAlerta(msg, 'erro')
  } finally {
    salvando.value = false
  }
}

// Alterar Status Rápido
const alterarStatusRapido = async (ordem, novoStatus) => {
  try {
    const payload = {
      cliente_nome: ordem.cliente_nome,
      cliente_contato: ordem.cliente_contato,
      descricao: ordem.descricao,
      valor_servico: parseFloat(ordem.valor_servico),
      taxa_entrega: parseFloat(ordem.taxa_entrega) || 0,
      status: novoStatus,
      itens: (ordem.itens || []).map(i => ({ estoque_id: i.estoque_id, quantidade: i.quantidade })),
      pagamentos: (ordem.pagamentos || []).map(p => ({ forma_pagamento: p.forma_pagamento, valor: parseFloat(p.valor) })),
    }

    await api.put(`/ordens-servico/${ordem.id}`, payload)
    
    if (novoStatus === 'concluido') {
      exibirAlerta(`OS #${ordem.id} finalizada! Insumos debitados do estoque com sucesso.`)
    } else {
      exibirAlerta(`Status da OS #${ordem.id} atualizado para ${getStatusInfo(novoStatus).label}.`)
    }

    await carregarDados()
  } catch (err) {
    console.error('Erro ao atualizar status:', err)
    const msg = err.response?.data?.message || err.response?.data?.errors?.itens?.[0] || 'Erro ao alterar status.'
    exibirAlerta(msg, 'erro')
  }
}

// Confirmar Exclusão
const confirmarExcluir = (ordem) => {
  ordemParaExcluir.value = ordem
  modalExclusaoAberto.value = true
}

const excluirOrdem = async () => {
  if (!ordemParaExcluir.value) return
  try {
    await api.delete(`/ordens-servico/${ordemParaExcluir.value.id}`)
    exibirAlerta(`Ordem #${ordemParaExcluir.value.id} removida com sucesso!`)
    modalExclusaoAberto.value = false
    ordemParaExcluir.value = null
    await carregarDados()
  } catch (err) {
    console.error('Erro ao excluir OS:', err)
    exibirAlerta('Erro ao excluir Ordem de Serviço.', 'erro')
  }
}

// Gerar Texto para WhatsApp
const copiarMensagemWhatsApp = (ordem) => {
  const statusLabel = getStatusInfo(ordem.status).label
  const texto = `Olá *${ordem.cliente_nome}*! 👋\n` +
    `Aqui é da *PrintAdmin - Copiadora & Gráfica*.\n\n` +
    `📋 *Status do seu Pedido (OS #${ordem.id}):* ${statusLabel}\n` +
    `📄 *Descrição:* ${ordem.descricao}\n` +
    `💰 *Valor Total:* ${formatarDinheiro(ordem.valor_total)}\n` +
    (ordem.taxa_entrega > 0 ? `🛵 *Taxa de Entrega:* ${formatarDinheiro(ordem.taxa_entrega)}\n` : '') +
    `\nQualquer dúvida estamos à disposição!`

  navigator.clipboard.writeText(texto)
  exibirAlerta('Mensagem copiada para a área de transferência! Cole no WhatsApp.', 'sucesso')
}

onMounted(() => {
  // Se vier com parâmetros da calculadora pela rota
  if (route.query.descricao && route.query.valor) {
    abrirModalNovo({
      cliente_nome: route.query.cliente || '',
      descricao: route.query.descricao || '',
      valor_servico: parseFloat(route.query.valor) || 0,
      taxa_entrega: 0,
    })
  }
  carregarDados()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header com Título e Botão Nova OS -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
          <span>📋</span>
          <span>Ordens de Serviço (OS)</span>
        </h1>
        <p class="text-sm text-slate-500 mt-1">
          Gerenciamento de pedidos, controle de produção, pagamentos e baixa automática de insumos.
        </p>
      </div>

      <div class="flex items-center gap-3">
        <button
          @click="carregarDados"
          class="p-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 transition shadow-xs cursor-pointer"
          title="Atualizar lista"
        >
          🔄
        </button>
        <button
          @click="abrirModalNovo()"
          class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition shadow-md shadow-blue-600/20 cursor-pointer"
        >
          <span>➕</span>
          <span>Nova Ordem de Serviço</span>
        </button>
      </div>
    </div>

    <!-- Alertas -->
    <div
      v-if="mensagemAlerta.texto"
      :class="[
        'p-4 rounded-xl text-sm font-medium flex items-center justify-between shadow-xs transition-all',
        mensagemAlerta.tipo === 'erro'
          ? 'bg-red-50 text-red-700 border border-red-200'
          : 'bg-emerald-50 text-emerald-700 border border-emerald-200'
      ]"
    >
      <div class="flex items-center gap-2.5">
        <span>{{ mensagemAlerta.tipo === 'erro' ? '⚠️' : '✅' }}</span>
        <span>{{ mensagemAlerta.texto }}</span>
      </div>
      <button @click="mensagemAlerta.texto = ''" class="text-slate-400 hover:text-slate-600">✕</button>
    </div>

    <!-- Cards de Resumo Operacional -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4">
      <div
        @click="filtroStatus = ''"
        :class="[
          'bg-white rounded-2xl p-4 border transition cursor-pointer',
          filtroStatus === '' ? 'border-blue-500 ring-2 ring-blue-500/20 shadow-sm' : 'border-slate-200 hover:border-slate-300'
        ]"
      >
        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Todas as OS</span>
        <span class="text-2xl font-bold text-slate-900 mt-1 block">{{ resumoContadores.total }}</span>
      </div>

      <div
        @click="filtroStatus = 'pendente'"
        :class="[
          'bg-white rounded-2xl p-4 border transition cursor-pointer',
          filtroStatus === 'pendente' ? 'border-amber-500 ring-2 ring-amber-500/20 shadow-sm' : 'border-slate-200 hover:border-slate-300'
        ]"
      >
        <span class="text-xs font-semibold text-amber-600 uppercase tracking-wider block">Pendentes / Fila</span>
        <span class="text-2xl font-bold text-amber-700 mt-1 block">{{ resumoContadores.pendentes }}</span>
      </div>

      <div
        @click="filtroStatus = 'aguardando_pagamento'"
        :class="[
          'bg-white rounded-2xl p-4 border transition cursor-pointer',
          filtroStatus === 'aguardando_pagamento' ? 'border-orange-500 ring-2 ring-orange-500/20 shadow-sm' : 'border-slate-200 hover:border-slate-300'
        ]"
      >
        <span class="text-xs font-semibold text-orange-600 uppercase tracking-wider block">Aguard. Pagamento</span>
        <span class="text-2xl font-bold text-orange-700 mt-1 block">{{ resumoContadores.aguardando_pag }}</span>
      </div>

      <div
        @click="filtroStatus = 'pronto_retirada'"
        :class="[
          'bg-white rounded-2xl p-4 border transition cursor-pointer',
          filtroStatus === 'pronto_retirada' ? 'border-purple-500 ring-2 ring-purple-500/20 shadow-sm' : 'border-slate-200 hover:border-slate-300'
        ]"
      >
        <span class="text-xs font-semibold text-purple-600 uppercase tracking-wider block">Retirada / Entrega</span>
        <span class="text-2xl font-bold text-purple-700 mt-1 block">{{ resumoContadores.prontos }}</span>
      </div>

      <div
        @click="filtroStatus = 'concluido'"
        :class="[
          'bg-white rounded-2xl p-4 border transition cursor-pointer',
          filtroStatus === 'concluido' ? 'border-emerald-500 ring-2 ring-emerald-500/20 shadow-sm' : 'border-slate-200 hover:border-slate-300'
        ]"
      >
        <span class="text-xs font-semibold text-emerald-600 uppercase tracking-wider block">Concluídas</span>
        <span class="text-2xl font-bold text-emerald-700 mt-1 block">{{ resumoContadores.concluidos }}</span>
      </div>
    </div>

    <!-- Barra de Filtros & Busca -->
    <div class="bg-white rounded-2xl p-4 shadow-xs border border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div class="flex-1 relative">
        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">🔍</span>
        <input
          v-model="filtroBusca"
          type="text"
          placeholder="Buscar por cliente, WhatsApp ou descrição do serviço..."
          class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>

      <div class="flex items-center gap-3">
        <select
          v-model="filtroStatus"
          class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer"
        >
          <option value="">Todos os Status</option>
          <option v-for="st in statusOpcoes" :key="st.valor" :value="st.valor">
            {{ st.label }}
          </option>
        </select>
      </div>
    </div>

    <!-- Tabela / Lista de Ordens de Serviço -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200 overflow-hidden">
      <div v-if="loading" class="p-12 text-center text-slate-400">
        <span class="animate-spin inline-block text-2xl mb-2">⏳</span>
        <p>Carregando ordens de serviço...</p>
      </div>

      <div v-else-if="ordensFiltradas.length === 0" class="p-12 text-center text-slate-400">
        <span class="text-4xl block mb-2">📂</span>
        <p class="font-medium text-slate-600">Nenhuma Ordem de Serviço encontrada.</p>
        <p class="text-xs text-slate-400 mt-1">Clique em "Nova Ordem de Serviço" para registrar um pedido.</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100 text-left">
          <thead class="bg-slate-50 text-xs font-semibold text-slate-500 uppercase tracking-wider">
            <tr>
              <th class="px-5 py-3.5">OS / Data</th>
              <th class="px-5 py-3.5">Cliente</th>
              <th class="px-5 py-3.5">Descrição do Serviço</th>
              <th class="px-5 py-3.5">Insumos (Estoque)</th>
              <th class="px-5 py-3.5">Pagamento</th>
              <th class="px-5 py-3.5 text-right">Valor Total</th>
              <th class="px-5 py-3.5 text-center">Status</th>
              <th class="px-5 py-3.5 text-right">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-sm">
            <tr
              v-for="ordem in ordensFiltradas"
              :key="ordem.id"
              class="hover:bg-slate-50/70 transition"
            >
              <!-- ID e Data -->
              <td class="px-5 py-4 align-top">
                <span class="font-mono font-bold text-slate-800 text-sm block">#OS-{{ String(ordem.id).padStart(4, '0') }}</span>
                <span class="text-xs text-slate-400 block mt-0.5">{{ formatarData(ordem.created_at) }}</span>
              </td>

              <!-- Cliente -->
              <td class="px-5 py-4 align-top">
                <span class="font-semibold text-slate-900 block">{{ ordem.cliente_nome }}</span>
                <span v-if="ordem.cliente_contato" class="text-xs text-blue-600 font-mono block mt-0.5">
                  📱 {{ ordem.cliente_contato }}
                </span>
              </td>

              <!-- Descrição -->
              <td class="px-5 py-4 align-top max-w-xs">
                <p class="text-slate-700 text-xs line-clamp-2 leading-relaxed whitespace-pre-line">{{ ordem.descricao }}</p>
                <div v-if="ordem.taxa_entrega > 0" class="mt-1">
                  <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-cyan-700 bg-cyan-50 px-2 py-0.5 rounded-md border border-cyan-200">
                    🛵 Entrega (+{{ formatarDinheiro(ordem.taxa_entrega) }})
                  </span>
                </div>
              </td>

              <!-- Insumos Gastos -->
              <td class="px-5 py-4 align-top">
                <div v-if="ordem.itens && ordem.itens.length > 0" class="space-y-1">
                  <div
                    v-for="item in ordem.itens"
                    :key="item.id"
                    class="text-xs text-slate-600 flex items-center gap-1.5"
                  >
                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                    <span class="font-medium text-slate-800">{{ item.quantidade }}x</span>
                    <span class="truncate max-w-[140px]">{{ item.estoque?.nome || 'Insumo' }}</span>
                  </div>
                  <span
                    v-if="ordem.estoque_baixado"
                    class="inline-block text-[10px] font-bold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded"
                  >
                    ✓ Baixa efetuada
                  </span>
                </div>
                <span v-else class="text-xs text-slate-400 italic">Sem insumos vinculados</span>
              </td>

              <!-- Pagamento -->
              <td class="px-5 py-4 align-top">
                <div v-if="ordem.pagamentos && ordem.pagamentos.length > 0" class="space-y-1">
                  <div
                    v-for="pag in ordem.pagamentos"
                    :key="pag.id"
                    class="text-xs flex items-center justify-between gap-2"
                  >
                    <span class="text-slate-500 uppercase text-[10px] font-semibold">
                      {{ pag.forma_pagamento.replace('_', ' ') }}:
                    </span>
                    <span class="font-mono font-medium text-slate-800">{{ formatarDinheiro(pag.valor) }}</span>
                  </div>
                </div>
                <span v-else class="text-xs text-slate-400 italic">Não informado</span>
              </td>

              <!-- Valor Total -->
              <td class="px-5 py-4 align-top text-right">
                <span class="font-bold text-slate-900 text-sm block">{{ formatarDinheiro(ordem.valor_total) }}</span>
                <span class="text-[10px] text-slate-400 block">Serviço: {{ formatarDinheiro(ordem.valor_servico) }}</span>
              </td>

              <!-- Status -->
              <td class="px-5 py-4 align-top text-center">
                <span
                  :class="[
                    'inline-block px-2.5 py-1 rounded-full text-xs font-semibold border',
                    getStatusInfo(ordem.status).badgeClass
                  ]"
                >
                  {{ getStatusInfo(ordem.status).label }}
                </span>

                <!-- Atalhos rápidos de mudança de status -->
                <div class="flex items-center justify-center gap-1 mt-2">
                  <button
                    v-if="ordem.status === 'pendente'"
                    @click="alterarStatusRapido(ordem, 'em_producao')"
                    class="p-1 rounded bg-blue-50 text-blue-700 hover:bg-blue-100 text-[10px] font-semibold transition"
                    title="Mudar para Em Produção"
                  >
                    ⚙️ Produzir
                  </button>

                  <button
                    v-if="ordem.status === 'em_producao'"
                    @click="alterarStatusRapido(ordem, 'pronto_retirada')"
                    class="p-1 rounded bg-purple-50 text-purple-700 hover:bg-purple-100 text-[10px] font-semibold transition"
                    title="Mudar para Pronto p/ Retirada"
                  >
                    📦 Pronto
                  </button>

                  <button
                    v-if="ordem.status !== 'concluido' && ordem.status !== 'cancelado'"
                    @click="alterarStatusRapido(ordem, 'concluido')"
                    class="p-1 rounded bg-emerald-50 text-emerald-700 hover:bg-emerald-100 text-[10px] font-semibold transition"
                    title="Concluir e Baixar Estoque"
                  >
                    ✅ Concluir
                  </button>
                </div>
              </td>

              <!-- Ações -->
              <td class="px-5 py-4 align-top text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <button
                    @click="copiarMensagemWhatsApp(ordem)"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition cursor-pointer"
                    title="Copiar mensagem para WhatsApp"
                  >
                    📲
                  </button>
                  <button
                    @click="abrirModalEditar(ordem)"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition cursor-pointer"
                    title="Editar OS"
                  >
                    ✏️
                  </button>
                  <button
                    @click="confirmarExcluir(ordem)"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition cursor-pointer"
                    title="Excluir OS"
                  >
                    🗑️
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- MODAL DE CADASTRO / EDIÇÃO DA ORDEM DE SERVIÇO -->
    <div
      v-if="modalAberto"
      class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6"
    >
      <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto shadow-2xl border border-slate-200">
        <!-- Topo do Modal -->
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white z-10">
          <h3 class="font-bold text-lg text-slate-900 flex items-center gap-2">
            <span>{{ form.id ? '✏️ Editar Ordem de Serviço' : '➕ Nova Ordem de Serviço' }}</span>
          </h3>
          <button @click="fecharModal" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100">
            ✕
          </button>
        </div>

        <form @submit.prevent="salvarOrdem" class="p-6 space-y-5">
          <!-- Dados do Cliente -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                Nome do Cliente *
              </label>
              <input
                v-model="form.cliente_nome"
                type="text"
                required
                placeholder="Ex: Carlos Silva"
                class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                WhatsApp / Contato
              </label>
              <input
                v-model="form.cliente_contato"
                type="text"
                placeholder="Ex: (11) 98765-4321"
                class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>

          <!-- Descrição do Serviço por Extenso -->
          <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
              Descrição do Serviço por Extenso *
            </label>
            <textarea
              v-model="form.descricao"
              required
              rows="3"
              placeholder="Ex: 3 apostilas preto e branco em papel A4 encadernadas com capa azul e espiral 17mm..."
              class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            ></textarea>
          </div>

          <!-- Valores & Status -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200">
            <div>
              <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                Valor do Serviço (R$) *
              </label>
              <input
                v-model.number="form.valor_servico"
                type="number"
                step="0.01"
                min="0"
                required
                placeholder="0.00"
                class="w-full px-3.5 py-2 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                Taxa de Entrega (R$)
              </label>
              <input
                v-model.number="form.taxa_entrega"
                type="number"
                step="0.01"
                min="0"
                placeholder="0.00"
                class="w-full px-3.5 py-2 bg-white border border-slate-200 rounded-xl text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                Status da OS *
              </label>
              <select
                v-model="form.status"
                class="w-full px-3.5 py-2 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer"
              >
                <option v-for="st in statusOpcoes" :key="st.valor" :value="st.valor">
                  {{ st.label }}
                </option>
              </select>
            </div>
          </div>

          <!-- Totalizador -->
          <div class="flex items-center justify-between px-2 text-sm">
            <span class="font-medium text-slate-600">Valor Total da OS:</span>
            <span class="font-extrabold text-lg text-blue-600">{{ formatarDinheiro(valorTotalCalculado) }}</span>
          </div>

          <!-- SEÇÃO: INSUMOS DO ESTOQUE UTILIZADOS -->
          <div class="border-t border-slate-200 pt-4">
            <div class="flex items-center justify-between mb-3">
              <div>
                <h4 class="font-bold text-sm text-slate-800 flex items-center gap-1.5">
                  <span>📦</span>
                  <span>Insumos do Estoque Utilizados</span>
                </h4>
                <p class="text-xs text-slate-400">Ao concluir a OS, esses itens serão debitados automaticamente.</p>
              </div>
              <button
                type="button"
                @click="adicionarItemInsumo"
                class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition cursor-pointer"
              >
                + Adicionar Insumo
              </button>
            </div>

            <div v-if="form.itens.length === 0" class="text-xs text-slate-400 bg-slate-50 p-3 rounded-xl border border-dashed border-slate-200 text-center">
              Nenhum insumo adicionado. (Opcional, mas necessário para controle automático de estoque).
            </div>

            <div v-else class="space-y-2.5">
              <div
                v-for="(item, index) in form.itens"
                :key="index"
                class="flex items-center gap-3 bg-slate-50 p-2.5 rounded-xl border border-slate-200"
              >
                <div class="flex-1">
                  <select
                    v-model="item.estoque_id"
                    required
                    class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="" disabled>Selecione um item do estoque</option>
                    <option v-for="est in estoqueItens" :key="est.id" :value="est.id">
                      {{ est.nome }} (Disponível: {{ est.quantidade_estoque }})
                    </option>
                  </select>
                </div>

                <div class="w-24">
                  <input
                    v-model.number="item.quantidade"
                    type="number"
                    min="1"
                    required
                    placeholder="Qtd"
                    class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-center font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>

                <button
                  type="button"
                  @click="removerItemInsumo(index)"
                  class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition"
                  title="Remover insumo"
                >
                  ✕
                </button>
              </div>
            </div>
          </div>

          <!-- SEÇÃO: FORMAS DE PAGAMENTO -->
          <div class="border-t border-slate-200 pt-4">
            <div class="flex items-center justify-between mb-3">
              <div>
                <h4 class="font-bold text-sm text-slate-800 flex items-center gap-1.5">
                  <span>💵</span>
                  <span>Formas de Pagamento</span>
                </h4>
                <p class="text-xs text-slate-400">Divida o valor entre múltiplos meios se necessário.</p>
              </div>
              <button
                type="button"
                @click="adicionarPagamento"
                class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition cursor-pointer"
              >
                + Adicionar Pagamento
              </button>
            </div>

            <div class="space-y-2.5">
              <div
                v-for="(pag, index) in form.pagamentos"
                :key="index"
                class="flex items-center gap-3 bg-slate-50 p-2.5 rounded-xl border border-slate-200"
              >
                <div class="flex-1">
                  <select
                    v-model="pag.forma_pagamento"
                    required
                    class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option v-for="fp in formasPagamentoOpcoes" :key="fp.valor" :value="fp.valor">
                      {{ fp.label }}
                    </option>
                  </select>
                </div>

                <div class="w-32">
                  <input
                    v-model.number="pag.valor"
                    type="number"
                    step="0.01"
                    min="0"
                    required
                    placeholder="Valor (R$)"
                    class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-right font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>

                <button
                  type="button"
                  @click="removerPagamento(index)"
                  class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition"
                  title="Remover forma"
                >
                  ✕
                </button>
              </div>
            </div>

            <!-- Resumo do Pagamento -->
            <div class="mt-3 p-3 rounded-xl bg-slate-100/80 flex items-center justify-between text-xs">
              <span class="text-slate-600">Total Pago: <strong class="text-slate-900">{{ formatarDinheiro(totalPagoCalculado) }}</strong></span>
              <span :class="saldoRestantePagamento > 0 ? 'text-amber-700 font-bold' : 'text-emerald-700 font-bold'">
                {{ saldoRestantePagamento > 0 ? `Restante: ${formatarDinheiro(saldoRestantePagamento)}` : '✓ 100% Integralizado' }}
              </span>
            </div>
          </div>

          <!-- Botões de Ação do Modal -->
          <div class="border-t border-slate-200 pt-4 flex items-center justify-end gap-3">
            <button
              type="button"
              @click="fecharModal"
              class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-sm font-medium transition cursor-pointer"
            >
              Cancelar
            </button>
            <button
              type="submit"
              :disabled="salvando"
              class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white text-sm font-semibold transition shadow-md shadow-blue-600/20 cursor-pointer flex items-center gap-2"
            >
              <span v-if="salvando" class="animate-spin">⏳</span>
              <span>{{ salvando ? 'Salvando...' : (form.id ? 'Salvar Alterações' : 'Criar Ordem de Serviço') }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL DE CONFIRMAÇÃO DE EXCLUSÃO -->
    <div
      v-if="modalExclusaoAberto"
      class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
    >
      <div class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-slate-200 text-center space-y-4">
        <div class="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center text-xl mx-auto">
          🗑️
        </div>
        <div>
          <h4 class="font-bold text-slate-900 text-base">Excluir Ordem de Serviço?</h4>
          <p class="text-xs text-slate-500 mt-1">
            Tem certeza que deseja remover a OS #{{ ordemParaExcluir?.id }}? Se os insumos já foram baixados, o saldo será estornado ao estoque.
          </p>
        </div>
        <div class="flex items-center justify-center gap-3 pt-2">
          <button
            @click="modalExclusaoAberto = false"
            class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-semibold cursor-pointer"
          >
            Cancelar
          </button>
          <button
            @click="excluirOrdem"
            class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-semibold transition shadow-md shadow-red-600/20 cursor-pointer"
          >
            Confirmar Exclusão
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
