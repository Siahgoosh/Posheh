import { useRef } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { ArrowRight, Globe, ExternalLink, BookOpen } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { tourApi } from '../api/tourApi'
import { TourViewer, type TourViewerHandle } from '../engine/TourViewer'
import { SceneManagerPanel } from './SceneManagerPanel'
import { useTourEditorStore } from '../store/editorStore'
import type { TourData } from '../types'

interface Props {
  tourId: string
}

export function TourEditorLayout({ tourId }: Props) {
  const queryClient = useQueryClient()
  const viewerRef = useRef<TourViewerHandle>(null)
  const { activeSceneId, setActiveSceneId } = useTourEditorStore()

  const { data: tour, isLoading, refetch } = useQuery({
    queryKey: ['virtual-tour', tourId],
    queryFn: async () => (await tourApi.get(tourId)).data.data as TourData,
    enabled: !!tourId,
  })

  const invalidate = () => {
    queryClient.invalidateQueries({ queryKey: ['virtual-tour', tourId] })
    refetch()
  }

  const publishMutation = useMutation({
    mutationFn: (status: string) => tourApi.update(tourId, { status: status as 'draft' | 'published' }),
    onSuccess: invalidate,
  })

  const renameMutation = useMutation({
    mutationFn: ({ sceneId, name }: { sceneId: number; name: string }) =>
      tourApi.updateScene(tourId, sceneId, { name }),
    onSuccess: invalidate,
  })

  const duplicateMutation = useMutation({
    mutationFn: (sceneId: number) => tourApi.duplicateScene(tourId, sceneId),
    onSuccess: invalidate,
  })

  const deleteMutation = useMutation({
    mutationFn: (sceneId: number) => tourApi.deleteScene(tourId, sceneId),
    onSuccess: invalidate,
  })

  const publishSceneMutation = useMutation({
    mutationFn: (sceneId: number) => tourApi.publishScene(tourId, sceneId),
    onSuccess: invalidate,
  })

  const unpublishSceneMutation = useMutation({
    mutationFn: (sceneId: number) => tourApi.unpublishScene(tourId, sceneId),
    onSuccess: invalidate,
  })

  const visibilityMutation = useMutation({
    mutationFn: (sceneId: number) => {
      const scene = tour?.scenes.find((s) => s.id === sceneId)
      return tourApi.updateScene(tourId, sceneId, { is_visible: !scene?.is_visible })
    },
    onSuccess: invalidate,
  })

  const defaultMutation = useMutation({
    mutationFn: (sceneId: number) => tourApi.setDefaultScene(tourId, sceneId),
    onSuccess: invalidate,
  })

  const reorderMutation = useMutation({
    mutationFn: (sceneIds: number[]) => tourApi.reorderScenes(tourId, sceneIds),
    onSuccess: invalidate,
  })

  if (isLoading || !tour) {
    return (
      <div className="flex justify-center items-center min-h-[60vh]">
        <div className="flex flex-col items-center gap-3">
          <div className="h-10 w-10 animate-spin rounded-full border-2 border-primary border-t-transparent" />
          <p className="text-sm text-muted">در حال بارگذاری ویرایشگر...</p>
        </div>
      </div>
    )
  }

  const handleSceneSelect = (sceneId: number) => {
    setActiveSceneId(sceneId)
    viewerRef.current?.goToScene(sceneId)
  }

  return (
    <div className="flex flex-col h-[calc(100vh-5rem)] -mx-4 -my-2">
      {/* Header */}
      <div className="flex items-center gap-3 px-4 py-3 border-b border-card-border/50 bg-black/20 backdrop-blur-md">
        <Link to="/virtual-tours">
          <Button variant="ghost" size="icon"><ArrowRight className="h-5 w-5" /></Button>
        </Link>
        <div className="flex-1 min-w-0">
          <h1 className="text-lg font-bold truncate">{tour.title}</h1>
          <p className="text-[11px] text-muted">ویرایشگر تور مجازی ۳۶۰ درجه</p>
        </div>
        <Badge variant={tour.status === 'published' ? 'default' : 'outline'}>
          {tour.status === 'published' ? 'منتشر شده' : 'پیش‌نویس'}
        </Badge>
        {tour.status !== 'published' ? (
          <Button size="sm" onClick={() => publishMutation.mutate('published')}>
            <Globe className="h-4 w-4" />انتشار تور
          </Button>
        ) : (
          <a href={`/tour/${tour.slug}`} target="_blank" rel="noreferrer">
            <Button size="sm" variant="outline"><ExternalLink className="h-4 w-4" />مشاهده عمومی</Button>
          </a>
        )}
        <a href="/virtual-tour-guide.html" target="_blank" rel="noreferrer">
          <Button variant="ghost" size="sm"><BookOpen className="h-4 w-4" /></Button>
        </a>
      </div>

      {/* Main layout */}
      <div className="flex flex-1 min-h-0">
        {/* Left panel */}
        <aside className="w-80 lg:w-96 shrink-0 border-l border-card-border/50 bg-black/30 backdrop-blur-xl overflow-hidden flex flex-col">
          <SceneManagerPanel
            tourId={tourId}
            scenes={tour.scenes}
            onSceneSelect={handleSceneSelect}
            onSceneRename={(id, name) => renameMutation.mutate({ sceneId: id, name })}
            onSceneDuplicate={(id) => duplicateMutation.mutate(id)}
            onSceneDelete={(id) => {
              if (confirm('حذف این صحنه؟')) deleteMutation.mutate(id)
            }}
            onScenePublish={(id) => publishSceneMutation.mutate(id)}
            onSceneUnpublish={(id) => unpublishSceneMutation.mutate(id)}
            onSceneToggleVisibility={(id) => visibilityMutation.mutate(id)}
            onSceneSetDefault={(id) => defaultMutation.mutate(id)}
            onSceneReorder={(ids) => reorderMutation.mutate(ids)}
            onRefresh={invalidate}
          />
        </aside>

        {/* Viewer */}
        <main className="flex-1 min-w-0 relative bg-black">
          {tour.scenes.length > 0 ? (
            <TourViewer
              ref={viewerRef}
              tour={tour}
              initialSceneId={activeSceneId}
              onSceneChange={setActiveSceneId}
              className="h-full min-h-0"
              showControls
              showSceneName
            />
          ) : (
            <div className="flex flex-col items-center justify-center h-full text-center p-8">
              <div className="w-24 h-24 rounded-2xl bg-gradient-to-br from-primary/20 to-purple-500/20 flex items-center justify-center text-3xl font-bold mb-4 border border-white/10">
                ۳۶۰
              </div>
              <h2 className="text-lg font-semibold mb-2">اولین صحنه را اضافه کنید</h2>
              <p className="text-sm text-muted max-w-md">
                پانورامای equirectangular خود را از پنل سمت چپ آپلود کنید تا تور ۳۶۰ درجه شما ساخته شود.
              </p>
            </div>
          )}
        </main>
      </div>
    </div>
  )
}
