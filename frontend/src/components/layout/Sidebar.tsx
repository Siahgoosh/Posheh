import { NavLink } from 'react-router-dom'
import {
  LayoutDashboard,
  Building2,
  Search,
  Users,
  Settings,
  CreditCard,
  Star,
  LogOut,
  Moon,
  Sun,
  Menu,
  X,
  BookOpen,
  Download,
  Shield,
  BarChart3,
  Building,
  Kanban,
  Wallet,
  FileText,
  LifeBuoy,
  UserCircle,
  CalendarDays,
  Contact,
} from 'lucide-react'
import { usePlanFeature } from '@/components/SubscriptionGuard'
import { useState } from 'react'
import { cn } from '@/lib/utils'
import { useAuthStore, useThemeStore } from '@/stores/auth'
import { Button } from '@/components/ui/button'

export function Sidebar() {
  const [mobileOpen, setMobileOpen] = useState(false)
  const { user, logout } = useAuthStore()
  const { theme, toggleTheme } = useThemeStore()
  const hasTeam = usePlanFeature('team')
  const hasAccounting = usePlanFeature('accounting')
  const hasCrm = usePlanFeature('crm')
  const onTrial = user?.office?.on_trial

  const navItems = [
    { to: '/dashboard', icon: LayoutDashboard, label: 'داشبورد' },
    { to: '/properties', icon: Building2, label: 'املاک' },
    { to: '/owners', icon: UserCircle, label: 'مالکین' },
    { to: '/customers', icon: Contact, label: 'مشتریان' },
    { to: '/visits', icon: CalendarDays, label: 'بازدیدها' },
    { to: '/search', icon: Search, label: 'جستجو' },
    { to: '/favorites', icon: Star, label: 'علاقه‌مندی‌ها' },
    ...(hasCrm ? [{ to: '/crm', icon: Kanban, label: 'CRM' }] : []),
    ...(hasAccounting ? [{ to: '/accounting', icon: Wallet, label: 'حسابداری' }] : []),
    { to: '/reports', icon: BarChart3, label: 'گزارش‌ها' },
    { to: '/commissions', icon: Wallet, label: 'کمیسیون' },
    { to: '/contracts', icon: FileText, label: 'قراردادها' },
    ...(hasTeam ? [{ to: '/team', icon: Users, label: 'تیم' }] : []),
    { to: '/tickets', icon: LifeBuoy, label: 'پشتیبانی' },
    { to: '/subscription', icon: CreditCard, label: 'اشتراک' },
    { to: '/settings', icon: Settings, label: 'تنظیمات' },
  ]

  const adminItems =
    user?.role === 'super_admin'
      ? [
          { to: '/admin', icon: BarChart3, label: 'پنل مدیر کل' },
          { to: '/admin/plans', icon: CreditCard, label: 'پلن‌ها و قیمت‌ها' },
          { to: '/admin/offices', icon: Building, label: 'دفاتر و کاربران' },
          { to: '/admin/tickets', icon: LifeBuoy, label: 'تیکت‌ها' },
          { to: '/admin/blog', icon: BookOpen, label: 'مدیریت وبلاگ' },
          { to: '/admin/downloads', icon: Download, label: 'مدیریت دانلودها' },
        ]
      : []

  const sidebarContent = (
    <div className="flex h-full flex-col">
      <div className="flex items-center gap-3 px-6 py-6">
        <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-primary to-accent">
          <Building2 className="h-5 w-5 text-white" />
        </div>
        <div>
          <h1 className="text-lg font-bold gradient-text">پوشه</h1>
          <p className="text-xs text-muted">{user?.office?.name || 'سامانه املاک'}</p>
          {onTrial && user?.office?.trial_days_remaining !== undefined && (
            <p className="text-xs text-warning">{user.office.trial_days_remaining} روز آزمایشی</p>
          )}
        </div>
      </div>

      <nav className="flex-1 space-y-1 px-3">
        {navItems.map((item) => (
          <NavLink
            key={item.to}
            to={item.to}
            onClick={() => setMobileOpen(false)}
            className={({ isActive }) =>
              cn(
                'flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-medium transition-all duration-200',
                isActive
                  ? 'bg-primary/15 text-primary'
                  : 'text-muted hover:bg-white/5 hover:text-foreground'
              )
            }
          >
            <item.icon className="h-4 w-4" />
            {item.label}
          </NavLink>
        ))}
        {adminItems.length > 0 && (
          <>
            <div className="flex items-center gap-2 px-4 pt-4 pb-1 text-xs text-muted">
              <Shield className="h-3 w-3" />
              مدیر کل
            </div>
            {adminItems.map((item) => (
              <NavLink
                key={item.to}
                to={item.to}
                onClick={() => setMobileOpen(false)}
                className={({ isActive }) =>
                  cn(
                    'flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-medium transition-all duration-200',
                    isActive
                      ? 'bg-accent/15 text-accent'
                      : 'text-muted hover:bg-white/5 hover:text-foreground'
                  )
                }
              >
                <item.icon className="h-4 w-4" />
                {item.label}
              </NavLink>
            ))}
          </>
        )}
      </nav>

      <div className="border-t border-card-border p-4 space-y-2">
        <div className="flex items-center gap-3 px-2 py-2">
          <div className="flex h-9 w-9 items-center justify-center rounded-full bg-primary/20 text-sm font-medium text-primary">
            {user?.name?.charAt(0)}
          </div>
          <div className="flex-1 min-w-0">
            <p className="text-sm font-medium truncate">{user?.name}</p>
            <p className="text-xs text-muted">{user?.role_label}</p>
          </div>
        </div>
        <div className="flex gap-2">
          <Button variant="ghost" size="sm" onClick={toggleTheme} className="flex-1">
            {theme === 'dark' ? <Sun className="h-4 w-4" /> : <Moon className="h-4 w-4" />}
          </Button>
          <Button variant="ghost" size="sm" onClick={() => logout().then(() => window.location.href = '/login')} className="flex-1 text-danger">
            <LogOut className="h-4 w-4" />
          </Button>
        </div>
      </div>
    </div>
  )

  return (
    <>
      <Button
        variant="ghost"
        size="icon"
        className="fixed top-4 right-4 z-50 lg:hidden"
        onClick={() => setMobileOpen(!mobileOpen)}
      >
        {mobileOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
      </Button>

      <aside className="hidden lg:fixed lg:inset-y-0 lg:right-0 lg:flex lg:w-64 lg:flex-col glass border-l border-card-border">
        {sidebarContent}
      </aside>

      {mobileOpen && (
        <div className="fixed inset-0 z-40 lg:hidden">
          <div className="absolute inset-0 bg-black/60 backdrop-blur-sm" onClick={() => setMobileOpen(false)} />
          <aside className="absolute inset-y-0 right-0 w-72 glass animate-fade-in">
            {sidebarContent}
          </aside>
        </div>
      )}
    </>
  )
}
