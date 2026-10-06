<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

// Modo atual: 'login' ou 'register'
const mode = ref('login')

const form = reactive({
  name: '',
  email: '',
  password: '',
})

const feedbackMessage = ref({ text: '', type: '' })

const switchMode = (newMode) => {
  mode.value = newMode
  feedbackMessage.value = { text: '', type: '' }
  authStore.error = null
}

const handleSubmit = async () => {
  feedbackMessage.value = { text: '', type: '' }

  if (!form.email || !form.password || (mode.value === 'register' && !form.name)) {
    feedbackMessage.value = {
      text: 'Por favor, preencha todos os campos obrigatórios.',
      type: 'error',
    }
    return
  }

  if (mode.value === 'register' && form.password.length < 6) {
    feedbackMessage.value = {
      text: 'A senha deve ter no mínimo 6 caracteres.',
      type: 'error',
    }
    return
  }

  try {
    if (mode.value === 'login') {
      await authStore.login({
        email: form.email,
        password: form.password,
      })
    } else {
      await authStore.register({
        name: form.name,
        email: form.email,
        password: form.password,
      })
    }
    
    // Redireciona para o painel principal após autenticar
    router.push('/')
  } catch (err) {
    const errorMsg =
      err.response?.data?.message ||
      err.response?.data?.errors?.[Object.keys(err.response?.data?.errors || {})[0]]?.[0] ||
      'Ocorreu um erro ao processar sua solicitação.'
    
    feedbackMessage.value = {
      text: errorMsg,
      type: 'error',
    }
  }
}
</script>

<template>
  <div class="min-h-screen bg-slate-950 flex flex-col justify-center items-center px-4 py-12 sm:px-6 lg:px-8 relative overflow-hidden">
    <!-- Efeitos de Fundo Decorativos -->
    <div class="absolute -top-32 -left-32 w-96 h-96 bg-blue-600/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl pointer-events-none"></div>

    <div class="w-full max-w-md relative z-10">
      <!-- Topo: Logo & Identidade -->
      <div class="text-center mb-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">PrintAdmin</h1>
        <p class="text-slate-400 text-sm mt-1">Sistema de Gestão para Copiadoras &amp; Gráficas</p>
      </div>

      <!-- Card do Formulário -->
      <div class="bg-slate-900/90 border border-slate-800 backdrop-blur-xl rounded-2xl p-6 sm:p-8 shadow-2xl">
        <!-- Alternador de Abas (Login / Cadastro) -->
        <div class="flex rounded-xl bg-slate-800/80 p-1 mb-6 border border-slate-700/50">
          <button
            type="button"
            @click="switchMode('login')"
            :class="[
              'flex-1 py-2 text-xs sm:text-sm font-semibold rounded-lg transition-all cursor-pointer',
              mode === 'login'
                ? 'bg-blue-600 text-white shadow-sm'
                : 'text-slate-400 hover:text-white'
            ]"
          >
            Entrar
          </button>
          <button
            type="button"
            @click="switchMode('register')"
            :class="[
              'flex-1 py-2 text-xs sm:text-sm font-semibold rounded-lg transition-all cursor-pointer',
              mode === 'register'
                ? 'bg-blue-600 text-white shadow-sm'
                : 'text-slate-400 hover:text-white'
            ]"
          >
            Cadastrar
          </button>
        </div>

        <!-- Mensagens de Alerta / Erro -->
        <div
          v-if="feedbackMessage.text"
          :class="[
            'p-3.5 rounded-xl text-xs sm:text-sm font-medium mb-5 flex items-center gap-2.5',
            feedbackMessage.type === 'error'
              ? 'bg-red-500/10 border border-red-500/30 text-red-400'
              : 'bg-emerald-500/10 border border-emerald-500/30 text-emerald-400'
          ]"
        >
          <span>{{ feedbackMessage.type === 'error' ? '⚠️' : '✅' }}</span>
          <span>{{ feedbackMessage.text }}</span>
        </div>

        <!-- Formulário -->
        <form @submit.prevent="handleSubmit" class="space-y-4">
          <!-- Campo Nome (Somente no Cadastro) -->
          <div v-if="mode === 'register'">
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
              Nome Completo
            </label>
            <input
              v-model="form.name"
              type="text"
              placeholder="Ex: João da Silva"
              required
              class="w-full px-4 py-2.5 bg-slate-800/80 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm transition"
            />
          </div>

          <!-- Campo E-mail -->
          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
              E-mail
            </label>
            <input
              v-model="form.email"
              type="email"
              placeholder="seu.email@exemplo.com"
              required
              class="w-full px-4 py-2.5 bg-slate-800/80 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm transition"
            />
          </div>

          <!-- Campo Senha -->
          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
              Senha
            </label>
            <input
              v-model="form.password"
              type="password"
              placeholder="••••••••"
              required
              class="w-full px-4 py-2.5 bg-slate-800/80 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm transition"
            />
            <p v-if="mode === 'register'" class="text-[11px] text-slate-400 mt-1">
              Mínimo de 6 caracteres.
            </p>
          </div>

          <!-- Botão Submit -->
          <button
            type="submit"
            :disabled="authStore.loading"
            class="w-full mt-6 py-3 px-4 rounded-xl bg-blue-600 hover:bg-blue-500 disabled:bg-blue-800/50 text-white font-semibold text-sm transition shadow-lg shadow-blue-600/20 flex items-center justify-center gap-2 cursor-pointer disabled:cursor-not-allowed"
          >
            <span v-if="authStore.loading" class="animate-spin text-base">⏳</span>
            <span>
              {{ authStore.loading ? 'Processando...' : (mode === 'login' ? 'Entrar no Sistema' : 'Criar Minha Conta') }}
            </span>
          </button>
        </form>
      </div>

      <!-- Rodapé -->
      <p class="text-center text-xs text-slate-600 mt-8">
        PrintAdmin &bull; Autenticação Segura via Laravel Sanctum
      </p>
    </div>
  </div>
</template>
