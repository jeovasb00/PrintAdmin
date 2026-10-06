<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import api from '../services/api'

// 1. ESTADO REATIVO (Model / Dados)
const itens = ref([])
const loading = ref(false)
const mensagem = ref({ texto: '', tipo: '' })
const filtroBusca = ref('')

// Formulário para cadastro/edição
const form = reactive({
  id: null,
  nome: '',
  quantidade_estoque: ''
})

// 2. PROPRIEDADES COMPUTADAS
const totalItens = computed(() => itens.value.length)
const totalEstoqueGeral = computed(() =>
  itens.value.reduce((acc, item) => acc + Number(item.quantidade_estoque || 0), 0)
)
const itensEstoqueBaixo = computed(() =>
  itens.value.filter((item) => Number(item.quantidade_estoque) <= 5).length
)
const itensFiltrados = computed(() => {
  if (!filtroBusca.value) return itens.value
  return itens.value.filter((item) =>
    item.nome.toLowerCase().includes(filtroBusca.value.toLowerCase())
  )
})

// 3. MÉTODOS
const exibirMensagem = (texto, tipo = 'sucesso') => {
  mensagem.value = { texto, tipo }
  setTimeout(() => {
    mensagem.value = { texto: '', tipo: '' }
  }, 4000)
}

// Carregar lista de produtos
const carregarEstoque = async () => {
  loading.value = true
  try {
    const response = await api.get('/estoque')
    itens.value = response.data
  } catch (error) {
    console.error('Erro ao conectar com a API:', error)
    exibirMensagem(
      'Não foi possível conectar com a API Laravel (certifique-se de que o backend está rodando com php artisan serve).',
      'erro'
    )
  } finally {
    loading.value = false
  }
}

// Salvar produto (Criar ou Atualizar)
const salvarProduto = async () => {
  if (!form.nome || form.quantidade_estoque === '') {
    exibirMensagem('Preencha todos os campos obrigatórios.', 'erro')
    return
  }

  loading.value = true
  try {
    if (form.id) {
      await api.put(`/estoque/${form.id}`, {
        nome: form.nome,
        quantidade_estoque: parseInt(form.quantidade_estoque)
      })
      exibirMensagem('Produto atualizado com sucesso!')
    } else {
      await api.post('/estoque', {
        nome: form.nome,
        quantidade_estoque: parseInt(form.quantidade_estoque)
      })
      exibirMensagem('Produto cadastrado com sucesso!')
    }

    limparFormulario()
    await carregarEstoque()
  } catch (error) {
    console.error('Erro ao salvar produto:', error)
    exibirMensagem('Erro ao salvar produto. Verifique os dados.', 'erro')
  } finally {
    loading.value = false
  }
}

// Preparar edição
const editarProduto = (item) => {
  form.id = item.id
  form.nome = item.nome
  form.quantidade_estoque = item.quantidade_estoque
}

// Cancelar edição / Limpar
const limparFormulario = () => {
  form.id = null
  form.nome = ''
  form.quantidade_estoque = ''
}

