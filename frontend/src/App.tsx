import { Navigate, Route, Routes } from 'react-router-dom'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { useAuthStore } from '@/stores/auth'
import { AuthBootstrap } from '@/components/AuthBootstrap'
import { AppLayout } from '@/components/layout/AppLayout'
import { LandingPage } from '@/pages/LandingPage'
import { LoginPage } from '@/pages/LoginPage'
import { RegisterPage } from '@/pages/RegisterPage'
import { RenewSubscriptionPage } from '@/pages/RenewSubscriptionPage'
import { DashboardPage } from '@/pages/DashboardPage'
import { PropertiesPage } from '@/pages/PropertiesPage'
import { PropertyDetailPage } from '@/pages/PropertyDetailPage'
import { PropertyFormPage } from '@/pages/PropertyFormPage'
import { SearchPage } from '@/pages/SearchPage'
import { FavoritesPage } from '@/pages/FavoritesPage'
import { TeamPage } from '@/pages/TeamPage'
import { SubscriptionPage } from '@/pages/SubscriptionPage'
import { BlogListPage } from '@/pages/BlogListPage'
import { BlogCategoryPage } from '@/pages/BlogCategoryPage'
import { BlogPostPage } from '@/pages/BlogPostPage'
import { DownloadPage } from '@/pages/DownloadPage'
import { SettingsPage } from '@/pages/SettingsPage'
import { SuperAdminRoute } from '@/components/SuperAdminRoute'
import { AdminBlogListPage } from '@/pages/admin/AdminBlogListPage'
import { AdminBlogEditorPage } from '@/pages/admin/AdminBlogEditorPage'
import { AdminDownloadsPage } from '@/pages/admin/AdminDownloadsPage'
import { AdminSuperPanelPage } from '@/pages/admin/AdminSuperPanelPage'
import { AnalyticsTracker } from '@/components/AnalyticsTracker'
import { SubscriptionGuard } from '@/components/SubscriptionGuard'
import { AdminPlansPage } from '@/pages/admin/AdminPlansPage'
import { AdminOfficesPage } from '@/pages/admin/AdminOfficesPage'
import { PaymentCallbackPage } from '@/pages/PaymentCallbackPage'
import { OfficeLandingPage } from '@/pages/OfficeLandingPage'
import { OfficeSitePage } from '@/pages/OfficeSitePage'
import { OfficeWebsitePage } from '@/pages/OfficeWebsitePage'
import { TicketsPage } from '@/pages/TicketsPage'
import { AccountingPage } from '@/pages/AccountingPage'
import { CrmPage } from '@/pages/CrmPage'
import { ReportsPage } from '@/pages/ReportsPage'
import { ContractsPage } from '@/pages/ContractsPage'
import { CommissionsPage } from '@/pages/CommissionsPage'
import { AdminTicketsPage } from '@/pages/admin/AdminTicketsPage'
import { VisitsPage } from '@/pages/VisitsPage'
import { OwnersPage } from '@/pages/OwnersPage'
import { OwnerDetailPage } from '@/pages/OwnerDetailPage'
import { CustomersPage } from '@/pages/CustomersPage'
import { CustomerDetailPage } from '@/pages/CustomerDetailPage'
import { PropertyPublicPage } from '@/pages/PropertyPublicPage'
import { TermsPage } from '@/pages/TermsPage'
import { PrivacyPage } from '@/pages/PrivacyPage'
import { ContactPage } from '@/pages/ContactPage'
import { getOfficeSubdomain } from '@/lib/subdomain'

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
  // When served from an office subdomain (name.posheapp.ir) render only that
  // office's public website, regardless of the requested path.
  const officeSubdomain = getOfficeSubdomain()
  if (officeSubdomain) {
    return (
      <QueryClientProvider client={queryClient}>
        <OfficeSitePage subdomain={officeSubdomain} />
      </QueryClientProvider>
    )
  }

  return (
    <QueryClientProvider client={queryClient}>
      <AuthBootstrap>
        <AnalyticsTracker />
        <Routes>
          <Route path="/" element={<LandingPage />} />
          <Route path="/blog" element={<BlogListPage />} />
          <Route path="/blog/category/:category" element={<BlogCategoryPage />} />
          <Route path="/blog/:slug" element={<BlogPostPage />} />
          <Route path="/download" element={<DownloadPage />} />
          <Route path="/login" element={<LoginPage />} />
          <Route path="/register" element={<RegisterPage />} />
          <Route path="/terms" element={<TermsPage />} />
          <Route path="/privacy" element={<PrivacyPage />} />
          <Route path="/contact" element={<ContactPage />} />
          <Route path="/payment/callback" element={<PaymentCallbackPage />} />
          <Route path="/p/:token" element={<PropertyPublicPage />} />
          <Route path="/o/:slug" element={<OfficeLandingPage />} />
          <Route path="/site/:subdomain" element={<OfficeSitePage />} />
          <Route
            element={
              <ProtectedRoute>
                <SubscriptionGuard>
                  <AppLayout />
                </SubscriptionGuard>
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
            <Route path="/renew" element={<RenewSubscriptionPage />} />
            <Route path="/tickets" element={<TicketsPage />} />
            <Route path="/accounting" element={<AccountingPage />} />
            <Route path="/crm" element={<CrmPage />} />
            <Route path="/reports" element={<ReportsPage />} />
            <Route path="/commissions" element={<CommissionsPage />} />
            <Route path="/contracts" element={<ContractsPage />} />
            <Route path="/office-website" element={<OfficeWebsitePage />} />
            <Route path="/owners" element={<OwnersPage />} />
            <Route path="/owners/:id" element={<OwnerDetailPage />} />
            <Route path="/customers" element={<CustomersPage />} />
            <Route path="/customers/:id" element={<CustomerDetailPage />} />
            <Route path="/visits" element={<VisitsPage />} />
            <Route path="/settings" element={<SettingsPage />} />
            <Route
              path="/admin/tickets"
              element={
                <SuperAdminRoute>
                  <AdminTicketsPage />
                </SuperAdminRoute>
              }
            />
            <Route
              path="/admin/plans"
              element={
                <SuperAdminRoute>
                  <AdminPlansPage />
                </SuperAdminRoute>
              }
            />
            <Route
              path="/admin/offices"
              element={
                <SuperAdminRoute>
                  <AdminOfficesPage />
                </SuperAdminRoute>
              }
            />
            <Route
              path="/admin"
              element={
                <SuperAdminRoute>
                  <AdminSuperPanelPage />
                </SuperAdminRoute>
              }
            />
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
