import { Navigate, Route, Routes } from 'react-router-dom'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { AuthBootstrap } from '@/components/AuthBootstrap'
import { useAuthStore } from '@/stores/auth'
import { isPlatformStaffRole } from '@/lib/subdomain'
import { AdminLayout } from '@/components/layout/AdminLayout'
import { PanelLoginPage } from '@/pages/admin/PanelLoginPage'
import { AdminSuperPanelPage } from '@/pages/admin/AdminSuperPanelPage'
import { AdminOfficesPage } from '@/pages/admin/AdminOfficesPage'
import { AdminPlansPage } from '@/pages/admin/AdminPlansPage'
import { AdminTicketsPage } from '@/pages/admin/AdminTicketsPage'
import { AdminBlogListPage } from '@/pages/admin/AdminBlogListPage'
import { AdminBlogEditorPage } from '@/pages/admin/AdminBlogEditorPage'
import { AdminDownloadsPage } from '@/pages/admin/AdminDownloadsPage'
import { AdminUsersPage } from '@/pages/admin/AdminUsersPage'
import { AdminPaymentsPage } from '@/pages/admin/AdminPaymentsPage'
import { AdminSubscriptionsPage } from '@/pages/admin/AdminSubscriptionsPage'
import { AdminWalletsPage } from '@/pages/admin/AdminWalletsPage'
import { AdminCouponsPage } from '@/pages/admin/AdminCouponsPage'
import { AdminSettingsPage } from '@/pages/admin/AdminSettingsPage'
import { AdminAuditPage } from '@/pages/admin/AdminAuditPage'
import { AdminAnnouncementsPage } from '@/pages/admin/AdminAnnouncementsPage'
import { AdminStaffPage } from '@/pages/admin/AdminStaffPage'
import { AdminReportsPage } from '@/pages/admin/AdminReportsPage'

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

/** مهمان → فقط صفحه ورود؛ هر مسیر دیگر → /login */
function PanelGuestOnly({ children }: { children: React.ReactNode }) {
  const { isAuthenticated, hydrated, user } = useAuthStore()
  if (!hydrated) return <PanelSpinner />
  if (isAuthenticated && isPlatformStaffRole(user?.role)) {
    return <Navigate to="/" replace />
  }
  return <>{children}</>
}

/** فقط مدیران پلتفرم — داشبورد مدیریت */
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
          <Route
            path="/login"
            element={
              <PanelGuestOnly>
                <PanelLoginPage />
              </PanelGuestOnly>
            }
          />
          <Route
            element={
              <PanelProtected>
                <AdminLayout />
              </PanelProtected>
            }
          >
            <Route index element={<AdminSuperPanelPage />} />
            <Route path="tenants" element={<AdminOfficesPage />} />
            <Route path="users" element={<AdminUsersPage />} />
            <Route path="staff" element={<AdminStaffPage />} />
            <Route path="subscriptions" element={<AdminSubscriptionsPage />} />
            <Route path="plans" element={<AdminPlansPage />} />
            <Route path="payments" element={<AdminPaymentsPage />} />
            <Route path="wallets" element={<AdminWalletsPage />} />
            <Route path="coupons" element={<AdminCouponsPage />} />
            <Route path="tickets" element={<AdminTicketsPage />} />
            <Route path="announcements" element={<AdminAnnouncementsPage />} />
            <Route path="blog" element={<AdminBlogListPage />} />
            <Route path="blog/new" element={<AdminBlogEditorPage />} />
            <Route path="blog/:id/edit" element={<AdminBlogEditorPage />} />
            <Route path="downloads" element={<AdminDownloadsPage />} />
            <Route path="settings" element={<AdminSettingsPage />} />
            <Route path="audit" element={<AdminAuditPage />} />
            <Route path="reports" element={<AdminReportsPage />} />
          </Route>
          {/* هر مسیر دیگر (شامل لندینگ، ثبت‌نام، …) → ورود یا داشبورد */}
          <Route path="*" element={<Navigate to="/login" replace />} />
        </Routes>
      </AuthBootstrap>
    </QueryClientProvider>
  )
}
