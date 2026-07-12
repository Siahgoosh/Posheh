export const TRANSACTION_TYPES = [
  { value: 'sale', label: 'فروش' },
  { value: 'rent', label: 'اجاره' },
  { value: 'mortgage', label: 'رهن' },
  { value: 'pre_sale', label: 'پیش‌فروش' },
  { value: 'land', label: 'زمین' },
  { value: 'garden', label: 'باغ' },
  { value: 'commercial', label: 'تجاری' },
  { value: 'warehouse', label: 'انبار' },
  { value: 'partnership', label: 'مشارکت' },
] as const

export const PROPERTY_CATEGORIES = [
  { value: 'apartment', label: 'آپارتمان' },
  { value: 'villa', label: 'ویلا' },
  { value: 'land', label: 'زمین / کلنگی' },
  { value: 'shop', label: 'مغازه' },
  { value: 'office', label: 'دفتر اداری' },
  { value: 'warehouse', label: 'انبار' },
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

export const EXTRA_FEATURES = [
  { value: 'balcony', label: 'بالکن' },
  { value: 'renovated', label: 'بازسازی‌شده' },
  { value: 'furnished', label: 'مبله' },
  { value: 'yard', label: 'حیاط' },
  { value: 'pool', label: 'استخر' },
  { value: 'security', label: 'نگهبانی' },
  { value: 'gas', label: 'گاز رومیزی' },
  { value: 'cooler', label: 'کولر' },
] as const

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
  return ['sale', 'pre_sale', 'land', 'commercial', 'warehouse', 'partnership'].includes(type)
}

export function showsRentFields(type: string) {
  return ['rent', 'mortgage'].includes(type)
}
