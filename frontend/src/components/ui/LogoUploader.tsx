import { useRef, useState } from 'react'
import { Building2, Upload, X } from 'lucide-react'
import { cn } from '@/lib/utils'

interface Props {
  onChange: (file: File | null) => void
  className?: string
}

export function LogoUploader({ onChange, className }: Props) {
  const inputRef = useRef<HTMLInputElement>(null)
  const [preview, setPreview] = useState<string | null>(null)
  const [dragOver, setDragOver] = useState(false)

  const handleFile = (file: File | null) => {
    if (preview) URL.revokeObjectURL(preview)
    if (!file) {
      setPreview(null)
      onChange(null)
      return
    }
    setPreview(URL.createObjectURL(file))
    onChange(file)
  }

  return (
    <div className={cn('space-y-2', className)}>
      <label className="text-sm text-muted block">لوگو دفتر (اختیاری)</label>
      <div
        role="button"
        tabIndex={0}
        onClick={() => inputRef.current?.click()}
        onKeyDown={(e) => e.key === 'Enter' && inputRef.current?.click()}
        onDragOver={(e) => { e.preventDefault(); setDragOver(true) }}
        onDragLeave={() => setDragOver(false)}
        onDrop={(e) => {
          e.preventDefault()
          setDragOver(false)
          const file = e.dataTransfer.files?.[0]
          if (file?.type.startsWith('image/')) handleFile(file)
        }}
        className={cn(
          'relative flex flex-col items-center justify-center gap-3 rounded-2xl border-2 border-dashed p-6 cursor-pointer transition-all',
          dragOver ? 'border-primary bg-primary/10' : 'border-card-border hover:border-primary/40 hover:bg-primary/5',
        )}
      >
        {preview ? (
          <>
            <img src={preview} alt="پیش‌نمایش لوگو" className="h-20 w-20 rounded-xl object-cover shadow-lg" />
            <p className="text-xs text-muted">برای تغییر کلیک کنید یا فایل بکشید</p>
            <button
              type="button"
              onClick={(e) => { e.stopPropagation(); handleFile(null); if (inputRef.current) inputRef.current.value = '' }}
              className="absolute top-2 left-2 rounded-full bg-danger/20 p-1 text-danger hover:bg-danger/30"
            >
              <X className="h-4 w-4" />
            </button>
          </>
        ) : (
          <>
            <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-primary/20 to-accent/20">
              <Building2 className="h-7 w-7 text-primary" />
            </div>
            <div className="text-center">
              <p className="text-sm font-medium flex items-center justify-center gap-1">
                <Upload className="h-4 w-4" /> آپلود لوگو
              </p>
              <p className="text-xs text-muted mt-1">PNG یا JPG — حداکثر ۵ مگابایت</p>
            </div>
          </>
        )}
        <input
          ref={inputRef}
          type="file"
          accept="image/png,image/jpeg,image/webp"
          className="hidden"
          onChange={(e) => handleFile(e.target.files?.[0] ?? null)}
        />
      </div>
    </div>
  )
}
