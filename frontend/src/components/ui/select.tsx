import { cn } from '@/lib/utils'

interface SelectProps extends React.SelectHTMLAttributes<HTMLSelectElement> {
  children: React.ReactNode
}

export function Select({ className, children, ...props }: SelectProps) {
  return (
    <select
      className={cn(
        'flex h-11 w-full appearance-none rounded-xl border border-card-border bg-background px-4 text-sm text-foreground',
        'focus:border-primary/50 focus:outline-none focus:ring-2 focus:ring-primary/20',
        'disabled:cursor-not-allowed disabled:opacity-50',
        className,
      )}
      {...props}
    >
      {children}
    </select>
  )
}

export function SelectOption({ className, children, ...props }: React.OptionHTMLAttributes<HTMLOptionElement>) {
  return (
    <option className={cn('bg-background text-foreground', className)} {...props}>
      {children}
    </option>
  )
}
