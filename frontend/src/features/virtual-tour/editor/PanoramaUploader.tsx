import { useCallback, useRef, useState } from 'react'
import { Upload, X, RotateCcw, CheckCircle, AlertCircle, Loader2 } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { tourApi } from '../api/tourApi'
import { useTourEditorStore } from '../store/editorStore'
import { compressPanoramaIfNeeded, createPreviewUrl, revokePreviewUrl, validatePanorama } from '../utils/imageCompression'
import { extractApiError } from '@/lib/apiError'
import type { UploadTask } from '../types'

interface Props {
  tourId: number | string
  onUploaded: () => void
}

export function PanoramaUploader({ tourId, onUploaded }: Props) {
  const inputRef = useRef<HTMLInputElement>(null)
  const [dragOver, setDragOver] = useState(false)
  const { uploadTasks, addUploadTask, updateUploadTask, removeUploadTask } = useTourEditorStore()

  const processFile = useCallback(async (file: File, taskId: string) => {
    const validation = await validatePanorama(file)
    if (!validation.valid) {
      updateUploadTask(taskId, { status: 'error', error: validation.errors.join(' ') })
      return
    }

    try {
      const compressed = await compressPanoramaIfNeeded(file)
      const controller = new AbortController()
      updateUploadTask(taskId, { status: 'uploading', abortController: controller })

      await tourApi.uploadPanorama(tourId, compressed, {
        name: file.name.replace(/\.[^.]+$/, '').slice(0, 200),
        onProgress: (pct) => updateUploadTask(taskId, { progress: pct }),
        signal: controller.signal,
      })

      updateUploadTask(taskId, { status: 'success', progress: 100 })
      onUploaded()
    } catch (err: unknown) {
      if (err && typeof err === 'object' && 'code' in err && (err as { code: string }).code === 'ERR_CANCELED') {
        updateUploadTask(taskId, { status: 'cancelled' })
        return
      }
      const message = extractApiError(err, 'آپلود ناموفق بود.')
      updateUploadTask(taskId, { status: 'error', error: message })
    }
  }, [tourId, onUploaded, updateUploadTask])

  const enqueueFiles = useCallback((files: FileList | File[]) => {
    Array.from(files).forEach((file) => {
      const id = `${Date.now()}-${Math.random().toString(36).slice(2)}`
      const previewUrl = createPreviewUrl(file)
      const task: UploadTask = {
        id,
        file,
        name: file.name,
        progress: 0,
        status: 'pending',
        previewUrl,
      }
      addUploadTask(task)
      processFile(file, id)
    })
  }, [addUploadTask, processFile])

  const retryTask = (task: UploadTask) => {
    updateUploadTask(task.id, { status: 'pending', progress: 0, error: undefined })
    processFile(task.file, task.id)
  }

  const cancelTask = (task: UploadTask) => {
    task.abortController?.abort()
    if (task.previewUrl) revokePreviewUrl(task.previewUrl)
    removeUploadTask(task.id)
  }

  return (
    <div className="space-y-3">
      <div
        role="button"
        tabIndex={0}
        onKeyDown={(e) => e.key === 'Enter' && inputRef.current?.click()}
        onClick={() => inputRef.current?.click()}
        onDragOver={(e) => { e.preventDefault(); setDragOver(true) }}
        onDragLeave={() => setDragOver(false)}
        onDrop={(e) => {
          e.preventDefault()
          setDragOver(false)
          if (e.dataTransfer.files.length) enqueueFiles(e.dataTransfer.files)
        }}
        className={`relative rounded-xl border-2 border-dashed p-6 text-center cursor-pointer transition-all duration-300 ${
          dragOver
            ? 'border-primary bg-primary/10 scale-[1.01]'
            : 'border-card-border hover:border-primary/40 hover:bg-primary/5'
        }`}
      >
        <Upload className="h-8 w-8 mx-auto mb-2 text-primary/70" />
        <p className="text-sm font-medium">پانورامای ۳۶۰ درجه را اینجا رها کنید</p>
        <p className="text-xs text-muted mt-1">یا کلیک کنید — JPEG/PNG/WebP تا ۱۰۰MB — نسبت ۲:۱</p>
        <input
          ref={inputRef}
          type="file"
          accept="image/jpeg,image/png,image/webp"
          multiple
          className="hidden"
          onChange={(e) => e.target.files && enqueueFiles(e.target.files)}
        />
      </div>

      {uploadTasks.length > 0 && (
        <div className="space-y-2 max-h-48 overflow-y-auto">
          {uploadTasks.map((task) => (
            <div
              key={task.id}
              className="flex items-center gap-3 p-2.5 rounded-xl bg-black/20 border border-card-border/50"
            >
              {task.previewUrl ? (
                <img src={task.previewUrl} alt="" className="w-12 h-8 rounded object-cover shrink-0" />
              ) : (
                <div className="w-12 h-8 rounded bg-muted/20 shrink-0" />
              )}
              <div className="flex-1 min-w-0">
                <p className="text-xs font-medium truncate">{task.name}</p>
                {task.status === 'uploading' && (
                  <div className="mt-1 h-1 rounded-full bg-white/10 overflow-hidden">
                    <div className="h-full bg-primary transition-all" style={{ width: `${task.progress}%` }} />
                  </div>
                )}
                {task.error && <p className="text-[10px] text-danger mt-0.5">{task.error}</p>}
              </div>
              <div className="flex items-center gap-1 shrink-0">
                {task.status === 'uploading' && <Loader2 className="h-4 w-4 animate-spin text-primary" />}
                {task.status === 'success' && <CheckCircle className="h-4 w-4 text-success" />}
                {task.status === 'error' && (
                  <>
                    <AlertCircle className="h-4 w-4 text-danger" />
                    <Button variant="ghost" size="icon" className="h-7 w-7" onClick={() => retryTask(task)}>
                      <RotateCcw className="h-3 w-3" />
                    </Button>
                  </>
                )}
                {(task.status === 'uploading' || task.status === 'pending' || task.status === 'error') && (
                  <Button variant="ghost" size="icon" className="h-7 w-7" onClick={() => cancelTask(task)}>
                    <X className="h-3 w-3" />
                  </Button>
                )}
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  )
}
