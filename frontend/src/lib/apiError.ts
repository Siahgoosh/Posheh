import axios from 'axios'

export function extractApiError(err: unknown, fallback = 'خطای سرور'): string {
  if (!axios.isAxiosError(err)) {
    return err instanceof Error ? err.message : fallback
  }

  const data = err.response?.data as {
    message?: string
    errors?: Record<string, string[]>
    code?: string
  } | undefined

  if (data?.code === 'schema_outdated') {
    return data.message || 'دیتابیس به‌روز نیست. لطفاً migrate را اجرا کنید.'
  }

  if (data?.errors) {
    const first = Object.values(data.errors).flat()[0]
    if (first) {
      if (first.includes('image field is required')) return 'فایل تصویر ارسال نشد. دوباره تلاش کنید.'
      if (first.includes('panorama field is required')) return 'فایل پانوراما ارسال نشد. دوباره تلاش کنید.'
      return first
    }
  }

  if (data?.message && data.message !== 'Server Error') {
    return data.message
  }

  if (err.response?.status === 500) {
    return 'خطای سرور هنگام آپلود. اگر تازه deploy کرده‌اید، migrate را اجرا کنید.'
  }

  if (err.response?.status === 413) {
    return 'حجم فایل بیش از حد مجاز سرور است.'
  }

  return fallback
}
