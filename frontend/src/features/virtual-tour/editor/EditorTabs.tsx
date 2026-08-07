import { Layers, MapPin, Settings, Sliders, Share2, BarChart3 } from 'lucide-react'
import type { EditorTab } from '../types'

const TABS: { id: EditorTab; label: string; icon: typeof Layers }[] = [
  { id: 'scenes', label: 'صحنه‌ها', icon: Layers },
  { id: 'hotspots', label: 'اتصال', icon: MapPin },
  { id: 'scene-settings', label: 'تنظیمات صحنه', icon: Sliders },
  { id: 'tour-settings', label: 'تنظیمات تور', icon: Settings },
  { id: 'sharing', label: 'اشتراک', icon: Share2 },
  { id: 'analytics', label: 'تحلیل', icon: BarChart3 },
]

interface Props {
  activeTab: EditorTab
  onTabChange: (tab: EditorTab) => void
}

export function EditorTabs({ activeTab, onTabChange }: Props) {
  return (
    <div className="flex border-b border-card-border/50 bg-black/20">
      {TABS.map(({ id, label, icon: Icon }) => (
        <button
          key={id}
          type="button"
          onClick={() => onTabChange(id)}
          className={`flex-1 flex flex-col items-center gap-0.5 py-2.5 text-[10px] transition-all border-b-2 ${
            activeTab === id
              ? 'border-primary text-primary bg-primary/5'
              : 'border-transparent text-muted hover:text-foreground hover:bg-white/5'
          }`}
        >
          <Icon className="h-4 w-4" />
          {label}
        </button>
      ))}
    </div>
  )
}
