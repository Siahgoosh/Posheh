import { format as formatJalali } from 'date-fns-jalali/format'
import { parse as parseJalali } from 'date-fns-jalali/parse'
import { Input } from '@/components/ui/input'
import { cn, toEnglishDigits, toPersianDigits } from '@/lib/utils'

interface Props {
  value?: string
  onChange: (isoLocal: string) => void
  className?: string
  /** Include time fields (default true) */
  withTime?: boolean
  id?: string
}

/**
 * Jalali date(+time) input. Stores Gregorian ISO-like local string for API
 * (yyyy-MM-ddTHH:mm) while displaying/editing Shamsi.
 */
export function JalaliDateTimePicker({ value, onChange, className, withTime = true, id }: Props) {
  const date = value ? new Date(value) : null
  const valid = date && !Number.isNaN(date.getTime())

  const jalaliDate = valid ? formatJalali(date, 'yyyy/MM/dd') : ''
  const timePart = valid && withTime ? formatJalali(date, 'HH:mm') : withTime ? '' : '00:00'

  const displayDate = jalaliDate ? toPersianDigits(jalaliDate) : ''
  const displayTime = timePart ? toPersianDigits(timePart) : ''

  const emit = (jDate: string, jTime: string) => {
    const d = toEnglishDigits(jDate).trim()
    const t = toEnglishDigits(jTime || '00:00').trim()
    if (!/^\d{4}\/\d{1,2}\/\d{1,2}$/.test(d)) return
    try {
      const parsed = parseJalali(`${d} ${t.length === 5 ? t : '00:00'}`, 'yyyy/MM/dd HH:mm', new Date())
      if (Number.isNaN(parsed.getTime())) return
      const y = parsed.getFullYear()
      const m = String(parsed.getMonth() + 1).padStart(2, '0')
      const day = String(parsed.getDate()).padStart(2, '0')
      const hh = String(parsed.getHours()).padStart(2, '0')
      const mm = String(parsed.getMinutes()).padStart(2, '0')
      onChange(withTime ? `${y}-${m}-${day}T${hh}:${mm}` : `${y}-${m}-${day}`)
    } catch {
      // ignore partial typing
    }
  }

  return (
    <div className={cn('flex flex-col gap-1', className)}>
      <div className="flex gap-2">
        <Input
          id={id}
          dir="ltr"
          className="flex-1 text-sm"
          placeholder="۱۴۰۴/۰۵/۲۱"
          value={displayDate}
          onChange={(e) => emit(e.target.value, timePart || '00:00')}
          aria-label="تاریخ شمسی"
        />
        {withTime && (
          <Input
            dir="ltr"
            className="w-28 text-sm"
            placeholder="۱۴:۳۰"
            value={displayTime}
            onChange={(e) => emit(jalaliDate || formatJalali(new Date(), 'yyyy/MM/dd'), e.target.value)}
            aria-label="ساعت"
          />
        )}
      </div>
      <p className="text-[10px] text-muted">تاریخ شمسی (مثال: ۱۴۰۴/۰۵/۲۱{withTime ? ' ۱۴:۳۰' : ''})</p>
    </div>
  )
}
