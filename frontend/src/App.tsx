import { Navigate, Route, Routes } from 'react-router-dom'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { useAuthStore } from '@/stores/auth'
import { AppLayout } from '@/components/layout/AppLayout'
import { LoginPage } from '@/pages/LoginPage'
import { DashboardPage } from '@/pages/DashboardPage'
import { PropertiesPage } from '@/pages/PropertiesPage'
import { PropertyFormPage } from '@/pages/PropertyFormPage'
import { PropertyDetailPage } from '@/pages/PropertyDetailPage'
import { SearchPage } from '@/pages/SearchPage'
import { FavoritesPage } from '@/pages/FavoritesPage'
import { TeamPage } from '@/pages/TeamPage'
import { SubscriptionPage } from '@/pages/SubscriptionPage'
import { SettingsPage } from '@/pages/SettingsPage'
import { VirtualToursPage } from '@/pages/VirtualToursPage'
import { VirtualTourEditorPage } from '@/pages/VirtualTourEditorPage'
import { VirtualTourPublicPage } from '@/pages/VirtualTourPublicPage'
import { CrmPage } from '@/pages/CrmPage'
import { CommissionsPage } from '@/pages/CommissionsPage'
import { RentalsPage } from '@/pages/RentalsPage'
import { AdminPanelPage } from '@/pages/AdminPanelPage'

const queryClient = new QueryClient({
  defaultOptions: { queries: { retry: 1, staleTime: 30000 } },
})

function ProtectedRoute({ children }: { children: React.ReactNode }) {
  const { isAuthenticated } = useAuthStore()
  if (!isAuthenticated) return <Navigate to="/login" replace />
  return <>{children}</>
}

function SuperAdminRoute({ children }: { children: React.ReactNode }) {
  const { user } = useAuthStore()
  if (user?.role !== 'super_admin') return <Navigate to="/dashboard" replace />
  return <>{children}</>
}

export default function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <Routes>
        {/* Public — تور مجازی ۳۶۰ درجه (مثل 360nama) */}
        <Route path="/tour/:slug" element={<VirtualTourPublicPage />} />

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
          <Route path="/properties/:id/edit" element={<PropertyFormPage />} />
          <Route path="/search" element={<SearchPage />} />
          <Route path="/favorites" element={<FavoritesPage />} />
          <Route path="/team" element={<TeamPage />} />
          <Route path="/subscription" element={<SubscriptionPage />} />
          <Route path="/settings" element={<SettingsPage />} />
          <Route path="/virtual-tours" element={<VirtualToursPage />} />
          <Route path="/virtual-tours/:id/edit" element={<VirtualTourEditorPage />} />
          <Route path="/crm" element={<CrmPage />} />
          <Route path="/commissions" element={<CommissionsPage />} />
          <Route path="/rentals" element={<RentalsPage />} />
          <Route path="/admin" element={<SuperAdminRoute><AdminPanelPage /></SuperAdminRoute>} />
          <Route path="/" element={<Navigate to="/dashboard" replace />} />
        </Route>
      </Routes>
    </QueryClientProvider>
  )
}
