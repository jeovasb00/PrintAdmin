<script setup>
import { ref, computed } from 'vue'
import { RouterView, useRoute } from 'vue-router'
import Sidebar from './components/Sidebar.vue'
import Navbar from './components/Navbar.vue'

const route = useRoute()
const isMobileOpen = ref(false)
const isCollapsed = ref(localStorage.getItem('sidebar_collapsed') === 'true')

const isAuthLayout = computed(() => route.meta.layout === 'auth')

const toggleMobileSidebar = () => {
  isMobileOpen.value = !isMobileOpen.value
}

const toggleSidebarCollapse = () => {
  isCollapsed.value = !isCollapsed.value
  localStorage.setItem('sidebar_collapsed', isCollapsed.value)
}

const closeMobileSidebar = () => {
  isMobileOpen.value = false
}
</script>

<template>
  <!-- Layout de Autenticação (Tela cheia sem sidebar/navbar) -->
  <div v-if="isAuthLayout" class="min-h-screen bg-slate-950 font-sans">
    <router-view />
  </div>

  <!-- Layout Principal do Painel -->
  <div v-else class="min-h-screen bg-slate-100/70 antialiased font-sans text-slate-800 flex">
    <!-- Menu Lateral / Sidebar -->
    <Sidebar
      :is-open="isMobileOpen"
      :is-collapsed="isCollapsed"
      @close="closeMobileSidebar"
      @toggle-collapse="toggleSidebarCollapse"
    />

    <!-- Conteúdo Principal do Painel -->
    <div
      :class="[
        'flex-1 flex flex-col min-w-0 transition-all duration-300 ease-in-out',
        isCollapsed ? 'lg:pl-20' : 'lg:pl-64'
      ]"
    >
      <!-- Barra Superior / Navbar -->
      <Navbar
        :is-collapsed="isCollapsed"
        @toggle-sidebar="toggleMobileSidebar"
        @toggle-collapse="toggleSidebarCollapse"
      />

      <!-- Área de Visualização das Rotas / Páginas -->
      <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto">
        <router-view v-slot="{ Component }">
          <transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0 translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-1"
            mode="out-in"
          >
            <component :is="Component" />
          </transition>
        </router-view>
      </main>

      <!-- Rodapé do Painel Admin -->
      <footer class="py-4 px-6 text-center text-xs text-slate-400 border-t border-slate-200/60">
        PrintAdmin &bull; Sistema de Gestão para Copiadoras &amp; Gráficas &bull; Vue 3 + Tailwind CSS
      </footer>
    </div>
  </div>
</template>

