import { Input } from '@/components/ui/input'
import { Select, SelectOption } from '@/components/ui/select'
import { IRAN_PROVINCES } from '@/constants/property'
import type { FilingField, FilingFormValues } from '@/lib/filing'

interface Props {
  fields: FilingField[]
  values: FilingFormValues
  onChange: (key: string, value: string | boolean | string[]) => void
}

export function DynamicFilingFields({ fields, values, onChange }: Props) {
  if (!fields.length) return null

  return (
    <div className="grid sm:grid-cols-2 gap-4">
      {fields.map((field) => (
        <FieldRenderer key={field.key} field={field} values={values} onChange={onChange} />
      ))}
    </div>
  )
}

function FieldRenderer({
  field,
  values,
  onChange,
}: {
  field: FilingField
  values: FilingFormValues
  onChange: (key: string, value: string | boolean | string[]) => void
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
          required={field.required}
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
        required={field.required}
        dir={inputType === 'number' || inputType === 'tel' ? 'ltr' : undefined}
        placeholder={field.hint}
      />
    </div>
  )
}
