export interface FilingOption {
  value: string
  label: string
}

export interface FilingField {
  key: string
  label: string
  type: string
  section?: string
  required?: boolean
  hint?: string
  unit?: string
  options?: FilingOption[]
  storage?: string
  default?: string | boolean
}

export interface FilingSection {
  id: string
  label: string
  icon?: string
}

export interface FilingSchema {
  property_types: FilingOption[]
  transaction_types: FilingOption[]
  statuses: FilingOption[]
  permissions: FilingOption[]
  sections: FilingSection[]
  shared_fields: FilingField[]
  owner_fields: FilingField[]
  location_fields: FilingField[]
  common_property_fields: FilingField[]
  amenity_options: FilingOption[]
  document_status_options: FilingOption[]
  tag_options: FilingOption[]
  property_type_fields: Record<string, FilingField[]>
  transaction_type_fields: Record<string, FilingField[]>
}

export interface FilingFieldGroups {
  shared: FilingField[]
  owner: FilingField[]
  location: FilingField[]
  property: FilingField[]
  transaction: FilingField[]
  amenities: FilingField[]
  documents: FilingField[]
  notes: FilingField[]
}

export type FilingFormValues = Record<string, string | number | boolean | string[] | null | undefined>

const TOP_LEVEL_KEYS = new Set([
  'code', 'title', 'type', 'property_category', 'status', 'permission',
  'owner_name', 'owner_mobile', 'contact_phone_2',
  'price', 'deposit', 'rent', 'area', 'rooms', 'building_age', 'floor', 'total_floors',
  'has_parking', 'has_elevator', 'has_storage',
  'province', 'city', 'district', 'neighborhood', 'address', 'latitude', 'longitude',
  'description', 'features', 'tags', 'document_status',
  'expires_at', 'assigned_to', 'show_on_website',
])

const INT_KEYS = new Set(['price', 'deposit', 'rent', 'rooms', 'building_age', 'floor', 'total_floors', 'assigned_to'])
const FLOAT_KEYS = new Set(['area', 'latitude', 'longitude'])

function coerceValue(key: string, val: unknown): unknown {
  if (val === '' || val === undefined || val === null) return undefined
  if (INT_KEYS.has(key)) return parseInt(String(val), 10)
  if (FLOAT_KEYS.has(key)) return parseFloat(String(val))
  if (key === 'assigned_to') return parseInt(String(val), 10)
  return val
}

function storageBucket(storage?: string): 'owner' | 'location' | 'specs' | 'transaction' | null {
  if (!storage?.startsWith('filing_data.')) return null
  const part = storage.split('.')[1]
  if (part === 'owner' || part === 'location' || part === 'specs' || part === 'transaction') return part
  return null
}

export function buildFilingPayload(
  values: FilingFormValues,
  fields: FilingField[],
): Record<string, unknown> {
  const fieldByKey = new Map(fields.map((f) => [f.key, f]))
  const filingData: Record<string, Record<string, unknown>> = {
    owner: {},
    location: {},
    specs: {},
    transaction: {},
  }
  const topLevel: Record<string, unknown> = {}

  Object.entries(values).forEach(([key, raw]) => {
    if (key.startsWith('__')) return
    const field = fieldByKey.get(key)
    const val = coerceValue(key, raw)
    if (val === undefined) return

    const bucket = storageBucket(field?.storage)
    if (bucket) {
      filingData[bucket][key] = val
      return
    }
    if (TOP_LEVEL_KEYS.has(key) || !field?.storage) {
      topLevel[key] = val
    }
  })

  // Auto-compose internal owner name (never shown on public website)
  if (!topLevel.owner_name) {
    const first = filingData.owner.owner_first_name ?? values.owner_first_name
    const last = filingData.owner.owner_last_name ?? values.owner_last_name
    const name = [first, last].filter(Boolean).join(' ').trim()
    if (name) topLevel.owner_name = name
  }

  const payload: Record<string, unknown> = { ...topLevel }

  const mergedFiling: Record<string, unknown> = {}
  for (const [bucket, data] of Object.entries(filingData)) {
    if (Object.keys(data).length) mergedFiling[bucket] = data
  }
  if (Object.keys(mergedFiling).length) payload.filing_data = mergedFiling

  if (Array.isArray(payload.features) && !(payload.features as unknown[]).length) payload.features = null
  if (Array.isArray(payload.tags) && !(payload.tags as unknown[]).length) payload.tags = null

  return payload
}

export function flattenFieldGroups(groups: FilingFieldGroups): FilingField[] {
  return [
    ...groups.shared,
    ...groups.owner,
    ...groups.location,
    ...groups.property,
    ...groups.transaction,
    ...groups.amenities,
    ...groups.documents,
    ...groups.notes,
  ]
}

export function validateFilingForm(
  values: FilingFormValues,
  fields: FilingField[],
): string | null {
  for (const field of fields) {
    if (!field.required || field.key === 'show_on_website') continue
    const val = values[field.key]
    const empty = val === '' || val === undefined || val === null
      || (Array.isArray(val) && val.length === 0)
    if (empty) return `فیلد «${field.label}» الزامی است`
  }
  const mobile = String(values.owner_mobile ?? '').replace(/\D/g, '')
  if (mobile.length < 11) return 'شماره موبایل مالک باید ۱۱ رقم باشد'
  return null
}

/** Fields hidden from form UI */
export const HIDDEN_FILING_KEYS = new Set(['owner_name', 'show_on_website'])
