<script setup>
import { computed } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

defineProps({
  isOpen: {
    type: Boolean,
    default: true,
  },
  isCollapsed: {
    type: Boolean,
    default: false,
  },
})

defineEmits(['close', 'toggleCollapse'])

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const navLinks = [
  { name: 'Dashboard', path: '/', icon: '📊', description: 'Visão geral & financeiro' },
  { name: 'Ordens de Serviço', path: '/ordens-servico', icon: '📋', description: 'Gestão de pedidos e OS' },
  { name: 'Calculadora & Preços', path: '/servicos', icon: '🖩', description: 'Orçamentos rápidos' },
  { name: 'Estoque / Insumos', path: '/estoque', icon: '📦', description: 'Controle e saldo' },
  { name: 'Configurações', path: '/configuracoes', icon: '⚙️', description: 'Preferências do sistema' },
]

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
  <!-- Overlay Backdrop para telas menores (Mobile) -->
  <div
    v-if="isOpen"
    class="fixed inset-0 z-40 bg-slate-900/40 backdrop-blur-xs lg:hidden"
    @click="$emit('close')"
  ></div>

  <!-- Sidebar Container -->
  <aside
    :class="[
      'fixed top-0 bottom-0 left-0 z-50 bg-slate-900 text-slate-300 flex flex-col justify-between transition-all duration-300 ease-in-out border-r border-slate-800',
      isCollapsed ? 'lg:w-20 w-64' : 'w-64',
      isOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
    ]"
  >
    <!-- Botão Redondo na Extremidade da Sidebar com Seta Esquerda/Direita -->
    <button
      type="button"
      @click="$emit('toggleCollapse')"
      class="hidden lg:flex absolute -right-3 top-5 z-20 w-6 h-6 rounded-full bg-slate-800 hover:bg-blue-600 border border-slate-700 hover:border-blue-500 text-slate-300 hover:text-white items-center justify-center shadow-lg transition-all duration-200 cursor-pointer group"
      :title="isCollapsed ? 'Expandir barra lateral' : 'Recolher barra lateral'"
    >
      <!-- Seta para Esquerda (se aberta) -->
      <svg
        v-if="!isCollapsed"
        xmlns="http://www.w3.org/2000/svg"
        class="w-3.5 h-3.5 transition-transform group-hover:-translate-x-0.5"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        stroke-width="2.5"
      >
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
      </svg>

      <!-- Seta para Direita (se colapsada) -->
      <svg
        v-else
        xmlns="http://www.w3.org/2000/svg"
        class="w-3.5 h-3.5 transition-transform group-hover:translate-x-0.5"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        stroke-width="2.5"
      >
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
      </svg>
    </button>

    <div>
      <!-- Topo / Branding -->
      <div
        :class="[
          'h-16 flex items-center border-b border-slate-800 transition-all',
          isCollapsed ? 'lg:justify-center px-4' : 'justify-between px-5'
        ]"
      >
        <div class="flex items-center gap-3 overflow-hidden">
          <div
            class="w-9 h-9 min-w-9 rounded-xl bg-blue-600 flex items-center justify-center text-white text-lg shadow-sm shadow-blue-500/30 cursor-pointer"
            :title="isCollapsed ? 'Expandir Menu' : 'PrintAdmin'"
            @click="isCollapsed && $emit('toggleCollapse')"
          >
            🖨️
          </div>
          <div v-show="!isCollapsed" class="transition-opacity duration-200">
            <span class="font-bold text-white text-base tracking-tight block leading-none">PrintAdmin</span>
            <span class="text-[10px] text-blue-400 font-semibold uppercase tracking-wider">Copiadora &amp; Gráfica</span>
          </div>
        </div>

        <!-- Botão Fechar no Mobile -->
        <button
          class="lg:hidden p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 cursor-pointer"
          @click="$emit('close')"
        >
          ✕
        </button>
      </div>

      <!-- Links de Navegação -->
      <div :class="['py-6 transition-all', isCollapsed ? 'px-2.5' : 'px-4']">
        <span
          v-show="!isCollapsed"
          class="px-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wider block mb-2 transition-opacity"
        >
          Menu Principal
        </span>

        <nav class="space-y-1.5">
          <RouterLink
            v-for="item in navLinks"
            :key="item.path"
            :to="item.path"
            :title="isCollapsed ? item.name : ''"
            @click="$emit('close')"
            :class="[
              'flex items-center rounded-xl text-sm font-medium transition-all group',
              isCollapsed ? 'justify-center p-3' : 'gap-3 px-3.5 py-2.5',
              route.path === item.path
                ? 'bg-blue-600 text-white font-semibold shadow-sm shadow-blue-600/30'
                : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/80'
            ]"
          >
            <span class="text-base shrink-0">{{ item.icon }}</span>
            <div v-show="!isCollapsed" class="flex-1 truncate">
              <span>{{ item.name }}</span>
            </div>
            <span
              v-if="route.path === item.path && !isCollapsed"
              class="w-1.5 h-1.5 rounded-full bg-white shrink-0"
            ></span>
          </RouterLink>
        </nav>
      </div>
    </div>

    <!-- Rodapé da Sidebar (Usuário / Logout) -->
    <div
      :class="[
        'rounded-xl bg-slate-800/60 border border-slate-700/60 flex flex-col gap-2.5 transition-all',
        isCollapsed ? 'p-2 m-2 items-center' : 'p-3 m-3'
      ]"
    >
      <div
        :class="[
          'flex items-center gap-3 w-full',
          isCollapsed ? 'justify-center' : ''
        ]"
      >
        <div
          class="w-9 h-9 min-w-9 rounded-lg bg-blue-600/80 text-white flex items-center justify-center font-bold text-xs uppercase"
          :title="isCollapsed ? (authStore.user?.name || 'Operador') : ''"
        >
          {{ userInitials }}
        </div>
        <div v-show="!isCollapsed" class="overflow-hidden flex-1">
          <p class="text-xs font-semibold text-white truncate">
            {{ authStore.user?.name || 'Operador' }}
          </p>
          <div class="flex items-center gap-1.5 mt-0.5">
            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
            <span class="text-[11px] text-slate-400 truncate">
              {{ authStore.user?.email || 'Conectado' }}
            </span>
          </div>
        </div>
      </div>

      <button
        @click="handleLogout"
        :title="isCollapsed ? 'Encerrar Sessão' : ''"
        :class="[
          'rounded-lg bg-slate-800 hover:bg-red-500/20 text-slate-300 hover:text-red-400 border border-slate-700/60 hover:border-red-500/40 text-xs font-medium transition flex items-center justify-center cursor-pointer',
          isCollapsed ? 'p-2 w-full mt-1' : 'w-full mt-1 py-1.5 px-3 gap-2'
        ]"
      >
        <span>🚪</span>
        <span v-show="!isCollapsed">Encerrar Sessão</span>
      </button>
    </div>
  </aside>
</template>
