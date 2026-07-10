import { useRef, useCallback, useEffect } from 'react'
import { Bold, Italic, Link as LinkIcon, Image as ImageIcon, Heading2, List } from 'lucide-react'
import { Button } from '@/components/ui/button'

interface RichTextEditorProps {
  value: string
  onChange: (html: string) => void
  onUploadImage?: (file: File, alt: string) => Promise<string>
  placeholder?: string
  editorKey?: string
}

export function RichTextEditor({ value, onChange, onUploadImage, placeholder, editorKey }: RichTextEditorProps) {
  const editorRef = useRef<HTMLDivElement>(null)

  useEffect(() => {
    if (editorRef.current && editorRef.current.innerHTML !== value) {
      editorRef.current.innerHTML = value
    }
  }, [editorKey, value])

  const sync = useCallback(() => {
    onChange(editorRef.current?.innerHTML ?? '')
  }, [onChange])

  const exec = useCallback((command: string, val?: string) => {
    document.execCommand(command, false, val)
    editorRef.current?.focus()
    sync()
  }, [sync])

  const insertLink = () => {
    const url = window.prompt('آدرس لینک (https://...)')
    if (!url) return
    exec('createLink', url)
  }

  const insertImage = async () => {
    if (!onUploadImage) {
      const url = window.prompt('آدرس تصویر (URL)')
      const alt = window.prompt('متن جایگزین (alt)') || ''
      if (url) exec('insertHTML', `<img src="${url}" alt="${alt}" loading="lazy" />`)
      return
    }

    const input = document.createElement('input')
    input.type = 'file'
    input.accept = 'image/*'
    input.onchange = async () => {
      const file = input.files?.[0]
      if (!file) return
      const alt = window.prompt('متن جایگزین تصویر (alt) — برای سئو') || ''
      try {
        const html = await onUploadImage(file, alt)
        exec('insertHTML', html)
      } catch {
        window.alert('آپلود تصویر ناموفق بود')
      }
    }
    input.click()
  }

  return (
    <div className="space-y-2">
      <div className="flex flex-wrap gap-1 rounded-xl border border-card-border bg-card/50 p-2">
        <Button type="button" variant="ghost" size="sm" onClick={() => exec('bold')} title="درشت">
          <Bold className="h-4 w-4" />
        </Button>
        <Button type="button" variant="ghost" size="sm" onClick={() => exec('italic')} title="کج">
          <Italic className="h-4 w-4" />
        </Button>
        <Button type="button" variant="ghost" size="sm" onClick={() => exec('formatBlock', 'h2')} title="تیتر">
          <Heading2 className="h-4 w-4" />
        </Button>
        <Button type="button" variant="ghost" size="sm" onClick={() => exec('insertUnorderedList')} title="لیست">
          <List className="h-4 w-4" />
        </Button>
        <Button type="button" variant="ghost" size="sm" onClick={insertLink} title="لینک">
          <LinkIcon className="h-4 w-4" />
        </Button>
        <Button type="button" variant="ghost" size="sm" onClick={insertImage} title="تصویر">
          <ImageIcon className="h-4 w-4" />
        </Button>
      </div>
      <div
        ref={editorRef}
        contentEditable
        dir="rtl"
        role="textbox"
        aria-label={placeholder}
        className="min-h-[280px] rounded-xl border border-card-border bg-background/50 p-4 text-sm leading-8 outline-none focus:ring-2 focus:ring-primary/30 prose prose-invert max-w-none empty:before:content-[attr(data-placeholder)] empty:before:text-muted"
        onInput={sync}
        data-placeholder={placeholder}
      />
    </div>
  )
}
