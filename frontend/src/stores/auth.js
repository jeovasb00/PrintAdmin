import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../services/api'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('access_token') || null)
  const user = ref(JSON.parse(localStorage.getItem('user') || 'null'))
  const loading = ref(false)
  const error = ref(null)

  const isAuthenticated = computed(() => !!token.value)

  const setAuthData = (newToken, userData) => {
    token.value = newToken
    user.value = userData
    localStorage.setItem('access_token', newToken)
    localStorage.setItem('user', JSON.stringify(userData))
  }

  const clearAuthData = () => {
    token.value = null
    user.value = null
    localStorage.removeItem('access_token')
    localStorage.removeItem('user')
  }

  const login = async (credentials) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.post('/login', credentials)
      setAuthData(response.data.access_token, response.data.user)
      return response.data
    } catch (err) {
      const msg = err.response?.data?.message || 'Falha ao realizar login. Verifique seus dados.'
      error.value = msg
      throw err
    } finally {
      loading.value = false
    }
  }

  const register = async (userData) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.post('/register', userData)
      setAuthData(response.data.access_token, response.data.user)
      return response.data
    } catch (err) {
      const msg = err.response?.data?.message || 'Falha ao registrar usuário.'
      error.value = msg
      throw err
    } finally {
      loading.value = false
    }
  }

  const logout = async () => {
    loading.value = true
    try {
      if (token.value) {
        await api.post('/logout')
      }
    } catch (err) {
      console.error('Erro ao fazer logout na API:', err)
    } finally {
      clearAuthData()
      loading.value = false
    }
  }

  return {
    token,
    user,
    loading,
    error,
    isAuthenticated,
    login,
    register,
    logout,
    clearAuthData,
  }
})
