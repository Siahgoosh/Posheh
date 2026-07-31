import { NavLink } from 'react-router-dom'
import {
  LayoutDashboard, Building2, Users, CreditCard, Wallet, Ticket, Settings,
  BookOpen, Download, Shield, BarChart3, Tag, Bell, ScrollText,
  LogOut, Moon, Sun, Menu, X, UserCog, ChevronDown,
  UserCircle, Home, Handshake, Calendar, FileText, Percent,
  Calculator, Smartphone, TrendingDown, DollarSign, Database,
  Activity, Flag, DownloadCloud, Heart, Globe,
} from 'lucide-react'
import { useEffect, useState } from 'react'
import { cn } from '@/lib/utils'
import { useAuthStore, useThemeStore } from '@/stores/auth'
import { Button } from '@/components/ui/button'
import { CAPABILITY_COUNT } from '@/constants/adminCapabilities'

const navGroups = [
  {
    label: 'نمای کلی',
    items: [
      { to: '/', icon: LayoutDashboard, label: 'داشبورد' },
      { to: '/reports', icon: BarChart3, label: 'گزارش‌ها' },
      { to: '/analytics', icon: Activity, label: 'تحلیل‌ها' },
      { to: '/revenue', icon: DollarSign, label: 'درآمدها' },
      { to: '/churn', icon: TrendingDown, label: 'ریسک ریزش' },
    ],
  },
  {
    label: 'مدیریت پلتفرم',
    items: [
      { to: '/health', icon: Heart, label: 'Health Score' },
      { to: '/virtual-tours', icon: Globe, label: 'تور مجازی' },
      { to: '/tenants', icon: Building2, label: 'دفاتر' },
      { to: '/users', icon: Users, label: 'کاربران' },
      { to: '/customers', icon: UserCircle, label: 'مشتریان' },
      { to: '/owners', icon: Users, label: 'مالکان' },
      { to: '/properties', icon: Home, label: 'املاک' },
      { to: '/staff', icon: UserCog, label: 'مدیران پلتفرم' },
      { to: '/devices', icon: Smartphone, label: 'دستگاه‌ها' },
      { to: '/impersonation', icon: Shield, label: 'Impersonation' },
    ],
  },
  {
    label: 'مالی',
    items: [
      { to: '/subscriptions', icon: CreditCard, label: 'اشتراک‌ها' },
      { to: '/plans', icon: Shield, label: 'پلن‌ها' },
      { to: '/payments', icon: Wallet, label: 'پرداخت‌ها' },
      { to: '/wallets', icon: Wallet, label: 'کیف پول' },
      { to: '/wallet-transactions', icon: Database, label: 'تراکنش‌ها' },
      { to: '/coupons', icon: Tag, label: 'کوپن‌ها' },
      { to: '/commissions', icon: Percent, label: 'کمیسیون' },
      { to: '/accounting', icon: Calculator, label: 'حسابداری' },
      { to: '/exports', icon: DownloadCloud, label: 'خروجی CSV' },
    ],
  },
  {
    label: 'عملیات',
    items: [
      { to: '/crm', icon: Handshake, label: 'CRM' },
      { to: '/visits', icon: Calendar, label: 'بازدیدها' },
      { to: '/contracts', icon: FileText, label: 'قراردادها' },
    ],
  },
  {
    label: 'پشتیبانی و محتوا',
    items: [
      { to: '/tickets', icon: Ticket, label: 'تیکت‌ها' },
      { to: '/announcements', icon: Bell, label: 'اطلاعیه‌ها' },
      { to: '/blog', icon: BookOpen, label: 'وبلاگ' },
      { to: '/downloads', icon: Download, label: 'دانلود اپ' },
    ],
  },
  {
    label: 'سیستم',
    items: [
      { to: '/settings', icon: Settings, label: 'تنظیمات' },
      { to: '/system', icon: Flag, label: 'Feature Flags' },
      { to: '/audit', icon: ScrollText, label: 'لاگ ممیزی' },
    ],
  },
]

