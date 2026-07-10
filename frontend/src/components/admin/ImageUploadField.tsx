import { useRef, useState } from 'react'
import { ImagePlus, Loader2 } from 'lucide-react'
import { Button } from '@/components/ui/button'

interface ImageUploadFieldProps {
  label: string
  value: string
  onChange: (url: string) => void
  onUpload: (file: File) => Promise<string>
  hint?: string
}

export function ImageUploadField({ label, value, onChange, onUpload, hint }: ImageUploadFieldProps) {
  const inputRef = useRef<HTMLInputElement>(null)
  const [loading, setLoading] = useState(false)

  const handleFile = async (file: File) => {
    setLoading(true)
    try {
      const url = await onUpload(file)
      onChange(url)
    } catch {
      window.alert('آپلود تصویر ناموفق بود')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="space-y-2">
      <label className="text-sm text-muted block">{label}</label>
      {value && (
        <img src={value} alt="" className="max-h-40 rounded-xl border border-card-border object-cover" />
      )}
      <div className="flex flex-wrap gap-2">
        <input
          ref={inputRef}
          type="file"
          accept="image/*"
          className="hidden"
          onChange={(e) => {
            const file = e.target.files?.[0]
            if (file) handleFile(file)
            e.target.value = ''
          }}
        />
        <Button type="button" variant="outline" size="sm" disabled={loading} onClick={() => inputRef.current?.click()}>
          {loading ? <Loader2 className="h-4 w-4 animate-spin" /> : <ImagePlus className="h-4 w-4" />}
          انتخاب از سیستم
        </Button>
        {value && (
          <Button type="button" variant="ghost" size="sm" onClick={() => onChange('')}>
            حذف
          </Button>
        )}
      </div>
      {hint && <p className="text-xs text-muted">{hint}</p>}
    </div>
  )
}
