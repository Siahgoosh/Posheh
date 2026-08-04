import { Outlet } from 'react-router-dom'
import { Sidebar } from './Sidebar'
import { NotificationBell } from '@/components/notifications/NotificationBell'

export function AppLayout() {
  return (
    <div className="min-h-[100dvh] bg-background overflow-x-hidden">
      <Sidebar />
      <main className="lg:mr-60 xl:mr-64 min-h-[100dvh] w-full max-w-[100vw]">
        <header
          className="fixed top-0 left-0 right-0 z-40 flex items-center justify-between gap-3 px-3 sm:px-4 pt-[max(0.5rem,env(safe-area-inset-top))] h-[calc(3.25rem+env(safe-area-inset-top))] border-b border-card-border/60 bg-background/90 backdrop-blur-md lg:hidden"
        >
          <NotificationBell />
          <span className="text-sm font-bold gradient-text truncate">پوشه</span>
          <div className="w-10 shrink-0" aria-hidden />
        </header>

        <div
          className="container mx-auto max-w-7xl w-full px-3 sm:px-6 lg:px-8 pt-[calc(3.25rem+env(safe-area-inset-top))] lg:pt-8 pb-[max(1rem,env(safe-area-inset-bottom))]"
        >
          <div className="hidden lg:block fixed top-4 left-6 z-30">
            <NotificationBell />
          </div>
          <Outlet />
        </div>
      </main>
    </div>
  )
}