// Excluir produto
const excluirProduto = async (id) => {
  if (!confirm('Tem certeza que deseja remover este item?')) return

  loading.value = true
  try {
    await api.delete(`/estoque/${id}`)
    exibirMensagem('Item removido com sucesso!')
    await carregarEstoque()
  } catch (error) {
    console.error('Erro ao excluir:', error)
    exibirMensagem('Erro ao excluir o item.', 'erro')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  carregarEstoque()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Cabeçalho da Página -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Controle de Estoque</h1>
        <p class="text-sm text-slate-500 mt-1">Gerencie os materiais, insumos e níveis de estoque da copiadora</p>
      </div>

      <!-- Métricas Rápidas no Topo -->
      <div class="flex items-center gap-3">
        <div class="bg-white px-3.5 py-2 rounded-xl shadow-xs border border-slate-200 text-center">
          <span class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Produtos</span>
          <span class="text-lg font-bold text-blue-600">{{ totalItens }}</span>
        </div>
        <div class="bg-white px-3.5 py-2 rounded-xl shadow-xs border border-slate-200 text-center">
          <span class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Unidades</span>
          <span class="text-lg font-bold text-emerald-600">{{ totalEstoqueGeral }}</span>
        </div>
        <div class="bg-white px-3.5 py-2 rounded-xl shadow-xs border border-slate-200 text-center">
          <span class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Baixo</span>
          <span :class="['text-lg font-bold', itensEstoqueBaixo > 0 ? 'text-rose-600' : 'text-slate-700']">
            {{ itensEstoqueBaixo }}
          </span>
        </div>
      </div>
    </div>

    <!-- Alertas de Feedback -->
    <transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="transform -translate-y-2 opacity-0"
      enter-to-class="transform translate-y-0 opacity-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="transform translate-y-0 opacity-100"
      leave-to-class="transform -translate-y-2 opacity-0"
    >
      <div
        v-if="mensagem.texto"
        :class="[
          'p-4 rounded-xl shadow-xs text-sm font-medium border flex items-center gap-3',
          mensagem.tipo === 'erro'
            ? 'bg-rose-50 text-rose-800 border-rose-200'
            : 'bg-emerald-50 text-emerald-800 border-emerald-200'
        ]"
      >
        <span>{{ mensagem.tipo === 'erro' ? '⚠️' : '✅' }}</span>
        <span>{{ mensagem.texto }}</span>
      </div>
    </transition>

    <!-- Grade Principal (Formulário + Tabela) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
      
      <!-- Formulário de Cadastro / Edição -->
      <section class="lg:col-span-4 bg-white rounded-2xl shadow-xs border border-slate-200 p-6 space-y-5">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
          <h2 class="text-base font-bold text-slate-800">
            {{ form.id ? '✏️ Editar Item' : '➕ Cadastrar Item' }}
          </h2>
          <span v-if="form.id" class="text-xs px-2 py-0.5 rounded-md bg-amber-100 text-amber-800 font-semibold">
            Modo Edição
          </span>
        </div>

        <form @submit.prevent="salvarProduto" class="space-y-4">
          <div>
            <label for="nome" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">
              Nome do Material / Insumo:
            </label>
            <input
              id="nome"
              v-model.trim="form.nome"
              type="text"
              placeholder="Ex: Papel Sulfite A4 75g (Resma)"
              required
              class="w-full px-3.5 py-2.5 rounded-lg bg-slate-50 border border-slate-300 text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
            />
          </div>

          <div>
            <label for="quantidade" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">
              Quantidade em Estoque:
            </label>
            <input
              id="quantidade"
              v-model.number="form.quantidade_estoque"
              type="number"
              min="0"
              placeholder="Ex: 50"
              required
              class="w-full px-3.5 py-2.5 rounded-lg bg-slate-50 border border-slate-300 text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
            />
          </div>

          <div class="pt-2 flex flex-col sm:flex-row gap-2">
            <button
              type="submit"
              :disabled="loading"
              class="flex-1 inline-flex justify-center items-center px-4 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-sm font-semibold shadow-xs transition disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
            >
              {{ form.id ? 'Salvar Alterações' : 'Adicionar ao Estoque' }}
            </button>

            <button
              v-if="form.id"
              type="button"
              @click="limparFormulario"
              class="inline-flex justify-center items-center px-4 py-2.5 rounded-lg bg-slate-100 hover:bg-slate-200 active:bg-slate-300 text-slate-700 text-sm font-semibold transition cursor-pointer"
            >
              Cancelar
            </button>
          </div>
        </form>
      </section>

      <!-- Listagem de Estoque e Tabela -->
      <section class="lg:col-span-8 bg-white rounded-2xl shadow-xs border border-slate-200 overflow-hidden">
        
        <!-- Topo da Tabela com Busca -->
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div>
            <h2 class="text-base font-bold text-slate-800">Itens Cadastrados</h2>
            <p class="text-xs text-slate-500 mt-0.5">Visão detalhada de insumos disponíveis</p>
          </div>

          <div class="relative w-full sm:w-64">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-sm">
              🔍
            </span>
            <input
              v-model="filtroBusca"
              type="text"
              placeholder="Filtrar por nome..."
              class="w-full pl-9 pr-3.5 py-1.5 rounded-lg bg-slate-50 border border-slate-300 text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
            />
          </div>
        </div>

        <!-- Estado Carregando -->
        <div v-if="loading && itens.length === 0" class="py-16 text-center text-slate-500 text-sm">
          <div class="animate-spin inline-block w-6 h-6 border-2 border-blue-600 border-t-transparent rounded-full mb-2"></div>
          <p>Carregando dados da API...</p>
        </div>

        <!-- Estado Vazio -->
        <div v-else-if="itensFiltrados.length === 0" class="py-16 text-center text-slate-400 text-sm">
          <span class="text-3xl block mb-2">📭</span>
          Nenhum item encontrado no estoque.
        </div>

        <!-- Tabela Responsiva -->
        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-100 text-left">
            <thead class="bg-slate-50 text-xs font-semibold text-slate-500 uppercase tracking-wider">
              <tr>
                <th class="px-5 py-3.5">ID</th>
                <th class="px-5 py-3.5">Nome do Produto</th>
                <th class="px-5 py-3.5 text-center">Quantidade</th>
                <th class="px-5 py-3.5 text-center">Status</th>
                <th class="px-5 py-3.5 text-right">Ações</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
              <tr
                v-for="item in itensFiltrados"
                :key="item.id"
                class="hover:bg-slate-50/70 transition"
              >
                <!-- ID -->
                <td class="px-5 py-3.5 font-mono text-xs text-slate-400">
                  #{{ item.id }}
                </td>

                <!-- Nome -->
                <td class="px-5 py-3.5 font-semibold text-slate-800">
                  {{ item.nome }}
                </td>

                <!-- Quantidade -->
                <td class="px-5 py-3.5 text-center">
                  <span class="font-bold text-slate-800 text-sm">
                    {{ item.quantidade_estoque }}
                  </span>
                </td>

                <!-- Status -->
                <td class="px-5 py-3.5 text-center">
                  <span
                    :class="[
                      'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold',
                      item.quantidade_estoque <= 5
                        ? 'bg-rose-100 text-rose-700'
                        : 'bg-emerald-100 text-emerald-700'
                    ]"
                  >
                    {{ item.quantidade_estoque <= 5 ? 'Estoque Baixo' : 'Normal' }}
                  </span>
                </td>

                <!-- Ações -->
                <td class="px-5 py-3.5 text-right space-x-1.5 whitespace-nowrap">
                  <button
                    type="button"
                    @click="editarProduto(item)"
                    class="px-2.5 py-1 text-xs font-semibold rounded-md text-blue-600 hover:bg-blue-50 transition cursor-pointer"
                  >
                    Editar
                  </button>
                  <button
                    type="button"
                    @click="excluirProduto(item.id)"
                    class="px-2.5 py-1 text-xs font-semibold rounded-md text-rose-600 hover:bg-rose-50 transition cursor-pointer"
                  >
                    Excluir
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

    </div>
  </div>
</template>
