import { useRef, useState } from 'react'
import { ImagePlus, Star, Trash2, Loader2 } from 'lucide-react'
import api from '@/lib/api'
import { cn } from '@/lib/utils'

export interface PropertyMediaItem {
  id: number
  url: string
  is_cover: boolean
  original_name?: string
}

interface PropertyMediaUploaderProps {
  propertyId: number
  media: PropertyMediaItem[]
  onChange: (media: PropertyMediaItem[]) => void
  disabled?: boolean
}

export function PropertyMediaUploader({ propertyId, media, onChange, disabled }: PropertyMediaUploaderProps) {
  const inputRef = useRef<HTMLInputElement>(null)
  const [uploading, setUploading] = useState(false)
  const [error, setError] = useState('')

  const uploadFiles = async (files: FileList | null) => {
    if (!files?.length || disabled) return
    setError('')
    setUploading(true)
    const updated = [...media]

    try {
      for (const file of Array.from(files)) {
        const body = new FormData()
        body.append('image', file)
        body.append('is_cover', updated.length === 0 ? '1' : '0')
        const res = await api.post(`/properties/${propertyId}/media`, body, {
          headers: { 'Content-Type': 'multipart/form-data' },
        })
        updated.push({
          id: res.data.data.id,
          url: res.data.url || res.data.data.url,
          is_cover: res.data.data.is_cover,
          original_name: res.data.data.original_name,
        })
      }
      onChange(updated)
    } catch {
      setError('خطا در آپلود تصویر. حداکثر ۱۰ مگابایت و فرمت JPG/PNG.')
    } finally {
      setUploading(false)
      if (inputRef.current) inputRef.current.value = ''
    }
  }

  const removeMedia = async (item: PropertyMediaItem) => {
    if (disabled) return
    try {
      await api.delete(`/properties/${propertyId}/media/${item.id}`)
      onChange(media.filter((m) => m.id !== item.id))
    } catch {
      setError('حذف تصویر ناموفق بود.')
    }
  }

  const setCover = async (item: PropertyMediaItem) => {
    if (disabled || item.is_cover) return
    try {
      await api.post(`/properties/${propertyId}/media/${item.id}/cover`)
      onChange(media.map((m) => ({ ...m, is_cover: m.id === item.id })))
    } catch {
      setError('تنظیم کاور ناموفق بود.')
    }
  }

  return (
    <div className="space-y-4">
      <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
        {media.map((item) => (
          <div key={item.id} className="relative group aspect-[4/3] rounded-xl overflow-hidden border border-card-border bg-white/5">
            <img src={item.url} alt={item.original_name || 'تصویر ملک'} className="w-full h-full object-cover" />
            {item.is_cover && (
              <span className="absolute top-2 right-2 text-[10px] bg-primary text-white px-2 py-0.5 rounded-full">کاور</span>
            )}
            {!disabled && (
              <div className="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                {!item.is_cover && (
                  <button type="button" onClick={() => setCover(item)} className="p-2 rounded-lg bg-white/20 hover:bg-white/30" title="تنظیم به عنوان کاور">
                    <Star className="h-4 w-4" />
                  </button>
                )}
                <button type="button" onClick={() => removeMedia(item)} className="p-2 rounded-lg bg-danger/80 hover:bg-danger" title="حذف">
                  <Trash2 className="h-4 w-4" />
                </button>
              </div>
            )}
          </div>
        ))}

        {!disabled && (
          <button
            type="button"
            onClick={() => inputRef.current?.click()}
            disabled={uploading}
            className={cn(
              'aspect-[4/3] rounded-xl border-2 border-dashed border-card-border flex flex-col items-center justify-center gap-2 text-muted hover:border-primary/40 hover:text-primary transition-colors',
              uploading && 'opacity-60 cursor-wait',
            )}
          >
            {uploading ? <Loader2 className="h-8 w-8 animate-spin" /> : <ImagePlus className="h-8 w-8" />}
            <span className="text-xs">{uploading ? 'در حال آپلود...' : 'افزودن تصویر'}</span>
          </button>
        )}
      </div>

      <input
        ref={inputRef}
        type="file"
        accept="image/jpeg,image/png,image/webp"
        multiple
        className="hidden"
        onChange={(e) => uploadFiles(e.target.files)}
      />

      {!disabled && media.length === 0 && (
        <p className="text-xs text-muted">حداقل یک تصویر اضافه کنید. اولین تصویر به‌صورت خودکار کاور می‌شود.</p>
      )}

      {error && <p className="text-danger text-sm">{error}</p>}
    </div>
  )
}

interface PendingImagesProps {
  files: File[]
  onChange: (files: File[]) => void
}

export function PendingImagesPicker({ files, onChange }: PendingImagesProps) {
  const inputRef = useRef<HTMLInputElement>(null)

  const addFiles = (list: FileList | null) => {
    if (!list?.length) return
    onChange([...files, ...Array.from(list)])
  }

  const removeFile = (index: number) => {
    onChange(files.filter((_, i) => i !== index))
  }

  return (
    <div className="space-y-3">
      <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
        {files.map((file, index) => (
          <div key={`${file.name}-${index}`} className="relative group aspect-[4/3] rounded-xl overflow-hidden border border-card-border">
            <img src={URL.createObjectURL(file)} alt={file.name} className="w-full h-full object-cover" />
            {index === 0 && (
              <span className="absolute top-2 right-2 text-[10px] bg-primary text-white px-2 py-0.5 rounded-full">کاور</span>
            )}
            <button
              type="button"
              onClick={() => removeFile(index)}
              className="absolute top-2 left-2 p-1.5 rounded-lg bg-danger/80 opacity-0 group-hover:opacity-100 transition-opacity"
            >
              <Trash2 className="h-3.5 w-3.5" />
            </button>
          </div>
        ))}
        <button
          type="button"
          onClick={() => inputRef.current?.click()}
          className="aspect-[4/3] rounded-xl border-2 border-dashed border-card-border flex flex-col items-center justify-center gap-2 text-muted hover:border-primary/40 hover:text-primary transition-colors"
        >
          <ImagePlus className="h-8 w-8" />
          <span className="text-xs">انتخاب تصویر</span>
        </button>
      </div>
      <input ref={inputRef} type="file" accept="image/jpeg,image/png,image/webp" multiple className="hidden" onChange={(e) => addFiles(e.target.files)} />
      <p className="text-xs text-muted">تصاویر پس از ثبت ملک آپلود می‌شوند. JPG، PNG یا WebP — حداکثر ۱۰ مگابایت.</p>
    </div>
  )
}

export async function uploadPendingImages(propertyId: number, files: File[]) {
  for (let i = 0; i < files.length; i++) {
    const body = new FormData()
    body.append('image', files[i])
    body.append('is_cover', i === 0 ? '1' : '0')
    await api.post(`/properties/${propertyId}/media`, body, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  }
}
