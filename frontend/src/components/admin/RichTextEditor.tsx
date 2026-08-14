import { useRef, useCallback, useEffect } from 'react'
import {
  Bold, Italic, Underline, Link as LinkIcon, Image as ImageIcon,
  Heading1, Heading2, Heading3, List, ListOrdered, Quote, Minus, Code,
} from 'lucide-react'
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

  const insertHtml = (html: string) => {
    exec('insertHTML', html)
  }

  const insertLink = () => {
    const url = window.prompt('آدرس لینک (https://...)')
    if (!url) return
    exec('createLink', url)
  }

  const insertImage = async () => {
    if (!onUploadImage) {
      const url = window.prompt('آدرس تصویر (URL)')
      const alt = window.prompt('متن جایگزین (alt)') || ''
      if (url) insertHtml(`<img src="${url}" alt="${alt}" loading="lazy" />`)
      return
    }

    const input = document.createElement('input')
    input.type = 'file'
    input.accept = 'image/jpeg,image/png,image/webp,image/gif'
    input.onchange = async () => {
      const file = input.files?.[0]
      if (!file) return
      const alt = window.prompt('متن جایگزین تصویر (alt) — بدون keyword stuffing') || ''
      try {
        const html = await onUploadImage(file, alt)
        insertHtml(html)
      } catch {
        window.alert('آپلود تصویر ناموفق بود')
      }
    }
    input.click()
  }

  const insertVideo = () => {
    const url = window.prompt('آدرس ویدیو (YouTube/Aparat embed URL)')
    if (!url) return
    insertHtml(`<div class="video-embed"><iframe src="${url.replace(/"/g, '')}" title="video" loading="lazy" allowfullscreen></iframe></div>`)
  }

  const insertButton = () => {
    const text = window.prompt('متن دکمه', 'ادامه مطلب') || 'ادامه مطلب'
    const href = window.prompt('لینک دکمه', '/register') || '#'
    insertHtml(`<p><a class="btn-cta" href="${href.replace(/"/g, '')}">${text}</a></p>`)
  }

  const insertCallout = () => {
    insertHtml('<aside class="callout"><p>نکته مهم…</p></aside>')
  }

  const insertFaqBlock = () => {
    insertHtml('<div class="faq-block"><h3>سوال؟</h3><p>پاسخ…</p></div>')
  }

  const insertToc = () => {
    insertHtml('<nav class="toc" data-auto-toc="1"><strong>فهرست مطالب</strong><ul><li>از H2/H3 تولید می‌شود</li></ul></nav>')
  }

  const insertTable = () => {
    insertHtml('<table><thead><tr><th>ستون ۱</th><th>ستون ۲</th></tr></thead><tbody><tr><td>—</td><td>—</td></tr></tbody></table>')
  }

  return (
    <div className="space-y-2">
      <div className="flex flex-wrap gap-1 rounded-xl border border-card-border bg-card/50 p-2">
        <Button type="button" variant="ghost" size="sm" onClick={() => exec('bold')} title="درشت"><Bold className="h-4 w-4" /></Button>
        <Button type="button" variant="ghost" size="sm" onClick={() => exec('italic')} title="کج"><Italic className="h-4 w-4" /></Button>
        <Button type="button" variant="ghost" size="sm" onClick={() => exec('underline')} title="زیرخط"><Underline className="h-4 w-4" /></Button>
        <Button type="button" variant="ghost" size="sm" onClick={() => exec('formatBlock', 'h1')} title="H1"><Heading1 className="h-4 w-4" /></Button>
        <Button type="button" variant="ghost" size="sm" onClick={() => exec('formatBlock', 'h2')} title="H2"><Heading2 className="h-4 w-4" /></Button>
        <Button type="button" variant="ghost" size="sm" onClick={() => exec('formatBlock', 'h3')} title="H3"><Heading3 className="h-4 w-4" /></Button>
        <Button type="button" variant="ghost" size="sm" onClick={() => exec('formatBlock', 'h4')} title="H4" className="text-xs font-bold">H4</Button>
        <Button type="button" variant="ghost" size="sm" onClick={() => exec('formatBlock', 'p')} title="پاراگراف" className="text-xs">P</Button>
        <Button type="button" variant="ghost" size="sm" onClick={() => exec('formatBlock', 'blockquote')} title="نقل‌قول"><Quote className="h-4 w-4" /></Button>
        <Button type="button" variant="ghost" size="sm" onClick={() => exec('insertUnorderedList')} title="لیست"><List className="h-4 w-4" /></Button>
        <Button type="button" variant="ghost" size="sm" onClick={() => exec('insertOrderedList')} title="لیست شماره‌دار"><ListOrdered className="h-4 w-4" /></Button>
        <Button type="button" variant="ghost" size="sm" onClick={() => insertHtml('<ul class="checklist"><li>☐ مورد</li></ul>')} title="چک‌لیست" className="text-xs">☐</Button>
        <Button type="button" variant="ghost" size="sm" onClick={insertTable} title="جدول" className="text-xs">جدول</Button>
        <Button type="button" variant="ghost" size="sm" onClick={() => exec('formatBlock', 'pre')} title="کد"><Code className="h-4 w-4" /></Button>
        <Button type="button" variant="ghost" size="sm" onClick={insertLink} title="لینک"><LinkIcon className="h-4 w-4" /></Button>
        <Button type="button" variant="ghost" size="sm" onClick={insertImage} title="تصویر"><ImageIcon className="h-4 w-4" /></Button>
        <Button type="button" variant="ghost" size="sm" onClick={insertVideo} title="ویدیو" className="text-xs">ویدیو</Button>
        <Button type="button" variant="ghost" size="sm" onClick={insertButton} title="دکمه" className="text-xs">دکمه</Button>
        <Button type="button" variant="ghost" size="sm" onClick={insertCallout} title="Callout" className="text-xs">نکته</Button>
        <Button type="button" variant="ghost" size="sm" onClick={() => exec('insertHorizontalRule')} title="جداکننده"><Minus className="h-4 w-4" /></Button>
        <Button type="button" variant="ghost" size="sm" onClick={insertFaqBlock} title="FAQ" className="text-xs">FAQ</Button>
        <Button type="button" variant="ghost" size="sm" onClick={insertToc} title="TOC" className="text-xs">TOC</Button>
      </div>
      <div
        ref={editorRef}
        contentEditable
        dir="rtl"
        lang="fa"
        role="textbox"
        aria-label={placeholder}
        className="min-h-[320px] rounded-xl border border-card-border bg-background/50 p-4 text-sm leading-8 outline-none focus:ring-2 focus:ring-primary/30 prose prose-invert max-w-none empty:before:content-[attr(data-placeholder)] empty:before:text-muted"
        onInput={sync}
        onPaste={() => {
          // Keep paste manageable; browser default + sync
          setTimeout(sync, 0)
        }}
        data-placeholder={placeholder}
      />
      <p className="text-xs text-muted">ویرایشگر RTL فارسی — نیم‌فاصله در ذخیرهٔ سرور نرمال‌سازی اختیاری دارد (قابل Undo با Revision).</p>
    </div>
  )
}
