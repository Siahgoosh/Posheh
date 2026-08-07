import { Outlet } from 'react-router-dom'
import { AdminSidebar } from './AdminSidebar'
import { AdminTopbar } from './AdminTopbar'

export function AdminLayout() {
  return (
    <div className="min-h-[100dvh] bg-background overflow-x-hidden">
      <AdminSidebar />
      <main className="lg:mr-60 xl:mr-64 min-h-[100dvh] min-w-0 overflow-x-hidden">
        <AdminTopbar />
        <div className="mx-auto w-full min-w-0 max-w-7xl px-3 sm:px-6 lg:px-8 pt-[calc(3.25rem+env(safe-area-inset-top))] lg:pt-4 pb-[max(1rem,env(safe-area-inset-bottom))]">
          <Outlet />
        </div>
      </main>
    </div>
  )
}
