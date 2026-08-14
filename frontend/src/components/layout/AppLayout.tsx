import { Outlet } from 'react-router-dom'
import { Sidebar } from './Sidebar'
import { ContentPlannerBell } from '@/pages/ContentPlannerPage'

export function AppLayout() {
  return (
    <div className="min-h-screen bg-background">
      <Sidebar />
      <main className="lg:mr-60 xl:mr-64 min-h-screen">
        <div className="container mx-auto max-w-7xl p-4 pt-14 sm:p-6 lg:p-8 lg:pt-8">
          <div className="flex justify-end mb-2 lg:mb-0">
            <ContentPlannerBell />
          </div>
          <Outlet />
        </div>
      </main>
    </div>
  )
}
