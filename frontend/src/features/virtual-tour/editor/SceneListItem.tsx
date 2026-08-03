import { useState } from 'react'
import {
  GripVertical, Copy, Trash2, Eye, EyeOff, Globe, Star, Pencil, Check, X,
} from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import type { TourScene } from '../types'

interface Props {
  scene: TourScene
  isActive: boolean
  isDragging: boolean
  onSelect: () => void
  onRename: (name: string) => void
  onDuplicate: () => void
  onDelete: () => void
  onPublish: () => void
  onUnpublish: () => void
  onToggleVisibility: () => void
  onSetDefault: () => void
  onDragStart: (e: React.DragEvent) => void
  onDragOver: (e: React.DragEvent) => void
  onDrop: (e: React.DragEvent) => void
  onDragEnd: () => void
}

export function SceneListItem({
  scene,
  isActive,
  isDragging,
  onSelect,
  onRename,
  onDuplicate,
  onDelete,
  onPublish,
  onUnpublish,
  onToggleVisibility,
  onSetDefault,
  onDragStart,
  onDragOver,
  onDrop,
  onDragEnd,
}: Props) {
  const [editing, setEditing] = useState(false)
  const [editName, setEditName] = useState(scene.name)

  const commitRename = () => {
    if (editName.trim() && editName !== scene.name) {
      onRename(editName.trim())
    }
    setEditing(false)
  }

  return (
    <div
      draggable
      onDragStart={onDragStart}
      onDragOver={onDragOver}
      onDrop={onDrop}
      onDragEnd={onDragEnd}
      onClick={onSelect}
      className={`group flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer transition-all duration-200 ${
        isActive
          ? 'bg-primary/10 border-primary/40 shadow-lg shadow-primary/5'
          : 'bg-black/20 border-card-border/50 hover:bg-white/5 hover:border-white/10'
      } ${isDragging ? 'opacity-50 scale-[0.98]' : ''}`}
    >
      <button
        type="button"
        className="cursor-grab active:cursor-grabbing p-0.5 text-muted hover:text-foreground"
        onClick={(e) => e.stopPropagation()}
        onMouseDown={(e) => e.stopPropagation()}
      >
        <GripVertical className="h-4 w-4" />
      </button>

      {scene.thumbnail_url ? (
        <img src={scene.thumbnail_url} alt="" className="w-14 h-9 rounded-lg object-cover shrink-0 border border-white/10" />
      ) : (
        <div className="w-14 h-9 rounded-lg bg-gradient-to-br from-primary/20 to-purple-500/20 flex items-center justify-center text-[10px] font-bold shrink-0 border border-white/10">
          ۳۶۰
        </div>
      )}

      <div className="flex-1 min-w-0">
        {editing ? (
          <div className="flex items-center gap-1" onClick={(e) => e.stopPropagation()}>
            <input
              value={editName}
              onChange={(e) => setEditName(e.target.value)}
              className="flex-1 text-xs bg-black/40 border border-card-border rounded px-2 py-1 outline-none focus:border-primary"
              onKeyDown={(e) => {
                if (e.key === 'Enter') commitRename()
                if (e.key === 'Escape') setEditing(false)
              }}
              autoFocus
            />
            <button type="button" onClick={commitRename} className="text-success"><Check className="h-3.5 w-3.5" /></button>
            <button type="button" onClick={() => setEditing(false)} className="text-muted"><X className="h-3.5 w-3.5" /></button>
          </div>
        ) : (
          <div className="flex items-center gap-1.5">
            <p className="text-sm font-medium truncate">{scene.name}</p>
            {scene.is_default && <Star className="h-3 w-3 text-warning fill-warning shrink-0" />}
          </div>
        )}
        <div className="flex items-center gap-1.5 mt-1">
          <Badge variant={scene.status === 'published' ? 'default' : 'outline'} className="text-[9px] px-1.5 py-0">
            {scene.status === 'published' ? 'منتشر' : 'پیش‌نویس'}
          </Badge>
          {!scene.is_visible && (
            <Badge variant="outline" className="text-[9px] px-1.5 py-0 text-muted">مخفی</Badge>
          )}
        </div>
      </div>

      <div className="flex items-center gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity" onClick={(e) => e.stopPropagation()}>
        <Button variant="ghost" size="icon" className="h-7 w-7" title="ویرایش نام" onClick={() => { setEditName(scene.name); setEditing(true) }}>
          <Pencil className="h-3 w-3" />
        </Button>
        <Button variant="ghost" size="icon" className="h-7 w-7" title="مخفی/نمایش" onClick={onToggleVisibility}>
          {scene.is_visible ? <Eye className="h-3 w-3" /> : <EyeOff className="h-3 w-3" />}
        </Button>
        {scene.status === 'published' ? (
          <Button variant="ghost" size="icon" className="h-7 w-7" title="پیش‌نویس" onClick={onUnpublish}>
            <Globe className="h-3 w-3 text-success" />
          </Button>
        ) : (
          <Button variant="ghost" size="icon" className="h-7 w-7" title="انتشار" onClick={onPublish}>
            <Globe className="h-3 w-3" />
          </Button>
        )}
        {!scene.is_default && (
          <Button variant="ghost" size="icon" className="h-7 w-7" title="پیش‌فرض" onClick={onSetDefault}>
            <Star className="h-3 w-3" />
          </Button>
        )}
        <Button variant="ghost" size="icon" className="h-7 w-7" title="کپی" onClick={onDuplicate}>
          <Copy className="h-3 w-3" />
        </Button>
        <Button variant="ghost" size="icon" className="h-7 w-7 text-danger" title="حذف" onClick={onDelete}>
          <Trash2 className="h-3 w-3" />
        </Button>
      </div>
    </div>
  )
}
