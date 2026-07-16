export const TRANSACTION_TYPES = [
  { value: 'sale', label: 'فروش' },
  { value: 'full_mortgage', label: 'رهن کامل' },
  { value: 'rent', label: 'اجاره' },
  { value: 'mortgage_rent', label: 'رهن و اجاره' },
  { value: 'pre_sale', label: 'پیش‌فروش' },
  { value: 'construction_partnership', label: 'مشارکت در ساخت' },
  { value: 'exchange', label: 'معاوضه' },
  { value: 'barter', label: 'تهاتر' },
  { value: 'installment_sale', label: 'فروش اقساطی' },
  { value: 'auction', label: 'مزایده' },
] as const

export const PROPERTY_CATEGORIES = [
  { value: 'land', label: 'زمین' },
  { value: 'apartment', label: 'آپارتمان' },
  { value: 'villa', label: 'ویلا' },
  { value: 'old_house', label: 'خانه کلنگی' },
  { value: 'shop', label: 'مغازه' },
  { value: 'office', label: 'دفتر اداری' },
  { value: 'commercial_unit', label: 'واحد تجاری' },
  { value: 'warehouse', label: 'سوله' },
  { value: 'factory', label: 'کارخانه' },
  { value: 'garden', label: 'باغ' },
  { value: 'garden_villa', label: 'باغ ویلا' },
  { value: 'agricultural_land', label: 'زمین کشاورزی' },
  { value: 'greenhouse', label: 'گلخانه' },
  { value: 'livestock', label: 'دامداری' },
  { value: 'storage', label: 'انبار' },
  { value: 'construction_project', label: 'پروژه مشارکتی' },
  { value: 'pre_sale_unit', label: 'پیش‌فروش' },
  { value: 'residential_complex', label: 'مجتمع مسکونی' },
  { value: 'commercial_complex', label: 'مجتمع تجاری' },
  { value: 'hotel', label: 'هتل' },
  { value: 'parking', label: 'پارکینگ' },
  { value: 'suite', label: 'سوئیت' },
  { value: 'townhouse', label: 'تاون‌هاوس' },
  { value: 'other', label: 'سایر' },
] as const

export const PERMISSION_LEVELS = [
  { value: 'office', label: 'دفتری — همه مشاوران' },
  { value: 'team', label: 'تیمی — اعضای تیم' },
  { value: 'private', label: 'خصوصی — فقط من' },
  { value: 'manager_only', label: 'فقط مدیر' },
] as const

export const FILE_STATUSES = [
  { value: 'active', label: 'فعال' },
  { value: 'reserved', label: 'رزرو' },
  { value: 'sold', label: 'فروخته شده' },
  { value: 'rented', label: 'اجاره رفته' },
  { value: 'archived', label: 'آرشیو' },
  { value: 'cancelled', label: 'باطل' },
] as const

export const EXTRA_FEATURES = [
  'آب', 'برق', 'گاز', 'تلفن', 'فیبر نوری', 'اینترنت',
  'سرمایش', 'گرمایش', 'کابینت', 'کفپوش', 'نما',
  'استخر', 'سونا', 'جکوزی', 'لابی', 'نگهبانی', 'دوربین', 'سرایدار',
  'بالکن', 'مبله', 'انباری', 'پارکینگ',
].map((label) => ({ value: label, label }))

export const IRAN_PROVINCES = [
  'تهران', 'البرز', 'اصفهان', 'فارس', 'خراسان رضوی', 'آذربایجان شرقی', 'آذربایجان غربی',
  'خوزستان', 'مازندران', 'گیلان', 'کرمان', 'هرمزگان', 'یزد', 'قم', 'مرکزی', 'قزوین',
  'گلستان', 'اردبیل', 'همدان', 'کردستان', 'کرمانشاه', 'لرستان', 'سیستان و بلوچستان',
  'بوشهر', 'زنجان', 'سمنان', 'چهارمحال و بختیاری', 'کهگیلویه و بویراحمد', 'ایلام',
  'خراسان شمالی', 'خراسان جنوبی',
] as const

export function categoryLabel(value?: string | null): string {
  return PROPERTY_CATEGORIES.find((c) => c.value === value)?.label ?? value ?? '—'
}

export function showsSalePrice(type: string) {
  return ['sale', 'pre_sale', 'construction_partnership', 'exchange', 'barter', 'installment_sale', 'auction'].includes(type)
}

export function showsRentFields(type: string) {
  return ['rent', 'full_mortgage', 'mortgage_rent', 'mortgage'].includes(type)
}

export function showsMortgageOnly(type: string) {
  return ['full_mortgage', 'mortgage_rent', 'mortgage'].includes(type)
}
