import { Outlet } from 'react-router-dom'
import { Sidebar } from './Sidebar'

export function AppLayout() {
  return (
    <div className="min-h-screen bg-background">
      <Sidebar />
      <main className="lg:mr-60 xl:mr-64 min-h-screen">
        <div className="container mx-auto max-w-7xl p-4 pt-14 sm:p-6 lg:p-8 lg:pt-8">
          <Outlet />
        </div>
      </main>
    </div>
  )
}
