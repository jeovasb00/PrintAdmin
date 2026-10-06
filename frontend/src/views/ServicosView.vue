<script setup>
import { ref, reactive, computed } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

// 1. ESTADO DA CALCULADORA
const calc = reactive({
  clienteNome: '',
  quantidade: 50,
  tipoCor: 'pb', // 'pb' ou 'color'
  formato: 'a4', // 'a4', 'a3', 'a5'
  tipoPapel: 'sulfite_75',
  frenteVerso: false, // true = desconto de R$ 0,10/pág por economizar folha
  acabamento: 'nenhum', // 'nenhum', 'encadernacao_p', 'encadernacao_g', 'plastificacao', 'grampo'
  qtdAcabamento: 1,
  observacaoExtra: '',
})

const copiadoAlerta = ref(false)

// Tabelas de Preço Base e Desconto Progressivo por Página
const regrasPreco = {
  pb: {
    nome: 'Preto & Branco (P&B)',
    faixas: [
      { min: 1, max: 50, preco: 0.50 },
      { min: 51, max: 200, preco: 0.35 },
      { min: 201, max: Infinity, preco: 0.20 },
    ]
  },
  color: {
    nome: 'Colorida',
    faixas: [
      { min: 1, max: 20, preco: 2.00 },
      { min: 21, max: 100, preco: 1.50 },
      { min: 101, max: Infinity, preco: 1.00 },
    ]
  }
}

// Multiplicador/Adicional por Tipo de Papel
const tiposPapel = [
  { valor: 'sulfite_75', label: 'Sulfite Comum 75g', adicional: 0.00 },
  { valor: 'sulfite_90', label: 'Sulfite Especial 90g', adicional: 0.10 },
  { valor: 'couche_180', label: 'Couché Brilho/Fosco 180g', adicional: 0.80 },
  { valor: 'kraft_180', label: 'Papel Kraft 180g', adicional: 0.50 },
  { valor: 'fotografico', label: 'Papel Fotográfico Glossy', adicional: 1.50 },
]

// Formatos
const formatosPapel = [
  { valor: 'a4', label: 'Formato A4 (Padrão)', mult: 1.0 },
  { valor: 'a3', label: 'Formato A3 (Dobro)', mult: 2.0 },
  { valor: 'a5', label: 'Formato A5 (Metade)', mult: 0.75 },
]

// Acabamentos
const opcoesAcabamento = [
  { valor: 'nenhum', label: 'Nenhum acabamento', preco: 0 },
  { valor: 'encadernacao_p', label: 'Encadernação Espiral (até 100 fls)', preco: 6.00 },
  { valor: 'encadernacao_g', label: 'Encadernação Espiral (101 a 250 fls)', preco: 9.00 },
  { valor: 'plastificacao', label: 'Plastificação Polaseal A4', preco: 4.50 },
  { valor: 'grampo', label: 'Grampo / Dobra Revista', preco: 2.00 },
]

// 2. COMPUTADAS DE CÁLCULO
const precoBaseSemDescontoFV = computed(() => {
  const qtd = parseInt(calc.quantidade) || 1
  const regra = regrasPreco[calc.tipoCor]
  const faixa = regra.faixas.find(f => qtd >= f.min && qtd <= f.max) || regra.faixas[0]
  
  const formatoInfo = formatosPapel.find(f => f.valor === calc.formato)
  const papelInfo = tiposPapel.find(p => p.valor === calc.tipoPapel)

  const precoBase = faixa.preco * (formatoInfo ? formatoInfo.mult : 1.0)
  return precoBase + (papelInfo ? papelInfo.adicional : 0)
})

const precoUnitarioPagina = computed(() => {
  const descontoFV = calc.frenteVerso ? 0.10 : 0.00
  return Math.max(0.10, precoBaseSemDescontoFV.value - descontoFV)
})

const totalEconomiaFrenteVerso = computed(() => {
  if (!calc.frenteVerso) return 0
  const qtd = parseInt(calc.quantidade) || 0
  return qtd * 0.10
})

