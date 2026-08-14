import { useQuery, useMutation } from '@tanstack/react-query'
import { History, Save, RotateCcw } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { tourApi } from '../api/tourApi'

interface Props {
  tourId: string | number
  onRestored: () => void
}

export function VersionHistoryPanel({ tourId, onRestored }: Props) {
  const { data, refetch } = useQuery({
    queryKey: ['tour-versions', tourId],
    queryFn: async () => (await tourApi.versions(tourId)).data.data,
  })

  const backupMutation = useMutation({
    mutationFn: (label?: string) => tourApi.backup(tourId, label),
    onSuccess: () => refetch(),
  })

  const restoreMutation = useMutation({
    mutationFn: (versionId: number) => tourApi.restoreVersion(tourId, versionId),
    onSuccess: () => { refetch(); onRestored() },
  })

  const versions = data || []

  return (
    <div className="border-t border-card-border/50 pt-4 mt-4 space-y-3">
      <div className="flex items-center justify-between">
        <h3 className="text-sm font-semibold flex items-center gap-2">
          <History className="h-4 w-4 text-primary" />
          تاریخچه نسخه‌ها
        </h3>
        <Button size="sm" variant="outline" onClick={() => backupMutation.mutate('پشتیبان دستی')} disabled={backupMutation.isPending}>
          <Save className="h-3 w-3" />پشتیبان
        </Button>
      </div>

      <div className="space-y-1.5 max-h-40 overflow-y-auto">
        {versions.map((v: { id: number; version_number: number; label: string; created_at: string; size_bytes: number }) => (
          <div key={v.id} className="flex items-center justify-between p-2 rounded-lg bg-black/20 border border-card-border/50 text-xs">
            <div>
              <span className="font-medium">v{v.version_number}</span>
              <span className="text-muted mr-2">{v.label}</span>
              <span className="text-[10px] text-muted block">{new Date(v.created_at).toLocaleString('fa-IR')}</span>
            </div>
            <Button size="sm" variant="ghost" onClick={() => {
              if (confirm('بازیابی این نسخه؟ نسخه فعلی پشتیبان‌گیری می‌شود.')) {
                restoreMutation.mutate(v.id)
              }
            }}>
              <RotateCcw className="h-3 w-3" />
            </Button>
          </div>
        ))}
        {!versions.length && <p className="text-xs text-muted text-center py-2">نسخه‌ای ذخیره نشده</p>}
      </div>
    </div>
  )
}