export function AdminSidebar() {
  const [mobileOpen, setMobileOpen] = useState(false)
  const [collapsed, setCollapsed] = useState(false)
  const { user, logout } = useAuthStore()
  const { theme, toggleTheme } = useThemeStore()

  useEffect(() => {
    document.body.style.overflow = mobileOpen ? 'hidden' : ''
    return () => { document.body.style.overflow = '' }
  }, [mobileOpen])

  const linkClass = (active: boolean) => cn(
    'flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition-all duration-200',
    active ? 'bg-primary/15 text-primary' : 'text-muted hover:bg-white/5 hover:text-foreground',
  )

  const sidebar = (
    <div className="flex h-full min-h-0 flex-col">
      <div className="flex items-center gap-3 px-4 py-4 shrink-0 border-b border-card-border/50">
        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-primary to-accent">
          <Shield className="h-5 w-5 text-white" />
        </div>
        {!collapsed && (
          <div className="min-w-0 flex-1">
            <h1 className="text-base font-bold gradient-text truncate">پنل مدیریت پوشه</h1>
            <p className="text-xs text-muted truncate">{CAPABILITY_COUNT}+ قابلیت · SaaS</p>
          </div>
        )}
        <button type="button" className="lg:hidden text-muted p-1" onClick={() => setMobileOpen(false)}>
          <X className="h-5 w-5" />
        </button>
      </div>

      <nav className="flex-1 min-h-0 overflow-y-auto px-2 py-2 space-y-4">
        {navGroups.map((group) => (
          <div key={group.label}>
            {!collapsed && (
              <p className="px-3 py-1 text-[10px] font-semibold uppercase tracking-wider text-muted/70">
                {group.label}
              </p>
            )}
            <div className="space-y-0.5">
              {group.items.map((item) => (
                <NavLink
                  key={item.to}
                  to={item.to}
                  end={item.to === '/'}
                  onClick={() => setMobileOpen(false)}
                  className={({ isActive }) => linkClass(isActive)}
                  title={collapsed ? item.label : undefined}
                >
                  <item.icon className="h-4 w-4 shrink-0" />
                  {!collapsed && <span className="truncate">{item.label}</span>}
                </NavLink>
              ))}
            </div>
          </div>
        ))}
      </nav>

      <div className="shrink-0 border-t border-card-border p-3 space-y-2">
        <div className="flex items-center gap-2 px-1">
          <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-accent/20 text-sm font-medium text-accent">
            {user?.name?.charAt(0)}
          </div>
          {!collapsed && (
            <div className="min-w-0 flex-1">
              <p className="text-sm font-medium truncate">{user?.name}</p>
              <p className="text-xs text-muted truncate">{user?.role_label}</p>
            </div>
          )}
        </div>
        <div className="flex gap-1">
          <Button variant="ghost" size="sm" className="flex-1" onClick={toggleTheme}>
            {theme === 'dark' ? <Sun className="h-4 w-4" /> : <Moon className="h-4 w-4" />}
          </Button>
          <Button variant="ghost" size="sm" className="flex-1 text-danger" onClick={() => logout()}>
            <LogOut className="h-4 w-4" />
          </Button>
        </div>
        <button
          type="button"
          className="hidden lg:flex w-full items-center justify-center gap-1 text-xs text-muted py-1"
          onClick={() => setCollapsed((v) => !v)}
        >
          <ChevronDown className={cn('h-3 w-3 transition-transform', collapsed && '-rotate-90')} />
          {!collapsed && 'جمع کردن منو'}
        </button>
      </div>
    </div>
  )

  return (
    <>
      <button
        type="button"
        className="lg:hidden fixed top-3 right-3 z-50 rounded-xl bg-card/90 border border-card-border p-2 backdrop-blur"
        onClick={() => setMobileOpen(true)}
      >
        <Menu className="h-5 w-5" />
      </button>

      <aside className={cn(
        'hidden lg:flex flex-col fixed inset-y-0 right-0 z-40 border-l border-card-border bg-background/95 backdrop-blur-xl transition-all',
        collapsed ? 'w-[72px]' : 'w-60 xl:w-64',
      )}>
        {sidebar}
      </aside>

      {mobileOpen && (
        <div className="lg:hidden fixed inset-0 z-50">
          <div className="absolute inset-0 bg-black/60" onClick={() => setMobileOpen(false)} />
          <aside className="absolute inset-y-0 right-0 w-72 max-w-[85vw] bg-background border-l border-card-border shadow-xl">
            {sidebar}
          </aside>
        </div>
      )}
    </>
  )
}
