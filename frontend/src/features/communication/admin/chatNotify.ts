/** Admin inbox alerts: browser sound + optional desktop notification. */

const SOUND_PREF_KEY = 'posheh_comm_admin_sound'
const DESKTOP_PREF_KEY = 'posheh_comm_admin_desktop'

let audioCtx: AudioContext | null = null
let unlocked = false

function getCtx(): AudioContext | null {
  if (typeof window === 'undefined') return null
  const Ctx = window.AudioContext || (window as unknown as { webkitAudioContext?: typeof AudioContext }).webkitAudioContext
  if (!Ctx) return null
  if (!audioCtx) audioCtx = new Ctx()
  return audioCtx
}

/** Call from a click/tap so browsers allow later autoplay. */
export async function unlockAdminChatAudio(): Promise<boolean> {
  const ctx = getCtx()
  if (!ctx) return false
  try {
    if (ctx.state === 'suspended') await ctx.resume()
    unlocked = ctx.state === 'running'
    return unlocked
  } catch {
    return false
  }
}

export function isAdminChatSoundEnabled(): boolean {
  if (typeof localStorage === 'undefined') return true
  const v = localStorage.getItem(SOUND_PREF_KEY)
  return v !== '0'
}

export function setAdminChatSoundEnabled(on: boolean): void {
  localStorage.setItem(SOUND_PREF_KEY, on ? '1' : '0')
  if (on) void unlockAdminChatAudio()
}

export function isAdminChatDesktopEnabled(): boolean {
  if (typeof localStorage === 'undefined') return true
  const v = localStorage.getItem(DESKTOP_PREF_KEY)
  return v !== '0'
}

export function setAdminChatDesktopEnabled(on: boolean): void {
  localStorage.setItem(DESKTOP_PREF_KEY, on ? '1' : '0')
}

export async function requestDesktopNotifyPermission(): Promise<NotificationPermission | 'unsupported'> {
  if (typeof window === 'undefined' || !('Notification' in window)) return 'unsupported'
  if (Notification.permission === 'granted' || Notification.permission === 'denied') {
    return Notification.permission
  }
  try {
    return await Notification.requestPermission()
  } catch {
    return Notification.permission
  }
}

function tone(ctx: AudioContext, freq: number, start: number, dur: number, gain = 0.08): void {
  const osc = ctx.createOscillator()
  const g = ctx.createGain()
  osc.type = 'sine'
  osc.frequency.value = freq
  g.gain.setValueAtTime(0.0001, start)
  g.gain.exponentialRampToValueAtTime(gain, start + 0.02)
  g.gain.exponentialRampToValueAtTime(0.0001, start + dur)
  osc.connect(g)
  g.connect(ctx.destination)
  osc.start(start)
  osc.stop(start + dur + 0.02)
}

/** Soft two-note chime — no external audio file required. */
export async function playAdminChatSound(): Promise<void> {
  if (!isAdminChatSoundEnabled()) return
  const ctx = getCtx()
  if (!ctx) return
  try {
    if (ctx.state === 'suspended') await ctx.resume()
    const t = ctx.currentTime
    tone(ctx, 880, t, 0.12, 0.09)
    tone(ctx, 1174.7, t + 0.14, 0.18, 0.07)
    unlocked = true
  } catch {
    // Autoplay blocked until user gesture — ignore
  }
}

export function showAdminChatDesktopNotification(title: string, body: string, tag?: string): void {
  if (!isAdminChatDesktopEnabled()) return
  if (typeof window === 'undefined' || !('Notification' in window)) return
  if (Notification.permission !== 'granted') return
  // Avoid stacking when tab is focused and user already sees the inbox
  if (document.visibilityState === 'visible' && document.hasFocus()) return
  try {
    const n = new Notification(title, {
      body,
      tag: tag || 'posheh-comm-chat',
      lang: 'fa',
      dir: 'rtl',
      silent: false,
    })
    n.onclick = () => {
      window.focus()
      n.close()
    }
  } catch {
    // ignore
  }
}

export type InboxNotifySnapshot = {
  conversationCount: number
  unreadTotal: number
  /** uuid -> last_message signature */
  signatures: Record<string, string>
}

export function snapshotInbox(items: {
  uuid: string
  unread_operator: number
  last_message?: { body?: string; sender_type?: string } | null
  last_message_at?: string | null
}[]): InboxNotifySnapshot {
  const signatures: Record<string, string> = {}
  let unreadTotal = 0
  for (const c of items) {
    unreadTotal += c.unread_operator || 0
    signatures[c.uuid] = [
      c.last_message_at || '',
      c.last_message?.sender_type || '',
      (c.last_message?.body || '').slice(0, 80),
      String(c.unread_operator || 0),
    ].join('|')
  }
  return {
    conversationCount: items.length,
    unreadTotal,
    signatures,
  }
}

/** Returns true if operator should be alerted (new visitor activity). */
export function shouldAlertFromInbox(
  prev: InboxNotifySnapshot | null,
  next: InboxNotifySnapshot,
  items: {
    uuid: string
    unread_operator: number
    last_message?: { body?: string; sender_type?: string } | null
    visitor?: { name?: string } | null
    lead?: { office_name?: string } | null
    subject?: string | null
  }[],
): { alert: boolean; title: string; body: string; tag: string } | null {
  if (!prev) return null

  const newVisitorMsgs = items.filter((c) => {
    const before = prev.signatures[c.uuid]
    const after = next.signatures[c.uuid]
    if (!before) {
      // brand-new conversation
      return c.last_message?.sender_type === 'visitor' || (c.unread_operator || 0) > 0
    }
    if (before === after) return false
    return c.last_message?.sender_type === 'visitor' || (c.unread_operator || 0) > (Number(before.split('|')[3]) || 0)
  })

  if (newVisitorMsgs.length === 0 && next.unreadTotal <= prev.unreadTotal) {
    return null
  }

  const focus = newVisitorMsgs[0] || items.find((c) => (c.unread_operator || 0) > 0)
  const who = focus?.visitor?.name || focus?.lead?.office_name || focus?.subject || 'بازدیدکننده'
  const preview = focus?.last_message?.body?.slice(0, 120) || 'پیام جدید در چت آنلاین'

  return {
    alert: true,
    title: 'پیام جدید — مرکز ارتباطات',
    body: `${who}: ${preview}`,
    tag: focus?.uuid ? `posheh-comm-${focus.uuid}` : 'posheh-comm-chat',
  }
}

let lastAlertAt = 0
let lastAlertTag = ''

export async function notifyAdminNewChatMessage(opts: {
  title: string
  body: string
  tag?: string
}): Promise<void> {
  const tag = opts.tag || 'posheh-comm-chat'
  const now = Date.now()
  // Dedupe inbox + open-thread polls for the same alert.
  if (tag === lastAlertTag && now - lastAlertAt < 2500) return
  lastAlertTag = tag
  lastAlertAt = now

  await playAdminChatSound()
  showAdminChatDesktopNotification(opts.title, opts.body, tag)
}

export function isAudioUnlocked(): boolean {
  return unlocked && !!audioCtx && audioCtx.state === 'running'
}
