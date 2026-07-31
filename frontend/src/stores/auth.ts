import { create } from 'zustand'
import { persist } from 'zustand/middleware'
import api from '@/lib/api'
import { getDeviceId } from '@/lib/device'

export interface User {
  id: number
  name: string
  mobile: string
  email?: string
  username?: string
  role: string
  role_label: string
  avatar_url?: string
  office?: {
    id: number
    name: string
    slug: string
    city?: string
    panel_type?: string
    has_access?: boolean
    on_trial?: boolean
    trial_label?: string
    trial_hours_remaining?: number
    subscription_expired?: boolean
    is_verified?: boolean
    plan?: {
      id: number
      slug: string
      name: string
      panel_type: string
      features: string[]
    }
    subscription?: {
      status: string
      ends_at: string
      plan: string
    }
  }
}

interface AuthState {
  user: User | null
  token: string | null
  isAuthenticated: boolean
  hydrated: boolean
  setAuth: (user: User, token: string) => void
  setUser: (user: User) => void
  logout: () => Promise<void>
  logoutAll: () => Promise<void>
  refreshUser: () => Promise<void>
  setHydrated: () => void
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      user: null,
      token: null,
      isAuthenticated: false,
      hydrated: false,
      setAuth: (user, token) => {
        localStorage.setItem('token', token)
        set({ user, token, isAuthenticated: true })
      },
      setUser: (user) => set({ user }),
      logout: async () => {
        try {
          await api.post('/auth/logout', { device_id: getDeviceId() })
        } catch {
          // ignore network errors during logout
        }
        localStorage.removeItem('token')
        set({ user: null, token: null, isAuthenticated: false })
      },
      logoutAll: async () => {
        try {
          await api.post('/auth/logout-all')
        } catch {
          // ignore
        }
        localStorage.removeItem('token')
        set({ user: null, token: null, isAuthenticated: false })
      },
      refreshUser: async () => {
        const token = get().token ?? localStorage.getItem('token')
        if (!token) {
          set({ user: null, token: null, isAuthenticated: false })
          return
        }
        try {
          const { data } = await api.get('/auth/me')
          set({ user: data.user, token, isAuthenticated: true })
        } catch {
          localStorage.removeItem('token')
          set({ user: null, token: null, isAuthenticated: false })
        }
      },
      setHydrated: () => set({ hydrated: true }),
    }),
    {
      name: 'posheh-auth',
      partialize: (state) => ({
        user: state.user,
        token: state.token,
        isAuthenticated: state.isAuthenticated,
      }),
      onRehydrateStorage: () => (state) => {
        state?.setHydrated()
      },
    }
  )
)

interface ThemeState {
  theme: 'dark' | 'light'
  toggleTheme: () => void
}

export const useThemeStore = create<ThemeState>()(
  persist(
    (set, get) => ({
      theme: 'dark',
      toggleTheme: () => {
        const newTheme = get().theme === 'dark' ? 'light' : 'dark'
        document.documentElement.classList.toggle('light', newTheme === 'light')
        set({ theme: newTheme })
      },
    }),
    { name: 'posheh-theme' }
  )
)
