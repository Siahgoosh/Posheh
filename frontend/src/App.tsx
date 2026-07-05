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

const queryClient = new QueryClient({
  defaultOptions: { queries: { retry: 1, staleTime: 30000 } },
})

function ProtectedRoute({ children }: { children: React.ReactNode }) {
  const { isAuthenticated } = useAuthStore()
  if (!isAuthenticated) return <Navigate to="/login" replace />
  return <>{children}</>
}

export default function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <Routes>
        <Route path="/login" element={<LoginPage />} />
        <Route
          element={
            <ProtectedRoute>
              <AppLayout />
            </ProtectedRoute>
          }
        >
          <Route path="/dashboard" element={<DashboardPage />} />
          <Route path="/properties" element={<PropertiesPage />} />
          <Route path="/properties/new" element={<PropertyFormPage />} />
          <Route path="/properties/:id" element={<PropertyDetailPage />} />
          <Route path="/search" element={<SearchPage />} />
          <Route path="/favorites" element={<FavoritesPage />} />
          <Route path="/team" element={<TeamPage />} />
          <Route path="/tasks" element={<TasksPage />} />
          <Route path="/subscription" element={<SubscriptionPage />} />
          <Route path="/settings" element={<SettingsPage />} />
          <Route path="/" element={<Navigate to="/dashboard" replace />} />
        </Route>
      </Routes>
    </QueryClientProvider>
  )
}
