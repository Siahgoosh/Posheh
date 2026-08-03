import { useQuery } from '@tanstack/react-query'
import { Input } from '@/components/ui/input'
import { Select, SelectOption } from '@/components/ui/select'
import { JalaliDatePicker } from '@/components/ui/JalaliDatePicker'
import { IRAN_PROVINCES } from '@/constants/property'
import api from '@/lib/api'
import { HIDDEN_FILING_KEYS, type FilingField, type FilingFormValues } from '@/lib/filing'

interface Props {
  fields: FilingField[]
  values: FilingFormValues
  onChange: (key: string, value: string | boolean | string[]) => void
}

export function DynamicFilingFields({ fields, values, onChange }: Props) {
  const visibleFields = fields.filter((f) => !HIDDEN_FILING_KEYS.has(f.key))
  const needsTeam = visibleFields.some((f) => f.type === 'user_select')

  const { data: team } = useQuery({
    queryKey: ['office-team'],
    queryFn: async () => {
      const res = await api.get('/office/team')
      return res.data.data as { id: number; name: string; mobile: string; role: string }[]
    },
    enabled: needsTeam,
  })

  if (!visibleFields.length) return null

  return (
    <div className="grid sm:grid-cols-2 gap-4">
      {visibleFields.map((field) => (
        <FieldRenderer
          key={field.key}
          field={field}
          values={values}
          onChange={onChange}
          team={team}
        />
      ))}
    </div>
  )
}

function FieldRenderer({
  field,
  values,
  onChange,
  team,
}: {
  field: FilingField
  values: FilingFormValues
  onChange: (key: string, value: string | boolean | string[]) => void
  team?: { id: number; name: string; mobile: string; role: string }[]
}) {
  const val = values[field.key]
  const span = field.type === 'textarea' ? 'sm:col-span-2' : ''

  if (field.type === 'boolean') {
    return (
      <label className={`flex items-center gap-2 text-sm cursor-pointer ${span}`}>
        <input
          type="checkbox"
          checked={!!val}
          onChange={(e) => onChange(field.key, e.target.checked)}
          className="rounded border-card-border accent-primary"
        />
        {field.label}
      </label>
    )
  }

  if (field.type === 'user_select') {
    return (
      <div className={span}>
        <label className="text-sm text-muted mb-1 block">
          {field.label}{field.required ? ' *' : ''}
        </label>
        <Select
          value={val != null && val !== '' ? String(val) : ''}
          onChange={(e) => onChange(field.key, e.target.value)}
        >
          <SelectOption value="">انتخاب مشاور</SelectOption>
          {team?.map((m) => (
            <SelectOption key={m.id} value={String(m.id)}>
              {m.name || m.mobile}
            </SelectOption>
          ))}
        </Select>
        <p className="text-[11px] text-muted mt-1">از لیست مشاوران دفتر انتخاب کنید</p>
      </div>
    )
  }

  if (field.type === 'jalali_date') {
    return (
      <div className={span}>
        <JalaliDatePicker
          label={field.label}
          required={field.required}
          value={val ? String(val) : ''}
          onChange={(iso) => onChange(field.key, iso)}
        />
        <p className="text-[11px] text-muted mt-1">تاریخ شمسی — پس از این تاریخ فایل منقضی می‌شود</p>
      </div>
    )
  }

  if (field.type === 'select' || field.type === 'province_select') {
    const options = field.type === 'province_select'
      ? IRAN_PROVINCES.map((p) => ({ value: p, label: p }))
      : field.options || []

    return (
      <div className={span}>
        <label className="text-sm text-muted mb-1 block">
          {field.label}{field.required ? ' *' : ''}
        </label>
        <Select
          value={String(val ?? '')}
          onChange={(e) => onChange(field.key, e.target.value)}
        >
          <SelectOption value="">انتخاب کنید</SelectOption>
          {options.map((o) => (
            <SelectOption key={o.value} value={o.value}>{o.label}</SelectOption>
          ))}
        </Select>
      </div>
    )
  }

  if (field.type === 'multiselect') {
    const selected = Array.isArray(val) ? val : []
    return (
      <div className={`${span} space-y-2`}>
        <label className="text-sm text-muted block">{field.label}</label>
        <div className="flex flex-wrap gap-2">
          {(field.options || []).map((o) => (
            <label key={o.value} className="flex items-center gap-2 text-sm cursor-pointer">
              <input
                type="checkbox"
                checked={selected.includes(o.value)}
                onChange={() => {
                  const next = selected.includes(o.value)
                    ? selected.filter((x) => x !== o.value)
                    : [...selected, o.value]
                  onChange(field.key, next)
                }}
                className="rounded border-card-border accent-primary"
              />
              {o.label}
            </label>
          ))}
        </div>
      </div>
    )
  }

  if (field.type === 'textarea') {
    return (
      <div className={span}>
        <label className="text-sm text-muted mb-1 block">{field.label}</label>
        <textarea
          value={String(val ?? '')}
          onChange={(e) => onChange(field.key, e.target.value)}
          rows={4}
          className="flex w-full rounded-xl border border-card-border bg-background px-4 py-3 text-sm resize-none focus:border-primary/50 focus:outline-none focus:ring-2 focus:ring-primary/20"
        />
      </div>
    )
  }

  const inputType = ['currency', 'number'].includes(field.type) ? 'number' : field.type === 'phone' ? 'tel' : 'text'
  const isOwnerField = field.section === 'owner'

  return (
    <div className={span}>
      <label className="text-sm text-muted mb-1 block">
        {field.label}{field.required ? ' *' : ''}
        {field.unit ? <span className="text-xs"> ({field.unit})</span> : null}
      </label>
      <Input
        type={inputType}
        value={String(val ?? '')}
        onChange={(e) => onChange(field.key, e.target.value)}
        dir={inputType === 'number' || inputType === 'tel' ? 'ltr' : undefined}
        placeholder={field.hint}
      />
      {isOwnerField && (
        <p className="text-[11px] text-muted mt-1">فقط برای کارکنان دفتر — در وبسایت نمایش داده نمی‌شود</p>
      )}
    </div>
  )
}