const valorTotalImpressoes = computed(() => {
  const qtd = parseInt(calc.quantidade) || 0
  return qtd * precoUnitarioPagina.value
})

const valorTotalAcabamentos = computed(() => {
  const acab = opcoesAcabamento.find(a => a.valor === calc.acabamento)
  if (!acab || acab.preco === 0) return 0
  const qtd = parseInt(calc.qtdAcabamento) || 1
  return acab.preco * qtd
})

const valorTotalOrcamento = computed(() => {
  return valorTotalImpressoes.value + valorTotalAcabamentos.value
})

const formatarDinheiro = (val) => {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(val || 0)
}

// 3. MÉTODOS DE AÇÃO
const copiarOrcamentoWhatsApp = () => {
  const papelNome = tiposPapel.find(p => p.valor === calc.tipoPapel)?.label || ''
  const formatoNome = formatosPapel.find(f => f.valor === calc.formato)?.label || ''
  const acabNome = opcoesAcabamento.find(a => a.valor === calc.acabamento)?.label || ''
  const modoFV = calc.frenteVerso ? '📑 Frente e Verso (Desconto de R$ 0,10/pág por economia de papel)' : '📄 Só Frente'

  let texto = `Olá${calc.clienteNome ? ` *${calc.clienteNome}*` : ''}! Segue o orçamento solicitado: 🖨️✨\n\n`
  texto += `📄 *Serviço:* ${calc.quantidade}x Impressão/Cópia (${regrasPreco[calc.tipoCor].nome})\n`
  texto += `📐 *Papel:* ${formatoNome} &bull; ${papelNome}\n`
  texto += `📑 *Modo:* ${modoFV}\n`
  texto += `💵 *Valor das Impressões:* ${formatarDinheiro(valorTotalImpressoes.value)} (${formatarDinheiro(precoUnitarioPagina.value)}/pág)\n`
  
  if (calc.frenteVerso) {
    texto += `💚 *Economia Frente e Verso:* -${formatarDinheiro(totalEconomiaFrenteVerso.value)}\n`
  }

  if (calc.acabamento !== 'nenhum') {
    texto += `📎 *Acabamento:* ${calc.qtdAcabamento}x ${acabNome} = ${formatarDinheiro(valorTotalAcabamentos.value)}\n`
  }

  if (calc.observacaoExtra) {
    texto += `📝 *Obs:* ${calc.observacaoExtra}\n`
  }

  texto += `\n💰 *VALOR TOTAL:* *${formatarDinheiro(valorTotalOrcamento.value)}*\n\n`
  texto += `Ficamos à disposição para realizar o seu pedido!`

  navigator.clipboard.writeText(texto)
  copiadoAlerta.value = true
  setTimeout(() => {
    copiadoAlerta.value = false
  }, 3500)
}

const gerarOrdemServico = () => {
  const papelNome = tiposPapel.find(p => p.valor === calc.tipoPapel)?.label || ''
  const corNome = regrasPreco[calc.tipoCor].nome
  const modoFV = calc.frenteVerso ? ' [Frente e Verso]' : ' [Só Frente]'
  const acabNome = calc.acabamento !== 'nenhum' ? ` + ${calc.qtdAcabamento}x ${opcoesAcabamento.find(a => a.valor === calc.acabamento)?.label}` : ''
  
  const descricao = `${calc.quantidade}x Impressão ${corNome}${modoFV} em ${papelNome}${acabNome}${calc.observacaoExtra ? ` (${calc.observacaoExtra})` : ''}`

  router.push({
    path: '/ordens-servico',
    query: {
      cliente: calc.clienteNome,
      descricao: descricao,
      valor: valorTotalOrcamento.value.toFixed(2),
    }
  })
}
</script>

