/** کلمات کلیدی اصلی SEO — در متا و محتوا استفاده شود */
export const PRIMARY_SEO_KEYWORDS = [
  'فایلینگ املاک',
  'حسابداری املاک',
  'CRM املاک',
  'فروش ملک',
  'اجاره ملک',
] as const

export const PRIMARY_KEYWORDS_STRING = PRIMARY_SEO_KEYWORDS.join(', ')

export const SITE_SEO = {
  defaultTitle: 'پوشه — نرم‌افزار فایلینگ، CRM و حسابداری املاک',
  defaultDescription:
    'پوشه: سامانه ابری فایلینگ املاک، CRM املاک، حسابداری املاک، فروش ملک و اجاره ملک برای مشاوران و آژانس‌های ایران.',
  defaultKeywords: `نرم افزار املاک, ${PRIMARY_KEYWORDS_STRING}, پوشه, سامانه مشاور املاک`,
  blogListTitle: 'وبلاگ فایلینگ، CRM و حسابداری املاک',
  blogListDescription:
    'مقالات تخصصی فایلینگ املاک، CRM املاک، حسابداری املاک، فروش ملک و اجاره ملک برای مشاوران و مدیران دفاتر.',
} as const

/** متای SEO هر دسته وبلاگ */
export const BLOG_CATEGORY_SEO: Record<
  string,
  { title: string; description: string; keywords: string }
> = {
  software: {
    title: 'نرم‌افزار و سامانه املاک',
    description: 'راهنمای انتخاب نرم‌افزار املاک، فایلینگ املاک و CRM املاک برای دفاتر.',
    keywords: `نرم افزار املاک, فایلینگ املاک, CRM املاک, ${PRIMARY_KEYWORDS_STRING}`,
  },
  crm: {
    title: 'CRM املاک و فروش ملک',
    description: 'مدیریت سرنخ، قیف فروش ملک، پیگیری مشتری و CRM املاک حرفه‌ای.',
    keywords: `CRM املاک, فروش ملک, مدیریت مشتری املاک, ${PRIMARY_KEYWORDS_STRING}`,
  },
  filing: {
    title: 'فایلینگ و ثبت ملک',
    description: 'ثبت حرفه‌ای ملک، فایلینگ املاک، QR کد و استانداردسازی فایل‌ها.',
    keywords: `فایلینگ املاک, ثبت ملک, فروش ملک, اجاره ملک`,
  },
  agency: {
    title: 'مدیریت دفتر و آژانس املاک',
    description: 'مدیریت تیم، نقش‌ها و عملیات روزانه دفتر املاک.',
    keywords: `مدیریت دفتر املاک, CRM املاک, فایلینگ املاک`,
  },
  accounting: {
    title: 'حسابداری املاک و کمیسیون',
    description: 'حسابداری املاک، کمیسیون مشاور، درآمد و هزینه دفتر.',
    keywords: `حسابداری املاک, کمیسیون مشاور, حسابداری دفتر املاک`,
  },
  contracts: {
    title: 'قرارداد فروش و اجاره ملک',
    description: 'مبایعه‌نامه، اجاره‌نامه و قراردادهای فروش ملک و اجاره ملک.',
    keywords: `فروش ملک, اجاره ملک, قرارداد املاک, مبایعه نامه`,
  },
  marketing: {
    title: 'بازاریابی املاک',
    description: 'بازاریابی فایل، فروش ملک و جذب مشتری برای مشاوران.',
    keywords: `بازاریابی املاک, فروش ملک, فایلینگ املاک`,
  },
  education: {
    title: 'آموزش مشاور املاک',
    description: 'مهارت‌های فروش ملک، اجاره ملک و مذاکره برای مشاوران.',
    keywords: `آموزش مشاور املاک, فروش ملک, اجاره ملک`,
  },
  digital: {
    title: 'تحول دیجیتال املاک',
    description: 'دیجیتال‌سازی دفتر با CRM املاک و فایلینگ ابری.',
    keywords: `تحول دیجیتال املاک, CRM املاک, فایلینگ املاک`,
  },
  ai: {
    title: 'هوش مصنوعی در املاک',
    description: 'کاربرد AI در فایلینگ، تطبیق ملک و CRM املاک.',
    keywords: `هوش مصنوعی املاک, CRM املاک, فایلینگ املاک`,
  },
  mobile: {
    title: 'اپلیکیشن موبایل املاک',
    description: 'مدیریت فایلینگ و CRM املاک از موبایل.',
    keywords: `اپلیکیشن املاک, فایلینگ املاک, CRM املاک`,
  },
  website: {
    title: 'وبسایت اختصاصی دفتر املاک',
    description: 'سایت دفتر برای نمایش فروش ملک و اجاره ملک آنلاین.',
    keywords: `وبسایت املاک, فروش ملک, اجاره ملک`,
  },
  bots: {
    title: 'ربات تلگرام و واتساپ املاک',
    description: 'اتوماسیون پاسخگویی و ارسال فایل فروش و اجاره.',
    keywords: `ربات املاک, CRM املاک, فروش ملک`,
  },
  reports: {
    title: 'گزارش و KPI املاک',
    description: 'شاخص فروش ملک، عملکرد مشاور و گزارش مالی.',
    keywords: `گزارش املاک, حسابداری املاک, فروش ملک`,
  },
  security: {
    title: 'امنیت و OTP',
    description: 'امنیت داده‌های فایلینگ و حسابداری املاک.',
    keywords: `امنیت نرم افزار املاک, فایلینگ املاک`,
  },
}

export const SITEMAP_URL = 'https://posheapp.ir/sitemap.xml'
