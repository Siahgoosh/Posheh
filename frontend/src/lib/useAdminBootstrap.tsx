import { useState } from 'react'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import api from '@/lib/api'

type BootstrapMsg = { type: 'ok' | 'err'; text: string }

function extractError(err: unknown): string {
  const axiosErr = err as {
    message?: string
    response?: { data?: { message?: string; data?: { message?: string; message_fa?: string }; errors?: Record<string, string[]> } }
  }
  return (
    axiosErr.response?.data?.data?.message_fa
    || axiosErr.response?.data?.data?.message
    || axiosErr.response?.data?.message
    || axiosErr.message
    || 'راه‌اندازی ناموفق بود'
  )
}

function extractSuccess(data: unknown): string {
  const d = data as { message_fa?: string; message?: string; note?: string; ok?: boolean } | null
  return d?.message_fa || d?.message || d?.note || 'راه‌اندازی با موفقیت انجام شد.'
}

/** Shared bootstrap mutation with visible Persian success/error feedback. */
export function useAdminBootstrap(path: string, invalidateKeys: string[] = []) {
  const qc = useQueryClient()
  const [msg, setMsg] = useState<BootstrapMsg | null>(null)

  const mutation = useMutation({
    mutationFn: async () => {
      const res = await api.post(path)
      const payload = (res.data?.data ?? res.data) as { ok?: boolean; message?: string; message_fa?: string }
      if (payload && payload.ok === false) {
        throw new Error(payload.message_fa || payload.message || 'Migration یا پیش‌نیاز ناقص است')
      }
      return payload
    },
    onSuccess: (data) => {
      for (const key of invalidateKeys) {
        void qc.invalidateQueries({ queryKey: [key] })
      }
      setMsg({ type: 'ok', text: extractSuccess(data) })
    },
    onError: (err) => {
      setMsg({ type: 'err', text: extractError(err) })
    },
  })

  return {
    run: () => mutation.mutate(),
    isPending: mutation.isPending,
    msg,
    clearMsg: () => setMsg(null),
  }
}

export function BootstrapStatusBanner({ msg }: { msg: BootstrapMsg | null }) {
  if (!msg) return null
  return (
    <div
      className={`rounded-xl border px-3 py-2 text-sm ${
        msg.type === 'ok'
          ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-200'
          : 'border-red-500/40 bg-red-500/10 text-red-200'
      }`}
      role="status"
    >
      {msg.text}
    </div>
  )
}
