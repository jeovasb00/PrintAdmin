import { createRouter, createWebHistory } from 'vue-router'
import DashboardView from '../views/DashboardView.vue'
import EstoqueView from '../views/EstoqueView.vue'
import ServicosView from '../views/ServicosView.vue'
import OrdensServicoView from '../views/OrdensServicoView.vue'
import ConfigView from '../views/ConfigView.vue'
import AuthView from '../views/AuthView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: AuthView,
      meta: { requiresAuth: false, layout: 'auth' },
    },
    {
      path: '/',
      name: 'dashboard',
      component: DashboardView,
      meta: { requiresAuth: true },
    },
    {
      path: '/ordens-servico',
      name: 'ordens-servico',
      component: OrdensServicoView,
      meta: { requiresAuth: true },
    },
    {
      path: '/estoque',
      name: 'estoque',
      component: EstoqueView,
      meta: { requiresAuth: true },
    },
    {
      path: '/servicos',
      name: 'servicos',
      component: ServicosView,
      meta: { requiresAuth: true },
    },
    {
      path: '/configuracoes',
      name: 'configuracoes',
      component: ConfigView,
      meta: { requiresAuth: true },
    },
  ],
})

// Guarda Global de Navegação (Navigation Guard)
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('access_token')

  // Se a rota requer autenticação e o usuário NÃO possui token
  if (to.meta.requiresAuth && !token) {
    return next({ name: 'login' })
  }

  // Se o usuário já estiver logado e tentar acessar a tela de login
  if (to.name === 'login' && token) {
    return next({ name: 'dashboard' })
  }

  next()
})

export default router

