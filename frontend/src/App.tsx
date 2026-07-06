import { Navigate, Route, Routes } from 'react-router-dom'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { useAuthStore } from '@/stores/auth'
import { AppLayout } from '@/components/layout/AppLayout'
import { LoginPage } from '@/pages/LoginPage'
import { DashboardPage } from '@/pages/DashboardPage'
import { PropertiesPage } from '@/pages/PropertiesPage'
import { PropertyDetailPage } from '@/pages/PropertyDetailPage'
import { PropertyFormPage } from '@/pages/PropertyFormPage'
import { SearchPage } from '@/pages/SearchPage'
import { FavoritesPage } from '@/pages/FavoritesPage'
import { TeamPage } from '@/pages/TeamPage'
import { SubscriptionPage } from '@/pages/SubscriptionPage'
import { TasksPage } from '@/pages/TasksPage'
import { SettingsPage } from '@/pages/SettingsPage'
import { AdminDashboardPage } from '@/pages/admin/AdminDashboardPage'
import { AdminSettingsPage } from '@/pages/admin/AdminSettingsPage'
import { AdminOfficesPage } from '@/pages/admin/AdminOfficesPage'
import { AdminFinancePage } from '@/pages/admin/AdminFinancePage'
import { AdminTicketsPage } from '@/pages/admin/AdminTicketsPage'
import { SupportPage } from '@/pages/SupportPage'
import { ContactsPage } from '@/pages/ContactsPage'

const queryClient = new QueryClient({
  defaultOptions: { queries: { retry: 1, staleTime: 30000 } },
})

function ProtectedRoute({ children }: { children: React.ReactNode }) {
  const { isAuthenticated } = useAuthStore()
  if (!isAuthenticated) return <Navigate to="/login" replace />
  return <>{children}</>
}

function SuperAdminRoute({ children }: { children: React.ReactNode }) {
  const { user, isAuthenticated } = useAuthStore()
  if (!isAuthenticated) return <Navigate to="/login" replace />
  if (user?.role !== 'super_admin') return <Navigate to="/dashboard" replace />
  return <>{children}</>
}

function SubscriptionGuard({ children }: { children: React.ReactNode }) {
  const { user } = useAuthStore()
  if (user?.role === 'super_admin') return <>{children}</>
  const hasAccess = user?.office?.has_access ?? user?.office?.on_trial
  if (user?.office && !hasAccess) {
    return <Navigate to="/subscription" replace />
  }
  return <>{children}</>
}

export default function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <Routes>
        <Route path="/login" element={<LoginPage />} />
        <Route
          path="/admin/*"
          element={
            <SuperAdminRoute>
              <AppLayout />
            </SuperAdminRoute>
          }
        >
          <Route index element={<AdminDashboardPage />} />
          <Route path="offices" element={<AdminOfficesPage />} />
          <Route path="finance" element={<AdminFinancePage />} />
          <Route path="tickets" element={<AdminTicketsPage />} />
          <Route path="settings" element={<AdminSettingsPage />} />
        </Route>
        <Route
          element={
            <ProtectedRoute>
              <AppLayout />
            </ProtectedRoute>
          }
        >
          <Route path="/subscription" element={<SubscriptionPage />} />
          <Route path="/settings" element={<SettingsPage />} />
          <Route path="/dashboard" element={<SubscriptionGuard><DashboardPage /></SubscriptionGuard>} />
          <Route path="/properties" element={<SubscriptionGuard><PropertiesPage /></SubscriptionGuard>} />
          <Route path="/properties/new" element={<SubscriptionGuard><PropertyFormPage /></SubscriptionGuard>} />
          <Route path="/properties/:id" element={<SubscriptionGuard><PropertyDetailPage /></SubscriptionGuard>} />
          <Route path="/search" element={<SubscriptionGuard><SearchPage /></SubscriptionGuard>} />
          <Route path="/favorites" element={<SubscriptionGuard><FavoritesPage /></SubscriptionGuard>} />
          <Route path="/team" element={<SubscriptionGuard><TeamPage /></SubscriptionGuard>} />
          <Route path="/tasks" element={<SubscriptionGuard><TasksPage /></SubscriptionGuard>} />
          <Route path="/support" element={<SubscriptionGuard><SupportPage /></SubscriptionGuard>} />
          <Route path="/contacts" element={<SubscriptionGuard><ContactsPage /></SubscriptionGuard>} />
          <Route path="/" element={<Navigate to="/dashboard" replace />} />
        </Route>
      </Routes>
    </QueryClientProvider>
  )
}
