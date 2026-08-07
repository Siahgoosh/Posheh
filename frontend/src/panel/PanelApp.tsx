import { Navigate, Route, Routes } from 'react-router-dom'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { AuthBootstrap } from '@/components/AuthBootstrap'
import { useAuthStore } from '@/stores/auth'
import { isPlatformStaffRole } from '@/lib/subdomain'
import { AdminLayout } from '@/components/layout/AdminLayout'
import { PanelLoginPage } from '@/pages/admin/PanelLoginPage'
import { ForgotPasswordPage } from '@/pages/ForgotPasswordPage'
import { ResetPasswordPage } from '@/pages/ResetPasswordPage'
import { AdminSuperPanelPage } from '@/pages/admin/AdminSuperPanelPage'
import { AdminOfficesPage } from '@/pages/admin/AdminOfficesPage'
import { AdminOfficeDetailPage } from '@/pages/admin/AdminOfficeDetailPage'
import { AdminPlansPage } from '@/pages/admin/AdminPlansPage'
import { AdminTicketsPage } from '@/pages/admin/AdminTicketsPage'
import { AdminBlogListPage } from '@/pages/admin/AdminBlogListPage'
import { AdminBlogEditorPage } from '@/pages/admin/AdminBlogEditorPage'
import { AdminDownloadsPage } from '@/pages/admin/AdminDownloadsPage'
import { AdminUsersPage } from '@/pages/admin/AdminUsersPage'
import { AdminUserDetailPage } from '@/pages/admin/AdminUserDetailPage'
import { AdminPaymentsPage } from '@/pages/admin/AdminPaymentsPage'
import { AdminSubscriptionsPage } from '@/pages/admin/AdminSubscriptionsPage'
import { AdminWalletsPage } from '@/pages/admin/AdminWalletsPage'
import { AdminWalletTransactionsPage } from '@/pages/admin/AdminWalletTransactionsPage'
import { AdminCouponsPage } from '@/pages/admin/AdminCouponsPage'
import { AdminSettingsPage } from '@/pages/admin/AdminSettingsPage'
import { AdminAuditPage } from '@/pages/admin/AdminAuditPage'
import { AdminAnnouncementsPage } from '@/pages/admin/AdminAnnouncementsPage'
import { AdminStaffPage } from '@/pages/admin/AdminStaffPage'
import { AdminReportsPage } from '@/pages/admin/AdminReportsPage'
import { AdminCustomersPage } from '@/pages/admin/AdminCustomersPage'
import { AdminOwnersPage } from '@/pages/admin/AdminOwnersPage'
import { AdminPropertiesPage } from '@/pages/admin/AdminPropertiesPage'
import { AdminCrmPage } from '@/pages/admin/AdminCrmPage'
import { AdminPlatformCrmPage } from '@/pages/admin/AdminPlatformCrmPage'
import { AdminEmailMarketingPage } from '@/pages/admin/AdminEmailMarketingPage'
import { AdminVisitsPage } from '@/pages/admin/AdminVisitsPage'
import { AdminContractsPage } from '@/pages/admin/AdminContractsPage'
import { AdminCommissionsPage } from '@/pages/admin/AdminCommissionsPage'
import { AdminAccountingPage } from '@/pages/admin/AdminAccountingPage'
import { AdminDevicesPage } from '@/pages/admin/AdminDevicesPage'
import { AdminImpersonationPage } from '@/pages/admin/AdminImpersonationPage'
import { AdminRevenuePage } from '@/pages/admin/AdminRevenuePage'
import { AdminAnalyticsPage } from '@/pages/admin/AdminAnalyticsPage'
import { AdminSystemPage } from '@/pages/admin/AdminSystemPage'
import { AdminExportsPage } from '@/pages/admin/AdminExportsPage'
import { AdminChurnPage } from '@/pages/admin/AdminChurnPage'
import { AdminHealthPage } from '@/pages/admin/AdminHealthPage'
import { AdminVirtualToursStatsPage } from '@/pages/admin/AdminVirtualToursStatsPage'
import { AdminCommunicationInboxPage } from '@/pages/admin/AdminCommunicationInboxPage'
import { AdminDomainOrdersPage } from '@/pages/admin/AdminDomainOrdersPage'
import { VirtualToursPage } from '@/pages/VirtualToursPage'
import { VirtualTourEditorPage } from '@/pages/VirtualTourEditorPage'
import { VirtualTourPreviewPage } from '@/pages/VirtualTourPreviewPage'

const queryClient = new QueryClient({
  defaultOptions: { queries: { retry: 1, staleTime: 30000 } },
})

