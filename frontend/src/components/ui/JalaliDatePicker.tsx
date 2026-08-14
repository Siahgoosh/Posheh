import { useEffect, useMemo, useRef, useState } from 'react'
import { CalendarDays, ChevronLeft, ChevronRight } from 'lucide-react'
import { format as formatJalali } from 'date-fns-jalali/format'
import { newDate } from 'date-fns-jalali/newDate'
import { parseISO } from 'date-fns-jalali/parseISO'
import { startOfMonth } from 'date-fns-jalali/startOfMonth'
import { getDay } from 'date-fns-jalali/getDay'
import { getDate } from 'date-fns-jalali/getDate'
import { getMonth } from 'date-fns-jalali/getMonth'
import { getYear } from 'date-fns-jalali/getYear'
import { getDaysInMonth } from 'date-fns-jalali/getDaysInMonth'
import { addMonths } from 'date-fns-jalali/addMonths'
import { isSameDay } from 'date-fns-jalali/isSameDay'
import { isToday } from 'date-fns-jalali/isToday'
import { faIR } from 'date-fns-jalali/locale/fa-IR'
import { cn, toPersianDigits } from '@/lib/utils'
import { Button } from '@/components/ui/button'

const WEEKDAYS = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج']
const MONTHS = [
  'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
  'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند',
]

/** Convert Date → API gregorian YYYY-MM-DD (UTC date parts from jalali helpers) */
export function toApiDate(date: Date): string {
  const y = date.getUTCFullYear()
  const m = String(date.getUTCMonth() + 1).padStart(2, '0')
  const d = String(date.getUTCDate()).padStart(2, '0')
  return `${y}-${m}-${d}`
}

export function todayApiDate(): string {
  return toApiDate(newDate(getYear(new Date()), getMonth(new Date()), getDate(new Date())))
}

export function parseApiDate(value?: string | null): Date | null {
  if (!value) return null
  try {
    const d = parseISO(value.slice(0, 10))
    return Number.isNaN(d.getTime()) ? null : d
  } catch {
    return null
  }
}

export function formatJalaliYmd(value?: string | Date | null): string {
  if (!value) return '—'
  const date = typeof value === 'string' ? parseApiDate(value) : value
  if (!date) return '—'
  return toPersianDigits(formatJalali(date, 'yyyy/MM/dd'))
}

export function formatJalaliLong(value?: string | Date | null): string {
  if (!value) return '—'
  const date = typeof value === 'string' ? parseApiDate(value) : value
  if (!date) return '—'
  return toPersianDigits(formatJalali(date, 'd MMMM yyyy', { locale: faIR }))
}

interface JalaliDatePickerProps {
  value?: string
  onChange: (apiDate: string) => void
  label?: string
  placeholder?: string
  className?: string
  disabled?: boolean
}

export function JalaliDatePicker({
  value,
  onChange,
  label,
  placeholder = 'انتخاب تاریخ',
  className,
  disabled,
}: JalaliDatePickerProps) {
  const selected = useMemo(() => parseApiDate(value) ?? null, [value])
  const [open, setOpen] = useState(false)
  const [cursor, setCursor] = useState<Date>(() => selected ?? new Date())
  const rootRef = useRef<HTMLDivElement>(null)

  useEffect(() => {
    if (selected) setCursor(selected)
  }, [selected])

  useEffect(() => {
    if (!open) return
    const onDoc = (e: MouseEvent) => {
      if (!rootRef.current?.contains(e.target as Node)) setOpen(false)
    }
    document.addEventListener('mousedown', onDoc)
    return () => document.removeEventListener('mousedown', onDoc)
  }, [open])

  const year = getYear(cursor)
  const monthIndex = getMonth(cursor)
  const daysInMonth = getDaysInMonth(cursor)
  const start = startOfMonth(cursor)
  // Saturday-first grid: getDay Sun=0 … Sat=6 → offset from Saturday
  const startDow = getDay(start)
  const leading = (startDow + 1) % 7

  const cells: Array<Date | null> = []
  for (let i = 0; i < leading; i++) cells.push(null)
  for (let day = 1; day <= daysInMonth; day++) {
    cells.push(newDate(year, monthIndex, day))
  }

  const display = selected
    ? toPersianDigits(formatJalali(selected, 'yyyy/MM/dd'))
    : ''

  return (
    <div ref={rootRef} className={cn('relative', className)}>
      {label && <label className="text-xs text-muted mb-1 block">{label}</label>}
      <button
        type="button"
        disabled={disabled}
        onClick={() => setOpen((v) => !v)}
        className={cn(
          'flex h-11 w-full items-center justify-between gap-2 rounded-xl border border-card-border bg-white/5 px-4 text-sm transition-colors',
          'focus:border-primary/50 focus:outline-none focus:ring-2 focus:ring-primary/20',
          disabled && 'opacity-50 cursor-not-allowed',
        )}
      >
        <span className={cn(!display && 'text-muted')} dir="ltr">
          {display || placeholder}
        </span>
        <CalendarDays className="h-4 w-4 text-muted shrink-0" />
      </button>

      {open && (
        <div className="absolute z-50 mt-2 w-[280px] rounded-2xl border border-card-border bg-card p-3 shadow-xl left-0">
          <div className="flex items-center justify-between mb-3">
            <Button
              type="button"
              size="sm"
              variant="ghost"
              className="h-8 w-8 p-0"
              onClick={() => setCursor((c) => addMonths(c, -1))}
            >
              <ChevronRight className="h-4 w-4" />
            </Button>
            <span className="text-sm font-medium">
              {MONTHS[monthIndex]} {toPersianDigits(String(year))}
            </span>
            <Button
              type="button"
              size="sm"
              variant="ghost"
              className="h-8 w-8 p-0"
              onClick={() => setCursor((c) => addMonths(c, 1))}
            >
              <ChevronLeft className="h-4 w-4" />
            </Button>
          </div>

          <div className="grid grid-cols-7 gap-1 mb-1">
            {WEEKDAYS.map((d) => (
              <div key={d} className="text-center text-[11px] text-muted py-1">{d}</div>
            ))}
          </div>

          <div className="grid grid-cols-7 gap-1">
            {cells.map((day, idx) => {
              if (!day) return <div key={`e-${idx}`} />
              const active = selected ? isSameDay(day, selected) : false
              const today = isToday(day)
              return (
                <button
                  key={toApiDate(day)}
                  type="button"
                  onClick={() => {
                    onChange(toApiDate(day))
                    setOpen(false)
                  }}
                  className={cn(
                    'h-8 rounded-lg text-sm transition-colors',
                    active && 'bg-primary text-primary-foreground font-bold',
                    !active && today && 'border border-primary/40 text-primary',
                    !active && !today && 'hover:bg-white/10',
                  )}
                >
                  {toPersianDigits(String(getDate(day)))}
                </button>
              )
            })}
          </div>

          <div className="flex justify-between mt-3 pt-2 border-t border-card-border">
            <button
              type="button"
              className="text-xs text-primary"
              onClick={() => {
                const t = newDate(getYear(new Date()), getMonth(new Date()), getDate(new Date()))
                onChange(toApiDate(t))
                setCursor(t)
                setOpen(false)
              }}
            >
              امروز
            </button>
            <button
              type="button"
              className="text-xs text-muted"
              onClick={() => setOpen(false)}
            >
              بستن
            </button>
          </div>
          <p className="text-[10px] text-muted mt-2 text-center">
            {selected ? formatJalali(selected, 'EEEE d MMMM yyyy', { locale: faIR }) : ''}
          </p>
        </div>
      )}
    </div>
  )
}