<template>
  <div class="space-y-8">
    <!-- Topo / Cabeçalho -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
          <span>🖩</span>
          <span>Calculadora Rápida de Orçamentos</span>
        </h1>
        <p class="text-sm text-slate-500 mt-1">
          Calcule preços na hora com descontos progressivos por volume e envie orçamentos para o WhatsApp.
        </p>
      </div>

      <!-- Alerta Flutuante de Cópia -->
      <div
        v-if="copiadoAlerta"
        class="bg-emerald-600 text-white text-xs font-semibold px-4 py-2 rounded-xl shadow-lg flex items-center gap-2 animate-bounce"
      >
        <span>✅</span>
        <span>Orçamento copiado para a área de transferência!</span>
      </div>
    </div>

    <!-- ÁREA PRINCIPAL: CALCULADORA + RESUMO DO ORÇAMENTO -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
      <!-- Formulário de Parâmetros (2 Colunas) -->
      <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-xs border border-slate-200 space-y-6">
        <h2 class="text-base font-bold text-slate-900 flex items-center gap-2 pb-3 border-b border-slate-100">
          <span>⚙️</span>
          <span>Parâmetros do Pedido</span>
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <!-- Nome do Cliente (Opcional) -->
          <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
              Nome do Cliente (Opcional)
            </label>
            <input
              v-model="calc.clienteNome"
              type="text"
              placeholder="Ex: Dra. Mariana"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <!-- Quantidade de Páginas / Cópias -->
          <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
              Quantidade de Páginas/Cópias *
            </label>
            <input
              v-model.number="calc.quantidade"
              type="number"
              min="1"
              required
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
        </div>

        <!-- Seletor Tipo de Cor (P&B ou Colorida) -->
        <div>
          <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">
            Tipo de Impressão
          </label>
          <div class="grid grid-cols-2 gap-3">
            <button
              type="button"
              @click="calc.tipoCor = 'pb'"
              :class="[
                'p-3.5 rounded-xl border text-left transition flex items-center justify-between cursor-pointer',
                calc.tipoCor === 'pb'
                  ? 'bg-slate-900 text-white border-slate-900 shadow-sm'
                  : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'
              ]"
            >
              <div>
                <span class="font-bold text-sm block">⚫ Preto e Branco</span>
                <span class="text-xs opacity-80 block mt-0.5">A partir de R$ 0,20/pág</span>
              </div>
              <span v-if="calc.tipoCor === 'pb'">✓</span>
            </button>

            <button
              type="button"
              @click="calc.tipoCor = 'color'"
              :class="[
                'p-3.5 rounded-xl border text-left transition flex items-center justify-between cursor-pointer',
                calc.tipoCor === 'color'
                  ? 'bg-blue-600 text-white border-blue-600 shadow-sm'
                  : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'
              ]"
            >
              <div>
                <span class="font-bold text-sm block">🎨 Colorida</span>
                <span class="text-xs opacity-80 block mt-0.5">A partir de R$ 1,00/pág</span>
              </div>
              <span v-if="calc.tipoCor === 'color'">✓</span>
            </button>
          </div>
        </div>

        <!-- SELETOR: SÓ FRENTE vs FRENTE E VERSO (COM DESCONTO DE R$ 0,10) -->
        <div>
          <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">
            Modo de Impressão (Faces da Folha)
          </label>
          <div class="grid grid-cols-2 gap-3">
            <button
              type="button"
              @click="calc.frenteVerso = false"
              :class="[
                'p-3.5 rounded-xl border text-left transition flex items-center justify-between cursor-pointer',
                !calc.frenteVerso
                  ? 'bg-slate-800 text-white border-slate-800 shadow-sm'
                  : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'
              ]"
            >
              <div>
                <span class="font-bold text-sm block">📄 Só Frente</span>
                <span class="text-xs opacity-80 block mt-0.5">1 página por folha</span>
              </div>
              <span v-if="!calc.frenteVerso">✓</span>
            </button>

            <button
              type="button"
              @click="calc.frenteVerso = true"
              :class="[
                'p-3.5 rounded-xl border text-left transition flex items-center justify-between cursor-pointer',
                calc.frenteVerso
                  ? 'bg-emerald-700 text-white border-emerald-700 shadow-sm'
                  : 'bg-emerald-50/60 text-emerald-900 border-emerald-200 hover:bg-emerald-100'
              ]"
            >
              <div>
                <div class="flex items-center gap-1.5">
                  <span class="font-bold text-sm block">📑 Frente e Verso</span>
                  <span class="text-[10px] font-extrabold bg-white/20 text-white px-1.5 py-0.2 rounded">
                    -R$ 0,10/pág
                  </span>
                </div>
                <span :class="['text-xs block mt-0.5', calc.frenteVerso ? 'text-emerald-100' : 'text-emerald-700']">
                  Economia de papel aplicada
                </span>
              </div>
              <span v-if="calc.frenteVerso">✓</span>
            </button>
          </div>
        </div>

        <!-- Formato e Papel -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
              Formato do Papel
            </label>
            <select
              v-model="calc.formato"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer"
            >
              <option v-for="fmt in formatosPapel" :key="fmt.valor" :value="fmt.valor">
                {{ fmt.label }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
              Tipo de Papel / Gramatura
            </label>
            <select
              v-model="calc.tipoPapel"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer"
            >
              <option v-for="tp in tiposPapel" :key="tp.valor" :value="tp.valor">
                {{ tp.label }} {{ tp.adicional > 0 ? `(+${formatarDinheiro(tp.adicional)})` : '' }}
              </option>
            </select>
          </div>
        </div>

        <!-- Acabamento Adicional -->
        <div class="border-t border-slate-100 pt-4">
          <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">
            Acabamentos / Serviços Extras
          </label>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="sm:col-span-2">
              <select
                v-model="calc.acabamento"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer"
              >
                <option v-for="ac in opcoesAcabamento" :key="ac.valor" :value="ac.valor">
                  {{ ac.label }} {{ ac.preco > 0 ? `(${formatarDinheiro(ac.preco)}/un)` : '' }}
                </option>
              </select>
            </div>

            <div v-if="calc.acabamento !== 'nenhum'">
              <input
                v-model.number="calc.qtdAcabamento"
                type="number"
                min="1"
                placeholder="Qtd acabamentos"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-center font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>
        </div>

        <!-- Observações Extras -->
        <div>
          <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
            Observação no Orçamento
          </label>
          <input
            v-model="calc.observacaoExtra"
            type="text"
            placeholder="Ex: Capa plástica transparente na frente e preta atrás..."
            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
      </div>

      <!-- Card Lateral de Resumo de Preços & Ações Rápidas -->
      <div class="bg-gradient-to-b from-slate-900 to-slate-950 text-white rounded-2xl p-6 shadow-xl border border-slate-800 space-y-6 sticky top-20">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 pb-3 border-b border-slate-800 flex items-center justify-between">
          <span>Resumo do Orçamento</span>
          <span class="text-xs bg-blue-600/30 text-blue-400 border border-blue-500/30 px-2 py-0.5 rounded-full font-mono">
            Desconto Ativo
          </span>
        </h3>

        <!-- Detalhamento de Valores -->
        <div class="space-y-3 text-sm">
          <div class="flex items-center justify-between text-slate-300">
            <span>Preço por Página:</span>
            <span class="font-mono font-bold text-white">{{ formatarDinheiro(precoUnitarioPagina) }}</span>
          </div>

          <div class="flex items-center justify-between text-slate-300">
            <span>Subtotal Impressões ({{ calc.quantidade }}x):</span>
            <span class="font-mono font-bold text-white">{{ formatarDinheiro(valorTotalImpressoes) }}</span>
          </div>

          <!-- Destaque do Desconto Frente e Verso -->
          <div v-if="calc.frenteVerso" class="flex items-center justify-between text-400 font-semibold bg-emerald-950/40 p-2 rounded-lg border border-emerald-800/40 text-xs">
            <span>🌱 Desconto Frente e Verso:</span>
            <span class="font-mono font-bold">-{{ formatarDinheiro(totalEconomiaFrenteVerso) }}</span>
          </div>

          <div v-if="calc.acabamento !== 'nenhum'" class="flex items-center justify-between text-slate-300">
            <span>Acabamento ({{ calc.qtdAcabamento }}x):</span>
            <span class="font-mono font-bold text-blue-400">+{{ formatarDinheiro(valorTotalAcabamentos) }}</span>
          </div>
        </div>

        <!-- Grande Totalizador -->
        <div class="p-4 rounded-xl bg-slate-800/80 border border-slate-700/60 text-center">
          <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 block">Total a Cobrar</span>
          <span class="text-3xl sm:text-4xl font-extrabold text-emerald-400 tracking-tight block mt-1">
            {{ formatarDinheiro(valorTotalOrcamento) }}
          </span>
        </div>

        <!-- Botões de Ação Rápida -->
        <div class="space-y-2.5 pt-2">
          <button
            type="button"
            @click="copiarOrcamentoWhatsApp"
            class="w-full py-3 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm transition shadow-lg shadow-emerald-600/20 flex items-center justify-center gap-2 cursor-pointer"
          >
            <span>📱</span>
            <span>Copiar p/ WhatsApp</span>
          </button>

          <button
            type="button"
            @click="gerarOrdemServico"
            class="w-full py-2.5 px-4 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-semibold text-sm transition shadow-md shadow-blue-600/20 flex items-center justify-center gap-2 cursor-pointer"
          >
            <span>📋</span>
            <span>Criar Ordem de Serviço</span>
          </button>
        </div>
      </div>
    </div>

    <!-- TABELA DE DESCONTOS PROGRESSIVOS PARA CONSULTA RÁPIDA -->
    <div class="bg-white rounded-2xl p-6 shadow-xs border border-slate-200 space-y-4">
      <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
        <span>📊</span>
        <span>Tabela Referencial de Desconto Progressivo por Volume</span>
      </h3>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs sm:text-sm">
        <!-- Tabela P&B -->
        <div class="rounded-xl border border-slate-200 p-4 bg-slate-50/50">
          <div class="flex items-center justify-between mb-3">
            <h4 class="font-bold text-slate-800 flex items-center gap-2">
              <span>⚫</span>
              <span>Impressões Preto &amp; Branco (A4 Sulfite 75g)</span>
            </h4>
            <span class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
              Frente/Verso: -R$ 0,10/pág
            </span>
          </div>
          <ul class="space-y-2 text-slate-600">
            <li class="flex justify-between py-1 border-b border-slate-200/60">
              <span>1 a 50 páginas:</span>
              <strong class="text-slate-900">R$ 0,50 / pág (F/V: R$ 0,40)</strong>
            </li>
            <li class="flex justify-between py-1 border-b border-slate-200/60">
              <span>51 a 200 páginas:</span>
              <strong class="text-emerald-700">R$ 0,35 / pág (F/V: R$ 0,25)</strong>
            </li>
            <li class="flex justify-between py-1">
              <span>Acima de 200 páginas:</span>
              <strong class="text-emerald-700">R$ 0,20 / pág (F/V: R$ 0,10)</strong>
            </li>
          </ul>
        </div>

        <!-- Tabela Colorida -->
        <div class="rounded-xl border border-slate-200 p-4 bg-slate-50/50">
          <div class="flex items-center justify-between mb-3">
            <h4 class="font-bold text-slate-800 flex items-center gap-2">
              <span>🎨</span>
              <span>Impressões Coloridas (A4 Sulfite 75g)</span>
            </h4>
            <span class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
              Frente/Verso: -R$ 0,10/pág
            </span>
          </div>
          <ul class="space-y-2 text-slate-600">
            <li class="flex justify-between py-1 border-b border-slate-200/60">
              <span>1 a 20 páginas:</span>
              <strong class="text-slate-900">R$ 2,00 / pág (F/V: R$ 1,90)</strong>
            </li>
            <li class="flex justify-between py-1 border-b border-slate-200/60">
              <span>21 a 100 páginas:</span>
              <strong class="text-emerald-700">R$ 1,50 / pág (F/V: R$ 1,40)</strong>
            </li>
            <li class="flex justify-between py-1">
              <span>Acima de 100 páginas:</span>
              <strong class="text-emerald-700">R$ 1,00 / pág (F/V: R$ 0,90)</strong>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>
