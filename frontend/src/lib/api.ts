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

  // Default Content-Type is application/json. For FormData uploads the browser
  // must set multipart/form-data with a boundary — otherwise Laravel never sees the file
  // ("panorama/image field is required").
  if (typeof FormData !== 'undefined' && config.data instanceof FormData) {
    if (typeof config.headers.set === 'function') {
      config.headers.set('Content-Type', false as unknown as string)
    } else {
      delete (config.headers as Record<string, unknown>)['Content-Type']
    }
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
