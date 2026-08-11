const MAX_DIMENSION = 8192
const JPEG_QUALITY = 0.85

export async function validatePanorama(file: File): Promise<{ valid: boolean; errors: string[] }> {
  const errors: string[] = []
  const allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp']

  if (!allowed.includes(file.type)) {
    errors.push('فرمت فایل باید JPEG، PNG یا WebP باشد.')
  }

  if (file.size > 100 * 1024 * 1024) {
    errors.push('حداکثر حجم فایل ۱۰۰ مگابایت است.')
  }

  try {
    const dims = await getImageDimensions(file)
    const ratio = dims.width / dims.height
    if (ratio < 1.8 || ratio > 2.2) {
      errors.push('تصویر باید equirectangular با نسبت ۲:۱ باشد.')
    }
    if (dims.width > MAX_DIMENSION || dims.height > MAX_DIMENSION / 2) {
      errors.push(`حداکثر ابعاد ${MAX_DIMENSION}×${MAX_DIMENSION / 2} پیکسل است.`)
    }
  } catch {
    errors.push('خواندن تصویر ناموفق بود.')
  }

  return { valid: errors.length === 0, errors }
}

export async function compressPanoramaIfNeeded(file: File): Promise<File> {
  if (file.size < 8 * 1024 * 1024 || !file.type.startsWith('image/')) {
    return file
  }

  const dims = await getImageDimensions(file)
  const needsResize = dims.width > MAX_DIMENSION
  const needsCompress = file.size > 12 * 1024 * 1024

  if (!needsResize && !needsCompress) return file

  const canvas = document.createElement('canvas')
  const scale = needsResize ? MAX_DIMENSION / dims.width : 1
  canvas.width = Math.round(dims.width * scale)
  canvas.height = Math.round(dims.height * scale)

  const ctx = canvas.getContext('2d')
  if (!ctx) return file

  const img = await loadImage(file)
  ctx.drawImage(img, 0, 0, canvas.width, canvas.height)

  const blob = await new Promise<Blob | null>((resolve) =>
    canvas.toBlob(resolve, 'image/jpeg', JPEG_QUALITY),
  )

  if (!blob) return file

  const baseName = file.name.replace(/\.[^.]+$/, '')
  return new File([blob], `${baseName}.jpg`, { type: 'image/jpeg' })
}

function getImageDimensions(file: File): Promise<{ width: number; height: number }> {
  return new Promise((resolve, reject) => {
    const url = URL.createObjectURL(file)
    const img = new Image()
    img.onload = () => {
      URL.revokeObjectURL(url)
      resolve({ width: img.naturalWidth, height: img.naturalHeight })
    }
    img.onerror = () => {
      URL.revokeObjectURL(url)
      reject(new Error('load failed'))
    }
    img.src = url
  })
}

function loadImage(file: File): Promise<HTMLImageElement> {
  return new Promise((resolve, reject) => {
    const url = URL.createObjectURL(file)
    const img = new Image()
    img.onload = () => {
      URL.revokeObjectURL(url)
      resolve(img)
    }
    img.onerror = () => {
      URL.revokeObjectURL(url)
      reject(new Error('load failed'))
    }
    img.src = url
  })
}

export function createPreviewUrl(file: File): string {
  return URL.createObjectURL(file)
}

export function revokePreviewUrl(url: string): void {
  URL.revokeObjectURL(url)
}
