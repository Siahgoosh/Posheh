import { Outlet } from 'react-router-dom'
import { Sidebar } from './Sidebar'
import { NotificationBell } from './NotificationBell'

export function AppLayout() {
  return (
    <div className="min-h-screen bg-background">
      <Sidebar />
      <main className="lg:mr-64">
        <div className="sticky top-0 z-30 flex justify-end p-4 lg:px-8 lg:pt-6 lg:pb-0">
          <NotificationBell />
        </div>
        <div className="container mx-auto max-w-7xl p-4 lg:p-8 lg:pt-4">
          <Outlet />
        </div>
      </main>
    </div>
  )
}
