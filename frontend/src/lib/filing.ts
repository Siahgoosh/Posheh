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

export function buildFilingPayload(values: FilingFormValues): Record<string, unknown> {
  const filingData: Record<string, Record<string, unknown>> = {
    owner: {},
    location: {},
    specs: {},
    transaction: {},
  }

  const topLevel: Record<string, unknown> = {}

  const assign = (key: string, val: unknown, storage?: string) => {
    if (val === '' || val === undefined || val === null) return
    if (storage?.startsWith('filing_data.')) {
      const part = storage.split('.')[1] as keyof typeof filingData
      filingData[part][key] = val
      return
    }
    topLevel[key] = val
  }

  Object.entries(values).forEach(([key, val]) => {
    if (key.startsWith('__')) return
    assign(key, val)
  })

  const payload: Record<string, unknown> = { ...topLevel }

  if (Object.keys(filingData.owner).length) payload.filing_data = { ...(payload.filing_data as object), owner: filingData.owner }
  if (Object.keys(filingData.location).length) {
    payload.filing_data = { ...(payload.filing_data as object || {}), location: filingData.location }
  }
  if (Object.keys(filingData.specs).length) {
    payload.filing_data = { ...(payload.filing_data as object || {}), specs: filingData.specs }
  }
  if (Object.keys(filingData.transaction).length) {
    payload.filing_data = { ...(payload.filing_data as object || {}), transaction: filingData.transaction }
  }

  ;['price', 'deposit', 'rent', 'rooms', 'building_age', 'floor', 'total_floors'].forEach((k) => {
    if (payload[k] != null && payload[k] !== '') {
      payload[k] = ['price', 'deposit', 'rent'].includes(k) ? parseInt(String(payload[k])) : parseFloat(String(payload[k]))
    }
  })

  if (payload.area != null && payload.area !== '') payload.area = parseFloat(String(payload.area))

  return payload
}
