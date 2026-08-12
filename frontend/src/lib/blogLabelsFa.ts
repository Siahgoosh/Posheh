/** برچسب‌های فارسی مشترک پنل وبلاگ / سئو */

export const REVIEW_STATUS_FA: Record<string, string> = {
  draft: 'پیش‌نویس',
  in_review: 'در حال بررسی',
  seo_review: 'بررسی سئو',
  content_review: 'بررسی محتوا',
  approved: 'تأییدشده',
  scheduled: 'زمان‌بندی‌شده',
  published: 'منتشرشده',
  unpublished: 'لغو انتشار',
  archived: 'آرشیو',
  rejected: 'نیاز به اصلاح',
  trash: 'سطل زباله',
}

export const SEARCH_INTENT_FA: Record<string, string> = {
  informational: 'اطلاعاتی',
  commercial: 'تجاری',
  transactional: 'تراکنشی',
  navigational: 'ناوبری',
  local: 'محلی',
  mixed: 'ترکیبی',
}

export const CONTENT_TYPE_FA: Record<string, string> = {
  guide: 'راهنما',
  news: 'خبر',
  analysis: 'تحلیل',
  list: 'فهرست',
  how_to: 'آموزش گام‌به‌گام',
  comparison: 'مقایسه',
  faq: 'پرسش‌وپاسخ',
  case_study: 'مطالعه موردی',
  local: 'محلی',
  product_led: 'محصول‌محور',
}

export const FUNNEL_STAGE_FA: Record<string, string> = {
  awareness: 'آگاهی',
  consideration: 'بررسی',
  decision: 'تصمیم',
  retention: 'نگهداری',
}

export const OPS_JOB_TYPE_FA: Record<string, string> = {
  research: 'تحقیق',
  brief: 'بریف',
  outline: 'ساختار',
  draft: 'پیش‌نویس',
  seo_audit: 'ممیزی سئو',
  fact_check: 'راستی‌آزمایی',
  internal_linking: 'لینک داخلی',
  image_suggestion: 'پیشنهاد تصویر',
  refresh: 'به‌روزرسانی',
  repurpose: 'بازنشر/بازتولید',
}

export const LOCATION_TYPE_FA: Record<string, string> = {
  country: 'کشور',
  province: 'استان',
  city: 'شهر',
  district: 'منطقه',
  neighborhood: 'محله',
  street: 'خیابان',
}

export const LEAD_STATUS_FA: Record<string, string> = {
  NEW: 'جدید',
  CONTACTED: 'تماس‌گرفته',
  QUALIFIED: 'واجد شرایط',
  WON: 'موفق',
  LOST: 'ازدست‌رفته',
}

export const LEAD_FEEDBACK_FA: Record<string, string> = {
  good: 'خوب',
  bad: 'ضعیف',
  wrong_intent: 'نیت اشتباه',
  converted: 'تبدیل‌شده',
}

export const DEVICE_FA: Record<string, string> = {
  desktop: 'دسکتاپ',
  tablet: 'تبلت',
  mobile: 'موبایل',
}

export const PORTFOLIO_FA: Record<string, string> = {
  healthy: 'سالم',
  needs_update: 'نیاز به به‌روزرسانی',
  critical: 'بحرانی',
  orphan_risk: 'ریسک یتیم',
}

export const UNKNOWN_FA = 'نامشخص'

export function labelFa(map: Record<string, string>, value?: string | null, fallback?: string): string {
  if (!value) return fallback || '—'
  return map[value] || fallback || value
}

export function unknownFa(v: unknown): string {
  if (v === null || v === undefined || v === '' || v === 'UNKNOWN') return UNKNOWN_FA
  return String(v)
}
