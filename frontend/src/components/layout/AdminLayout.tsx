import { Outlet } from 'react-router-dom'
import { AdminSidebar } from './AdminSidebar'
import { AdminTopbar } from './AdminTopbar'

export function AdminLayout() {
  return (
    <div className="min-h-screen bg-background">
      <AdminSidebar />
      <main className="lg:mr-60 xl:mr-64 min-h-screen">
        <AdminTopbar />
        <div className="container mx-auto max-w-7xl p-4 pt-14 sm:p-6 lg:p-8 lg:pt-4">
          <Outlet />
        </div>
      </main>
    </div>
  )
}
