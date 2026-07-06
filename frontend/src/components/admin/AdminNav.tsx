import { NavLink } from 'react-router-dom'
import { Shield, Building2, DollarSign, MessageSquare, Settings } from 'lucide-react'
import { cn } from '@/lib/utils'

const items = [
  { to: '/admin', icon: Shield, label: 'داشبورد', end: true },
  { to: '/admin/offices', icon: Building2, label: 'دفاتر' },
  { to: '/admin/finance', icon: DollarSign, label: 'مالی' },
  { to: '/admin/tickets', icon: MessageSquare, label: 'تیکت‌ها' },
  { to: '/admin/settings', icon: Settings, label: 'تنظیمات' },
]

export function AdminNav() {
  return (
    <nav className="flex flex-wrap gap-2 mb-6">
      {items.map((item) => (
        <NavLink
          key={item.to}
          to={item.to}
          end={item.end}
          className={({ isActive }) =>
            cn(
              'inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-medium transition-colors',
              isActive ? 'bg-primary/15 text-primary' : 'glass glass-hover text-muted'
            )
          }
        >
          <item.icon className="h-4 w-4" />
          {item.label}
        </NavLink>
      ))}
    </nav>
  )
}