function PanelSpinner() {
  return (
    <div className="flex min-h-screen items-center justify-center bg-background">
      <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
    </div>
  )
}

function PanelGuestOnly({ children }: { children: React.ReactNode }) {
  const { isAuthenticated, hydrated, user } = useAuthStore()
  if (!hydrated) return <PanelSpinner />
  if (isAuthenticated && isPlatformStaffRole(user?.role)) {
    return <Navigate to="/" replace />
  }
  return <>{children}</>
}

function PanelProtected({ children }: { children: React.ReactNode }) {
  const { isAuthenticated, hydrated, user } = useAuthStore()
  if (!hydrated) return <PanelSpinner />
  if (!isAuthenticated) return <Navigate to="/login" replace />
  if (!isPlatformStaffRole(user?.role)) return <Navigate to="/login" replace />
  return <>{children}</>
}

export function PanelApp() {
  return (
    <QueryClientProvider client={queryClient}>
      <AuthBootstrap>
        <Routes>
          <Route path="/login" element={<PanelGuestOnly><PanelLoginPage /></PanelGuestOnly>} />
          <Route path="/forgot-password" element={<ForgotPasswordPage />} />
          <Route path="/reset-password" element={<ResetPasswordPage />} />
          <Route element={<PanelProtected><AdminLayout /></PanelProtected>}>
            <Route index element={<AdminSuperPanelPage />} />
            <Route path="tenants" element={<AdminOfficesPage />} />
            <Route path="tenants/:id" element={<AdminOfficeDetailPage />} />
            <Route path="users" element={<AdminUsersPage />} />
            <Route path="users/:id" element={<AdminUserDetailPage />} />
            <Route path="customers" element={<AdminCustomersPage />} />
            <Route path="owners" element={<AdminOwnersPage />} />
            <Route path="properties" element={<AdminPropertiesPage />} />
            <Route path="staff" element={<AdminStaffPage />} />
            <Route path="devices" element={<AdminDevicesPage />} />
            <Route path="impersonation" element={<AdminImpersonationPage />} />
            <Route path="subscriptions" element={<AdminSubscriptionsPage />} />
            <Route path="plans" element={<AdminPlansPage />} />
            <Route path="payments" element={<AdminPaymentsPage />} />
            <Route path="wallets" element={<AdminWalletsPage />} />
            <Route path="wallet-transactions" element={<AdminWalletTransactionsPage />} />
            <Route path="coupons" element={<AdminCouponsPage />} />
            <Route path="commissions" element={<AdminCommissionsPage />} />
            <Route path="accounting" element={<AdminAccountingPage />} />
            <Route path="exports" element={<AdminExportsPage />} />
            <Route path="crm" element={<AdminCrmPage />} />
            <Route path="platform-crm" element={<AdminPlatformCrmPage />} />
            <Route path="email-marketing" element={<AdminEmailMarketingPage />} />
            <Route path="visits" element={<AdminVisitsPage />} />
            <Route path="contracts" element={<AdminContractsPage />} />
            <Route path="tickets" element={<AdminTicketsPage />} />
            <Route path="announcements" element={<AdminAnnouncementsPage />} />
            <Route path="blog" element={<AdminBlogListPage />} />
            <Route path="blog/new" element={<AdminBlogEditorPage />} />
            <Route path="blog/:id/edit" element={<AdminBlogEditorPage />} />
            <Route path="downloads" element={<AdminDownloadsPage />} />
            <Route path="settings" element={<AdminSettingsPage />} />
            <Route path="system" element={<AdminSystemPage />} />
            <Route path="audit" element={<AdminAuditPage />} />
            <Route path="reports" element={<AdminReportsPage />} />
            <Route path="analytics" element={<AdminAnalyticsPage />} />
            <Route path="revenue" element={<AdminRevenuePage />} />
            <Route path="churn" element={<AdminChurnPage />} />
            <Route path="health" element={<AdminHealthPage />} />
            <Route path="virtual-tours/stats" element={<AdminVirtualToursStatsPage />} />
            <Route path="virtual-tours/:id/preview" element={<VirtualTourPreviewPage />} />
            <Route path="virtual-tours/:id/edit" element={<VirtualTourEditorPage />} />
            <Route path="virtual-tours" element={<VirtualToursPage />} />
            <Route path="communication" element={<AdminCommunicationInboxPage />} />
            <Route path="domain-orders" element={<AdminDomainOrdersPage />} />
          </Route>
          <Route path="*" element={<Navigate to="/login" replace />} />
        </Routes>
      </AuthBootstrap>
    </QueryClientProvider>
  )
}
