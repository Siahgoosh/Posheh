import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api/v1',
  timeout: 25000,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token')
      if (!window.location.pathname.includes('/login') && window.location.pathname !== '/') {
        window.location.href = '/login'
      }
    }
    if (error.response?.status === 402 && error.response?.data?.subscription_expired) {
      if (!window.location.pathname.startsWith('/renew') && !window.location.pathname.startsWith('/subscription')) {
        window.location.href = '/renew'
      }
    }
    return Promise.reject(error)
  }
)

export default api
