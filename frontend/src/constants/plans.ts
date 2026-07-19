export interface PlanOption {
  id: number
  slug: string
  name: string
  description?: string
  monthly_price: number
  trial_days: number
  max_users: number
  max_properties: number
  features: string[]
  panel_type: string
}

export const FALLBACK_PLANS: PlanOption[] = [
  {
    id: 1,
    slug: 'solo',
    name: 'مشاور مستقل',
    description: 'پنل تک‌نفره — فایلینگ، CRM پایه و خروجی PDF/اکسل',
    monthly_price: 590_000,
    trial_days: 0,
    max_users: 1,
    max_properties: 150,
    panel_type: 'solo',
    features: ['filing', 'crm', 'property_share', 'ad_copy', 'quality_score'],
  },
  {
    id: 2,
    slug: 'office',
    name: 'دفتر املاک',
    description: 'تا ۵ مشاور — حسابداری، تیم، ربات تلگرام و گزارش KPI',
    monthly_price: 990_000,
    trial_days: 0,
    max_users: 5,
    max_properties: 600,
    panel_type: 'office',
    features: ['filing', 'crm', 'accounting', 'team', 'telegram_bot', 'lead_scoring', 'commissions'],
  },
  {
    id: 3,
    slug: 'premium',
    name: 'دفتر حرفه‌ای',
    description: 'سایت اختصاصی، واتساپ، تیک وریفای و CRM پیشرفته',
    monthly_price: 1_690_000,
    trial_days: 0,
    max_users: 10,
    max_properties: 1500,
    panel_type: 'premium',
    features: ['filing', 'crm', 'whatsapp_bot', 'website_listing', 'verified_badge', 'advanced_analytics', 'property_compare'],
  },
]

export const PLAN_FEATURE_LABELS: Record<string, string> = {
  filing: 'فایلینگ حرفه‌ای',
  properties: 'مدیریت املاک',
  search: 'جستجوی پیشرفته',
  favorites: 'علاقه‌مندی‌ها',
  accounting: 'حسابداری دفتر',
  team: 'مدیریت تیم',
  telegram_bot: 'ربات تلگرام',
  whatsapp_bot: 'ربات واتساپ',
  website_listing: 'نمایش در وبسایت',
  verified_badge: 'تیک تأیید',
  crm: 'مدیریت مشتری و فروش',
  lead_scoring: 'امتیازدهی سرنخ',
  property_share: 'اشتراک واتساپ/تلگرام',
  ad_copy: 'کپی آگهی هوشمند',
  quality_score: 'امتیاز کیفیت فایل',
  commissions: 'کمیسیون خودکار',
  visit_calendar: 'تقویم بازدید',
  owner_portal: 'پورتال مالک',
  demand_heatmap: 'نقشه تقاضا',
  property_compare: 'مقایسه ملک',
  advanced_analytics: 'تحلیل پیشرفته',
  excel_export: 'خروجی اکسل',
  pdf_export: 'خروجی پی‌دی‌اف',
  jalali_calendar: 'تقویم شمسی',
  saved_searches: 'جستجوهای ذخیره‌شده',
  activity_logs: 'گزارش فعالیت‌ها',
}

export const PANEL_TYPE_LABELS: Record<string, string> = {
  solo: 'مشاور مستقل',
  office: 'دفتر املاک',
  premium: 'دفتر حرفه‌ای',
}

export const SUBSCRIPTION_STATUS_LABELS: Record<string, string> = {
  active: 'فعال',
  expired: 'منقضی',
  cancelled: 'لغو شده',
  trial: 'دوره آزمایشی',
}

export const TASK_STATUS_LABELS: Record<string, string> = {
  pending: 'در انتظار',
  in_progress: 'در حال انجام',
  completed: 'انجام‌شده',
}

export function featureLabel(key: string): string {
  return PLAN_FEATURE_LABELS[key] || key
}

export function panelTypeLabel(key?: string): string {
  if (!key) return '—'
  return PANEL_TYPE_LABELS[key] || key
}

export function subscriptionStatusLabel(key?: string): string {
  if (!key) return '—'
  return SUBSCRIPTION_STATUS_LABELS[key] || key
}

export function trialBadgeForPlan(slug: string): string | null {
  if (slug === 'solo') return '۳ روز رایگان'
  return null
}

export function taskStatusLabel(key?: string): string {
  if (!key) return '—'
  return TASK_STATUS_LABELS[key] || key
}
