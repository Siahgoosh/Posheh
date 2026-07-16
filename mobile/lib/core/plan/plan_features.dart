const planFeatureLabels = <String, String>{
  'filing': 'فایلینگ حرفه‌ای',
  'properties': 'مدیریت املاک',
  'search': 'جستجوی پیشرفته',
  'favorites': 'علاقه‌مندی‌ها',
  'accounting': 'حسابداری دفتر',
  'team': 'مدیریت تیم',
  'telegram_bot': 'ربات تلگرام',
  'whatsapp_bot': 'ربات واتساپ',
  'website_listing': 'وبسایت دفتر',
  'verified_badge': 'تیک تأیید',
  'crm': 'مدیریت مشتری',
  'lead_scoring': 'امتیازدهی سرنخ',
  'property_share': 'اشتراک واتساپ/تلگرام',
  'ad_copy': 'کپی آگهی',
  'quality_score': 'امتیاز کیفیت فایل',
  'commissions': 'کمیسیون خودکار',
  'visit_calendar': 'تقویم بازدید',
  'owner_portal': 'پورتال مالک',
  'demand_heatmap': 'نقشه تقاضا',
  'property_compare': 'مقایسه ملک',
  'advanced_analytics': 'تحلیل پیشرفته',
  'excel_export': 'خروجی اکسل',
  'pdf_export': 'خروجی پی‌دی‌اف',
  'jalali_calendar': 'تقویم شمسی',
  'saved_searches': 'جستجوهای ذخیره‌شده',
  'activity_logs': 'گزارش فعالیت‌ها',
};

String featureLabel(String key) => planFeatureLabels[key] ?? key;

bool hasFeature(List<String> features, String key) => features.contains(key);
