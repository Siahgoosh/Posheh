import { NavLink } from 'react-router-dom'
import {
  LayoutDashboard, Building2, Search, Users, Settings, CreditCard, Star, LogOut,
  Moon, Sun, Menu, X, BookOpen, Download, Shield, BarChart3, Building,
  Kanban, Wallet, FileText, LifeBuoy, UserCircle, CalendarDays, Contact, Globe, ChevronDown,
  Bookmark, History, GitCompare, Tag,
} from 'lucide-react'
import { usePlanFeature } from '@/components/SubscriptionGuard'
import { useEffect, useState } from 'react'
import { cn } from '@/lib/utils'
import { useAuthStore, useThemeStore } from '@/stores/auth'
import { Button } from '@/components/ui/button'
import { NotificationBell } from '@/components/NotificationBell'

export function Sidebar() {
  const [mobileOpen, setMobileOpen] = useState(false)
  const [adminOpen, setAdminOpen] = useState(true)
  const { user, logout } = useAuthStore()
  const { theme, toggleTheme } = useThemeStore()
  const hasTeam = usePlanFeature('team')
  const hasAccounting = usePlanFeature('accounting')
  const hasCrm = usePlanFeature('crm')
  const hasWebsite = usePlanFeature('website_listing')
  const hasSavedSearches = usePlanFeature('saved_searches')
  const hasActivityLogs = usePlanFeature('activity_logs')
  const hasPropertyCompare = usePlanFeature('property_compare')
  const hasCommissions = usePlanFeature('commissions')
  const onTrial = user?.office?.on_trial
  const expired = user?.office?.subscription_expired === true || user?.office?.has_access === false

  useEffect(() => {
    document.body.style.overflow = mobileOpen ? 'hidden' : ''
    return () => { document.body.style.overflow = '' }
  }, [mobileOpen])

  const navItems = [
    { to: '/dashboard', icon: LayoutDashboard, label: 'داشبورد' },
    { to: '/properties', icon: Building2, label: 'املاک' },
    { to: '/owners', icon: UserCircle, label: 'مالکین' },
    { to: '/customers', icon: Contact, label: 'مشتریان' },
    { to: '/visits', icon: CalendarDays, label: 'بازدیدها' },
    { to: '/search', icon: Search, label: 'جستجو' },
    ...(hasSavedSearches ? [{ to: '/saved-searches', icon: Bookmark, label: 'جستجوهای ذخیره' }] : []),
    { to: '/favorites', icon: Star, label: 'علاقه‌مندی‌ها' },
    ...(hasCrm ? [{ to: '/crm', icon: Kanban, label: 'CRM' }] : []),
    ...(hasAccounting ? [{ to: '/accounting', icon: Wallet, label: 'حسابداری' }] : []),
    { to: '/reports', icon: BarChart3, label: 'گزارش‌ها' },
    ...(hasActivityLogs ? [{ to: '/activity-logs', icon: History, label: 'گزارش فعالیت' }] : []),
    ...(hasPropertyCompare ? [{ to: '/property-compare', icon: GitCompare, label: 'مقایسه ملک' }] : []),
    ...(hasCommissions ? [{ to: '/commissions', icon: Wallet, label: 'کمیسیون' }] : []),
    { to: '/contracts', icon: FileText, label: 'قراردادها' },
    ...(hasTeam ? [{ to: '/team', icon: Users, label: 'تیم' }] : []),
    ...(hasWebsite ? [{ to: '/office-website', icon: Globe, label: 'وبسایت دفتر' }] : []),
    { to: '/tickets', icon: LifeBuoy, label: 'پشتیبانی' },
    { to: '/subscription', icon: CreditCard, label: 'اشتراک' },
    { to: '/settings', icon: Settings, label: 'تنظیمات' },
  ]

  const adminItems = user?.role === 'super_admin'
    ? [
        { to: '/admin', icon: BarChart3, label: 'پنل مدیر کل' },
        { to: '/admin/settings', icon: Settings, label: 'تنظیمات سیستم' },
        { to: '/admin/broadcasts', icon: LifeBuoy, label: 'اعلان‌ها' },
        { to: '/admin/plans', icon: CreditCard, label: 'پلن‌ها' },
        { to: '/admin/payment-leads', icon: Wallet, label: 'سرنخ پرداخت' },
        { to: '/admin/discount-codes', icon: Tag, label: 'کد تخفیف' },
        { to: '/admin/offices', icon: Building, label: 'دفاتر' },
        { to: '/admin/tickets', icon: LifeBuoy, label: 'تیکت‌ها' },
        { to: '/admin/blog', icon: BookOpen, label: 'وبلاگ' },
        { to: '/admin/downloads', icon: Download, label: 'دانلودها' },
      ]
    : []

  const linkClass = (active: boolean, accent = false) => cn(
    'flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition-all duration-200 shrink-0',
    active
      ? accent ? 'bg-accent/15 text-accent' : 'bg-primary/15 text-primary'
      : 'text-muted hover:bg-white/5 hover:text-foreground'
  )

  const sidebarContent = (
    <div className="flex h-full min-h-0 flex-col">
      <div className="flex items-center gap-3 px-4 py-4 shrink-0 border-b border-card-border/50">
        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-primary to-accent">
          <Building2 className="h-5 w-5 text-white" />
        </div>
        <div className="min-w-0 flex-1">
          <h1 className="text-base font-bold gradient-text truncate">پوشه</h1>
          <p className="text-xs text-muted truncate">{user?.office?.name || 'سامانه املاک'}</p>
          {onTrial && user?.office?.trial_label && (
            <p className="text-[10px] text-warning">{user.office.trial_label}</p>
          )}
        </div>
        <button type="button" className="lg:hidden text-muted p-1" onClick={() => setMobileOpen(false)}>
          <X className="h-5 w-5" />
        </button>
        <NotificationBell />
      </div>

      {expired && (
        <div className="px-3 pb-2">
          <NavLink to="/renew" onClick={() => setMobileOpen(false)}>
            <Button className="w-full" size="sm">تمدید اشتراک</Button>
          </NavLink>
        </div>
      )}

      <nav className="flex-1 min-h-0 overflow-y-auto overscroll-contain px-2 py-2 space-y-0.5">
        {navItems.map((item) => (
          <NavLink key={item.to} to={item.to} onClick={() => setMobileOpen(false)}
            className={({ isActive }) => linkClass(isActive)}>
            <item.icon className="h-4 w-4 shrink-0" />
            <span className="truncate">{item.label}</span>
          </NavLink>
        ))}

        {adminItems.length > 0 && (
          <div className="pt-2 mt-2 border-t border-card-border/50">
            <button
              type="button"
              onClick={() => setAdminOpen((v) => !v)}
              className="flex w-full items-center justify-between px-3 py-2 text-xs text-muted"
            >
              <span className="flex items-center gap-2"><Shield className="h-3 w-3" /> مدیر کل</span>
              <ChevronDown className={cn('h-3 w-3 transition-transform', adminOpen && 'rotate-180')} />
            </button>
            {adminOpen && adminItems.map((item) => (
              <NavLink key={item.to} to={item.to} onClick={() => setMobileOpen(false)}
                className={({ isActive }) => linkClass(isActive, true)}>
                <item.icon className="h-4 w-4 shrink-0" />
                <span className="truncate">{item.label}</span>
              </NavLink>
            ))}
          </div>
        )}
      </nav>

      <div className="shrink-0 border-t border-card-border p-3 space-y-2">
        <div className="flex items-center gap-2 px-1">
          <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/20 text-sm font-medium text-primary">
            {user?.name?.charAt(0)}
          </div>
          <div className="min-w-0 flex-1">
            <p className="text-sm font-medium truncate">{user?.name}</p>
            <p className="text-[10px] text-muted truncate">{user?.role_label}</p>
          </div>
        </div>
        <div className="flex gap-2">
          <Button variant="ghost" size="sm" onClick={toggleTheme} className="flex-1 h-9">
            {theme === 'dark' ? <Sun className="h-4 w-4" /> : <Moon className="h-4 w-4" />}
          </Button>
          <Button variant="ghost" size="sm" onClick={() => logout().then(() => { window.location.href = '/login' })} className="flex-1 h-9 text-danger">
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
        className="fixed top-3 right-3 z-50 lg:hidden glass"
        onClick={() => setMobileOpen(true)}
        aria-label="منو"
      >
        <Menu className="h-5 w-5" />
      </Button>

      <aside className="hidden lg:fixed lg:inset-y-0 lg:right-0 lg:flex lg:w-60 xl:w-64 lg:flex-col glass border-l border-card-border z-30">
        {sidebarContent}
      </aside>

      {mobileOpen && (
        <div className="fixed inset-0 z-50 lg:hidden">
          <div className="absolute inset-0 bg-black/70 backdrop-blur-sm" onClick={() => setMobileOpen(false)} />
          <aside className="absolute inset-y-0 right-0 w-[min(85vw,18rem)] glass border-l border-card-border shadow-2xl animate-fade-in flex flex-col">
            {sidebarContent}
          </aside>
        </div>
      )}
    </>
  )
}
