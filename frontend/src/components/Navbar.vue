<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

defineProps({
  isCollapsed: {
    type: Boolean,
    default: false,
  },
})

defineEmits(['toggleSidebar', 'toggleCollapse'])

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const pageTitle = computed(() => {
  switch (route.path) {
    case '/':
      return 'Painel de Controle & Financeiro'
    case '/ordens-servico':
      return 'Ordens de Serviço (OS)'
    case '/servicos':
      return 'Calculadora de Orçamentos'
    case '/estoque':
      return 'Gestão de Estoque / Insumos'
    case '/configuracoes':
      return 'Configurações'
    default:
      return 'Painel'
  }
})

const userInitials = computed(() => {
  if (!authStore.user?.name) return 'OP'
  const names = authStore.user.name.trim().split(' ')
  if (names.length === 1) return names[0].substring(0, 2).toUpperCase()
  return (names[0][0] + names[names.length - 1][0]).toUpperCase()
})

const handleLogout = async () => {
  await authStore.logout()
  router.push('/login')
}
</script>

<template>
  <header class="h-16 bg-white border-b border-slate-200/80 sticky top-0 z-30 px-4 sm:px-6 flex items-center justify-between">
    <!-- Botão de Menu Mobile + Breadcrumbs -->
    <div class="flex items-center gap-3 sm:gap-4">
      <!-- Botão para Mobile -->
      <button
        type="button"
        @click="$emit('toggleSidebar')"
        class="lg:hidden p-2 rounded-xl text-slate-500 hover:text-blue-600 hover:bg-slate-100 transition flex items-center justify-center w-9 h-9 cursor-pointer border border-slate-200 shadow-xs text-base"
        title="Abrir Menu"
      >
        ☰
      </button>

      <div class="flex items-center gap-2">
        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 hidden sm:inline">
          Painel
        </span>
        <span class="text-slate-300 hidden sm:inline">/</span>
        <h2 class="text-sm sm:text-base font-bold text-slate-800">
          {{ pageTitle }}
        </h2>
      </div>
    </div>

    <!-- Ações do Topo / Usuário & Logout -->
    <div class="flex items-center gap-3 sm:gap-4">
      <div class="hidden md:flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold border border-emerald-200/60">
        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
        <span>Online</span>
      </div>

      <div class="h-8 w-px bg-slate-200 mx-1 hidden sm:block"></div>

      <!-- Avatar & Nome do Usuário -->
      <div class="flex items-center gap-2.5">
        <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-xs shadow-xs">
          {{ userInitials }}
        </div>
        <div class="hidden sm:block text-left">
          <p class="text-xs font-semibold text-slate-800 leading-tight">
            {{ authStore.user?.name || 'Operador' }}
          </p>
          <p class="text-[10px] text-slate-400 leading-tight truncate max-w-[120px]">
            {{ authStore.user?.email || 'operador@copiadora' }}
          </p>
        </div>
      </div>

      <!-- Botão Sair / Logout -->
      <button
        @click="handleLogout"
        title="Encerrar sessão"
        class="p-2 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition cursor-pointer flex items-center gap-1.5 text-xs font-medium border border-transparent hover:border-red-200"
      >
        <span>🚪</span>
        <span class="hidden sm:inline">Sair</span>
      </button>
    </div>
  </header>
</template>
