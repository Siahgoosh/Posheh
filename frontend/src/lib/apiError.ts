import axios from 'axios'

export function extractApiError(err: unknown, fallback = 'خطای سرور'): string {
  if (!axios.isAxiosError(err)) {
    return err instanceof Error ? err.message : fallback
  }

  const status = err.response?.status
  const data = err.response?.data as {
    message?: string
    error?: string
    errors?: Record<string, string[]>
    code?: string
  } | undefined

  if (data?.code === 'schema_outdated') {
    return data.message || 'دیتابیس به‌روز نیست. لطفاً migrate / deploy را اجرا کنید.'
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

  if (data?.error) {
    return data.error
  }

  if (status === 502 || status === 503 || status === 504) {
    return 'API در دسترس نیست (خطای دروازه). سرویس app/nginx را روی سرور بررسی کنید.'
  }

  if (status === 500) {
    return 'خطای سرور. اگر تازه UI را آپدیت کرده‌اید، حتماً دوباره deploy کامل با migrate بزنید.'
  }

  if (status === 413) {
    return 'حجم فایل بیش از حد مجاز سرور است.'
  }

  if (!err.response) {
    return 'ارتباط با سرور برقرار نشد. اتصال یا وضعیت API را بررسی کنید.'
  }

  return fallback
}
