const DEVICE_ID_KEY = 'posheh_device_id'

function createDeviceId(): string {
  if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
    return crypto.randomUUID()
  }

  return `web-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 12)}`
}

export function getDeviceId(): string {
  let id = localStorage.getItem(DEVICE_ID_KEY)
  if (!id) {
    id = createDeviceId()
    localStorage.setItem(DEVICE_ID_KEY, id)
  }
  return id
}

export function getDeviceName(): string {
  const ua = navigator.userAgent
  if (/Windows/i.test(ua)) return 'Windows'
  if (/Android/i.test(ua)) return 'Android'
  if (/iPhone|iPad/i.test(ua)) return 'iOS'
  if (/Mac/i.test(ua)) return 'macOS'
  return 'Web Browser'
}

export function getPlatform(): string {
  return 'web'
}
