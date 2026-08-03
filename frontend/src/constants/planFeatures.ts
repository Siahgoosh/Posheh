/** کاتالوگ کامل امکانات پلن‌ها — برای نمایش به مشتری و مقایسه */

export interface PlanFeatureDef {
  key: string
  label: string
  description: string
  category: 'core' | 'crm' | 'team' | 'marketing' | 'integrations' | 'analytics' | 'premium'
  highlight?: boolean
}

export const PLAN_FEATURE_CATALOG: PlanFeatureDef[] = [
  { key: 'filing', label: 'فایلینگ حرفه‌ای', description: 'ثبت، دسته‌بندی و آرشیو فایل‌های ملک با کد یکتا', category: 'core' },
  { key: 'properties', label: 'مدیریت املاک', description: 'ویرایش کامل مشخصات، عکس، وضعیت فروش و انتشار', category: 'core' },
  { key: 'search', label: 'جستجوی پیشرفته', description: 'فیلتر بر اساس منطقه، قیمت، متراژ، نوع معامله و امکانات', category: 'core' },
  { key: 'favorites', label: 'علاقه‌مندی‌ها', description: 'ذخیره فایل‌های منتخب برای پیگیری سریع', category: 'core' },
  { key: 'saved_searches', label: 'جستجوهای ذخیره‌شده', description: 'ذخیره فیلترها و دریافت هشدار فایل جدید', category: 'core' },
  { key: 'excel_export', label: 'خروجی اکسل', description: 'خروجی گرفتن از فایل‌ها و گزارش‌ها', category: 'core' },
  { key: 'pdf_export', label: 'خروجی PDF', description: 'چاپ و ارسال فایل به‌صورت PDF حرفه‌ای', category: 'core' },
  { key: 'jalali_calendar', label: 'تقویم شمسی', description: 'برنامه‌ریزی بازدید و یادآوری با تقویم فارسی', category: 'core' },
  { key: 'visit_calendar', label: 'تقویم بازدید', description: 'زمان‌بندی بازدید ملک و هماهنگی با مشتری', category: 'crm' },
  { key: 'crm', label: 'CRM مشتری', description: 'مدیریت سرنخ، وضعیت فروش و پیگیری مشتریان', category: 'crm' },
  { key: 'lead_scoring', label: 'امتیازدهی سرنخ', description: 'اولویت‌بندی خودکار مشتریان بر اساس رفتار و بودجه', category: 'crm', highlight: true },
  { key: 'owner_portal', label: 'پورتال مالک', description: 'دسترسی مالک به وضعیت فایل و بازدیدها', category: 'crm' },
  { key: 'property_share', label: 'اشتراک چندکاناله', description: 'ارسال فایل در واتساپ، تلگرام، روبیکا و بله', category: 'marketing' },
  { key: 'ad_copy', label: 'کپی آگهی هوشمند', description: 'تولید متن آگهی آماده انتشار از روی فایل', category: 'marketing' },
  { key: 'quality_score', label: 'امتیاز کیفیت فایل', description: 'بررسی کامل بودن فایل و پیشنهاد بهبود', category: 'marketing' },
  { key: 'content_assistant', label: 'دستیار هوشمند تولید محتوا', description: 'ریلز، استوری، کپشن، تقویم محتوا و تحلیل بازار با AI', category: 'marketing', highlight: true },
  { key: 'virtual_tour', label: 'تور مجازی ۳۶۰', description: 'ساخت تور پانوراما و اشتراک لینک عمومی', category: 'marketing' },
  { key: 'team', label: 'مدیریت تیم', description: 'افزودن مشاور، نقش‌ها و دسترسی‌ها', category: 'team' },
  { key: 'team_chat', label: 'چت درون‌تیمی', description: 'گفتگوی لحظه‌ای بین مشاوران دفتر', category: 'team' },
  { key: 'commissions', label: 'کمیسیون خودکار', description: 'محاسبه و تسویه سهم مشاوران از معاملات', category: 'team' },
  { key: 'accounting', label: 'حسابداری دفتر', description: 'درآمد، هزینه، صندوق و گزارش مالی', category: 'team' },
  { key: 'activity_logs', label: 'گزارش فعالیت‌ها', description: 'ثبت عملیات کاربران برای شفافیت مدیریتی', category: 'team' },
  { key: 'telegram_bot', label: 'ربات تلگرام', description: 'دریافت سرنخ و ارسال فایل از طریق تلگرام', category: 'integrations' },
  { key: 'whatsapp_bot', label: 'ربات واتساپ', description: 'پاسخگویی و ارسال فایل در واتساپ', category: 'integrations', highlight: true },
  { key: 'website_listing', label: 'وبسایت اختصاصی دفتر', description: 'سایت name.posheapp.ir با تم‌های حرفه‌ای', category: 'integrations', highlight: true },
  { key: 'verified_badge', label: 'تیک تأیید پوشه', description: 'نمایش نشان معتبر بودن دفتر در سایت و پنل', category: 'premium' },
  { key: 'advanced_analytics', label: 'تحلیل پیشرفته', description: 'نمودار فروش، بازدید و عملکرد مشاوران', category: 'analytics' },
  { key: 'demand_heatmap', label: 'نقشه تقاضا', description: 'مناطق پرتقاضا بر اساس رفتار مشتریان', category: 'analytics' },
  { key: 'property_compare', label: 'مقایسه ملک', description: 'مقایسه چند فایل برای مشاوره بهتر', category: 'analytics' },
]

export const PLAN_CATEGORY_LABELS: Record<string, string> = {
  core: 'امکانات پایه',
  crm: 'فروش و CRM',
  marketing: 'بازاریابی و محتوا',
  team: 'تیم و مالی',
  integrations: 'اتصالات و وب',
  analytics: 'تحلیل و گزارش',
  premium: 'امکانات ویژه',
}

export const PLAN_HIGHLIGHTS: Record<string, string[]> = {
  solo: [
    'مناسب مشاور مستقل',
    'تا ۱۵۰ فایل ملک',
    '۴۸ ساعت آزمایشی رایگان',
    'CRM پایه و اشتراک فایل',
  ],
  office: [
    'تا ۵ مشاور همزمان',
    'حسابداری و کمیسیون',
    'ربات تلگرام و چت تیمی',
    'امتیازدهی سرنخ CRM',
  ],
  premium: [
    'دستیار هوشمند AI محتوا',
    'وبسایت اختصاصی + تیک تأیید',
    'ربات واتساپ و تحلیل پیشرفته',
    'تا ۱۰ مشاور و ۱۵۰۰ فایل',
  ],
}

export const PLAN_COMPARISON_FEATURE_KEYS = [
  'filing', 'properties', 'search', 'crm', 'property_share', 'ad_copy',
  'content_assistant', 'virtual_tour', 'lead_scoring', 'team', 'team_chat',
  'accounting', 'commissions', 'telegram_bot', 'whatsapp_bot',
  'website_listing', 'verified_badge', 'advanced_analytics', 'demand_heatmap',
]

export function featureDef(key: string): PlanFeatureDef | undefined {
  return PLAN_FEATURE_CATALOG.find((f) => f.key === key)
}

export function planHasFeature(planFeatures: string[] | undefined, key: string): boolean {
  return !!planFeatures?.includes(key)
}
