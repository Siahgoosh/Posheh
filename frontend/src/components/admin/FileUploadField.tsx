import { useRef, useState } from 'react'
import { Upload, Loader2, FileIcon } from 'lucide-react'
import { Button } from '@/components/ui/button'

interface FileUploadFieldProps {
  label: string
  platform: string
  fileName?: string
  onUpload: (file: File) => Promise<{ download_url: string; file_size: string; file_path?: string }>
  onUploaded: (data: { download_url: string; file_size: string; file_path?: string }) => void
}

export function FileUploadField({ label, platform, fileName, onUpload, onUploaded }: FileUploadFieldProps) {
  const inputRef = useRef<HTMLInputElement>(null)
  const [loading, setLoading] = useState(false)

  const accept = platform === 'android'
    ? '.apk,.aab'
    : platform === 'windows'
      ? '.exe,.msi,.zip,.msix'
      : '.zip,.json,.webmanifest'

  const handleFile = async (file: File) => {
    setLoading(true)
    try {
      const data = await onUpload(file)
      onUploaded(data)
    } catch {
      window.alert('آپلود فایل ناموفق بود')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="space-y-2 rounded-xl border border-dashed border-card-border p-4">
      <label className="text-sm font-medium block">{label}</label>
      {fileName && (
        <p className="text-xs text-muted flex items-center gap-1">
          <FileIcon className="h-3 w-3" />
          {fileName}
        </p>
      )}
      <input
        ref={inputRef}
        type="file"
        accept={accept}
        className="hidden"
        onChange={(e) => {
          const file = e.target.files?.[0]
          if (file) handleFile(file)
          e.target.value = ''
        }}
      />
      <Button type="button" variant="outline" size="sm" disabled={loading} onClick={() => inputRef.current?.click()}>
        {loading ? <Loader2 className="h-4 w-4 animate-spin" /> : <Upload className="h-4 w-4" />}
        آپلود فایل از سیستم
      </Button>
      <p className="text-xs text-muted">فایل روی سرور ذخیره می‌شود و لینک دانلود خودکار تنظیم می‌شود.</p>
    </div>
  )
}
