import { Navigate, Route, Routes } from 'react-router-dom'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { useAuthStore } from '@/stores/auth'
import { AuthBootstrap } from '@/components/AuthBootstrap'
import { AppLayout } from '@/components/layout/AppLayout'
import { LandingPage } from '@/pages/LandingPage'
import { LoginPage } from '@/pages/LoginPage'
import { DashboardPage } from '@/pages/DashboardPage'
import { PropertiesPage } from '@/pages/PropertiesPage'
import { PropertyDetailPage } from '@/pages/PropertyDetailPage'
import { PropertyFormPage } from '@/pages/PropertyFormPage'
import { SearchPage } from '@/pages/SearchPage'
import { FavoritesPage } from '@/pages/FavoritesPage'
import { TeamPage } from '@/pages/TeamPage'
import { SubscriptionPage } from '@/pages/SubscriptionPage'
import { BlogListPage } from '@/pages/BlogListPage'
import { BlogPostPage } from '@/pages/BlogPostPage'
import { DownloadPage } from '@/pages/DownloadPage'
import { SettingsPage } from '@/pages/SettingsPage'
import { SuperAdminRoute } from '@/components/SuperAdminRoute'
import { AdminBlogListPage } from '@/pages/admin/AdminBlogListPage'
import { AdminBlogEditorPage } from '@/pages/admin/AdminBlogEditorPage'
import { AdminDownloadsPage } from '@/pages/admin/AdminDownloadsPage'

const queryClient = new QueryClient({
  defaultOptions: { queries: { retry: 1, staleTime: 30000 } },
})

function ProtectedRoute({ children }: { children: React.ReactNode }) {
  const { isAuthenticated, hydrated } = useAuthStore()
  if (!hydrated) {
    return (
      <div className="flex min-h-screen items-center justify-center">
        <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
      </div>
    )
  }
  if (!isAuthenticated) return <Navigate to="/login" replace />
  return <>{children}</>
}

export default function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <AuthBootstrap>
        <Routes>
          <Route path="/" element={<LandingPage />} />
          <Route path="/blog" element={<BlogListPage />} />
          <Route path="/blog/:slug" element={<BlogPostPage />} />
          <Route path="/download" element={<DownloadPage />} />
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
            <Route path="/properties/:id/edit" element={<PropertyFormPage />} />
            <Route path="/properties/:id" element={<PropertyDetailPage />} />
            <Route path="/search" element={<SearchPage />} />
            <Route path="/favorites" element={<FavoritesPage />} />
            <Route path="/team" element={<TeamPage />} />
            <Route path="/subscription" element={<SubscriptionPage />} />
            <Route path="/settings" element={<SettingsPage />} />
            <Route
              path="/admin/blog"
              element={
                <SuperAdminRoute>
                  <AdminBlogListPage />
                </SuperAdminRoute>
              }
            />
            <Route
              path="/admin/blog/new"
              element={
                <SuperAdminRoute>
                  <AdminBlogEditorPage />
                </SuperAdminRoute>
              }
            />
            <Route
              path="/admin/blog/:id/edit"
              element={
                <SuperAdminRoute>
                  <AdminBlogEditorPage />
                </SuperAdminRoute>
              }
            />
            <Route
              path="/admin/downloads"
              element={
                <SuperAdminRoute>
                  <AdminDownloadsPage />
                </SuperAdminRoute>
              }
            />
          </Route>
        </Routes>
      </AuthBootstrap>
    </QueryClientProvider>
  )
}
