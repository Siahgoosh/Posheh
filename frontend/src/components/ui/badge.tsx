import * as React from 'react'
import { cn } from '@/lib/utils'

const Badge = React.forwardRef<
  HTMLSpanElement,
  React.HTMLAttributes<HTMLSpanElement> & { variant?: 'default' | 'success' | 'warning' | 'danger' | 'outline' }
>(({ className, variant = 'default', ...props }, ref) => {
  const variants = {
    default: 'bg-primary/20 text-primary border-primary/30',
    success: 'bg-success/20 text-success border-success/30',
    warning: 'bg-warning/20 text-warning border-warning/30',
    danger: 'bg-danger/20 text-danger border-danger/30',
    outline: 'bg-transparent border-card-border text-muted',
  }

  return (
    <span
      ref={ref}
      className={cn(
        'inline-flex items-center rounded-lg border px-2.5 py-0.5 text-xs font-medium',
        variants[variant],
        className
      )}
      {...props}
    />
  )
})
Badge.displayName = 'Badge'

export { Badge }
