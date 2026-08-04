import { useRef, useEffect, useMemo, useCallback, useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { ArrowRight, Globe, ExternalLink, BookOpen } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { tourApi } from '../api/tourApi'
import { UnifiedTourViewer, type UnifiedTourViewerHandle } from '../engine/UnifiedTourViewer'
import { SceneManagerPanel } from './SceneManagerPanel'
import { EditorTabs } from './EditorTabs'
import { HotspotEditorPanel } from '../hotspots/HotspotEditorPanel'
import { SceneSettingsPanel } from '../settings/SceneSettingsPanel'
import { TourSettingsPanel } from '../settings/TourSettingsPanel'
import { SharingPanel } from '../sharing/SharingPanel'
import { VersionHistoryPanel } from '../settings/VersionHistoryPanel'
import { useTourEditorStore, mergeSceneWithPatches } from '../store/editorStore'
import { createDefaultHotspot, createSceneLinkHotspot } from '../hotspots/hotspotActions'
import { createSmartWalkSceneLinkHotspot, createSmartWalkHotspot } from '../smart-walk/smartWalkHotspots'
import { SceneLinkPickerModal } from '../hotspots/SceneLinkPickerModal'
import type { TourData, TourHotspot, TourScene } from '../types'

interface Props {
  tourId: string
}

export function TourEditorLayout({ tourId }: Props) {
  const queryClient = useQueryClient()
  const viewerRef = useRef<UnifiedTourViewerHandle>(null)
  const {
    activeSceneId,
    setActiveSceneId,
    activeTab,
    setActiveTab,
    selectedHotspotId,
    setSelectedHotspotId,
    isPlacingHotspot,
    setIsPlacingHotspot,
    isLinkingScenes,
    setIsLinkingScenes,
    isRepositioningHotspot,
    setIsRepositioningHotspot,
    localHotspots,
    localScenePatches,
    localTourSettings,
    initHotspots,
    addHotspot,
    updateHotspot,
    removeHotspot,
    patchScene,
    setLocalTourSettings,
  } = useTourEditorStore()

  const [pendingLinkHotspot, setPendingLinkHotspot] = useState<TourHotspot | null>(null)

  const { data: tour, isLoading, refetch } = useQuery({
    queryKey: ['virtual-tour', tourId],
    queryFn: async () => (await tourApi.get(tourId)).data.data as TourData,
    enabled: !!tourId,
  })

  useEffect(() => {
    if (tour?.scenes) initHotspots(tour.scenes)
  }, [tour?.id, initHotspots])

  useEffect(() => {
    if (activeTab === 'hotspots') {
      setIsLinkingScenes(true)
    }
  }, [activeTab, setIsLinkingScenes])

  const invalidate = () => {
    queryClient.invalidateQueries({ queryKey: ['virtual-tour', tourId] })
    refetch()
  }

  const publishMutation = useMutation({
    mutationFn: () => tourApi.publish(tourId),
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

  const saveHotspotsMutation = useMutation({
    mutationFn: ({ sceneId, hotspots }: { sceneId: number; hotspots: TourHotspot[] }) =>
      tourApi.syncHotspots(tourId, sceneId, hotspots.map((h, i) => ({
        type: h.type,
        yaw: h.yaw,
        pitch: h.pitch,
        position_x: h.position_x,
        position_y: h.position_y,
        position_z: h.position_z,
        target_scene_id: h.target_scene_id,
        title: h.title,
        label: h.label,
        tooltip: h.tooltip,
        content: h.content,
        link_url: h.link_url,
        icon: h.icon,
        style: h.style,
        action: h.action,
        popup: h.popup,
        sort_order: i,
      }))),
    onSuccess: invalidate,
  })

  const saveSceneMutation = useMutation({
    mutationFn: ({ sceneId, data }: { sceneId: number; data: Partial<TourScene> }) =>
      tourApi.updateScene(tourId, sceneId, data),
    onSuccess: invalidate,
  })

  const saveTourSettingsMutation = useMutation({
    mutationFn: (settings: Record<string, unknown>) => tourApi.update(tourId, { settings }),
    onSuccess: invalidate,
  })

  const saveSharingMutation = useMutation({
    mutationFn: (data: Record<string, unknown>) => tourApi.updateSharing(tourId, data),
    onSuccess: invalidate,
  })

  const liveTour = useMemo((): TourData | null => {
    if (!tour) return null
    const scenes = tour.scenes.map((s) => {
      const patched = mergeSceneWithPatches(s, localScenePatches[s.id] || {})
      return {
        ...patched,
        hotspots: localHotspots[s.id] ?? patched.hotspots,
      }
    })
    return {
      ...tour,
      settings: { ...tour.settings, ...localTourSettings },
      scenes,
    }
  }, [tour, localHotspots, localScenePatches, localTourSettings])

  const activeScene = liveTour?.scenes.find((s) => s.id === activeSceneId) ?? liveTour?.scenes[0] ?? null
  const activeHotspots = activeScene ? (localHotspots[activeScene.id] ?? activeScene.hotspots) : []

  const isSmartWalk = tour?.tour_type === 'smart_walk'

  const handlePlaceHotspot = useCallback((a: number, b: number) => {
    if (!activeScene) return
    if (isSmartWalk) {
      const x = a
      const y = b
      if (isRepositioningHotspot && selectedHotspotId) {
        const h = activeHotspots.find((x) => x.id === selectedHotspotId)
        if (h) {
          updateHotspot(activeScene.id, { ...h, position_x: x, position_y: y })
          setIsRepositioningHotspot(false)
        }
        return
      }
      if (isLinkingScenes) {
        const hotspot = createSmartWalkSceneLinkHotspot(x, y)
        addHotspot(activeScene.id, hotspot)
        setPendingLinkHotspot(hotspot)
        setActiveTab('hotspots')
        return
      }
      const hotspot = createSmartWalkHotspot(x, y)
      addHotspot(activeScene.id, hotspot)
      setActiveTab('hotspots')
      return
    }

    const yaw = a
    const pitch = b
    if (isRepositioningHotspot && selectedHotspotId) {
      const h = activeHotspots.find((x) => x.id === selectedHotspotId)
      if (h) {
        updateHotspot(activeScene.id, { ...h, yaw, pitch })
        setIsRepositioningHotspot(false)
      }
      return
    }
    if (isLinkingScenes) {
      const hotspot = createSceneLinkHotspot(yaw, pitch)
      addHotspot(activeScene.id, hotspot)
      setPendingLinkHotspot(hotspot)
      setActiveTab('hotspots')
      return
    }
    const hotspot = createDefaultHotspot(yaw, pitch)
    addHotspot(activeScene.id, hotspot)
    setActiveTab('hotspots')
  }, [activeScene, activeHotspots, selectedHotspotId, isRepositioningHotspot, isLinkingScenes, isSmartWalk, addHotspot, updateHotspot, setActiveTab, setIsRepositioningHotspot])

  const handleSceneLinkPick = (targetSceneId: number) => {
    if (!activeScene || !pendingLinkHotspot) return
    const target = liveTour?.scenes.find((s) => s.id === targetSceneId)
    updateHotspot(activeScene.id, {
      ...pendingLinkHotspot,
      target_scene_id: targetSceneId,
      label: target?.name || '',
      tooltip: target ? `رفتن به ${target.name}` : '',
      action: {
        ...pendingLinkHotspot.action,
        type: 'scene',
        target_scene_id: targetSceneId,
      },
    })
    setPendingLinkHotspot(null)
    setSelectedHotspotId(pendingLinkHotspot.id)
  }

  const repositionHotspot = useMemo(
    () => activeHotspots.find((h) => h.id === selectedHotspotId) ?? null,
    [activeHotspots, selectedHotspotId],
  )

  if (isLoading || !tour || !liveTour) {
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

  const renderSidebar = () => {
    switch (activeTab) {
      case 'hotspots':
        return (
          <HotspotEditorPanel
            scene={activeScene}
            scenes={liveTour.scenes}
            selectedHotspotId={selectedHotspotId}
            isPlacing={isPlacingHotspot}
            isLinking={isLinkingScenes}
            isRepositioning={isRepositioningHotspot}
            onSelectHotspot={setSelectedHotspotId}
            onUpdateHotspot={(h) => activeScene && updateHotspot(activeScene.id, h)}
            onDeleteHotspot={(id) => activeScene && removeHotspot(activeScene.id, id)}
            onTogglePlacing={() => {
              setIsLinkingScenes(false)
              setIsPlacingHotspot(!isPlacingHotspot)
            }}
            onToggleLinking={() => {
              setIsRepositioningHotspot(false)
              setIsLinkingScenes(!isLinkingScenes)
            }}
            onToggleRepositioning={() => setIsRepositioningHotspot(!isRepositioningHotspot)}
            onPreviewScene={(id) => viewerRef.current?.goToScene(id)}
            onSave={() => activeScene && saveHotspotsMutation.mutate({ sceneId: activeScene.id, hotspots: localHotspots[activeScene.id] || [] })}
            isSaving={saveHotspotsMutation.isPending}
          />
        )
      case 'scene-settings':
        return (
          <SceneSettingsPanel
            scene={activeScene}
            onUpdate={(patch) => activeScene && patchScene(activeScene.id, patch)}
            onSave={() => {
              if (!activeScene) return
              const patch = localScenePatches[activeScene.id] || {}
              saveSceneMutation.mutate({ sceneId: activeScene.id, data: patch })
            }}
            isSaving={saveSceneMutation.isPending}
          />
        )
      case 'tour-settings':
        return (
          <div className="overflow-y-auto">
            <TourSettingsPanel
              tour={liveTour}
              onUpdateSettings={setLocalTourSettings}
              onSave={() => saveTourSettingsMutation.mutate({ ...tour.settings, ...localTourSettings })}
              isSaving={saveTourSettingsMutation.isPending}
            />
            <VersionHistoryPanel tourId={tourId} onRestored={invalidate} />
          </div>
        )
      case 'sharing':
        return (
          <SharingPanel
            tour={liveTour}
            onUpdate={(data) => saveSharingMutation.mutate(data)}
            isSaving={saveSharingMutation.isPending}
          />
        )
      default:
        return (
          <SceneManagerPanel
            tourId={tourId}
            tourType={liveTour.tour_type}
            scenes={liveTour.scenes}
            onSceneSelect={handleSceneSelect}
            onSceneRename={(id, name) => renameMutation.mutate({ sceneId: id, name })}
            onSceneDuplicate={(id) => duplicateMutation.mutate(id)}
            onSceneDelete={(id) => { if (confirm('حذف این صحنه؟')) deleteMutation.mutate(id) }}
            onScenePublish={(id) => publishSceneMutation.mutate(id)}
            onSceneUnpublish={(id) => unpublishSceneMutation.mutate(id)}
            onSceneToggleVisibility={(id) => visibilityMutation.mutate(id)}
            onSceneSetDefault={(id) => defaultMutation.mutate(id)}
            onSceneReorder={(ids) => reorderMutation.mutate(ids)}
            onRefresh={invalidate}
          />
        )
    }
  }

  return (
    <div className="flex flex-col h-[calc(100vh-5rem)] -mx-4 -my-2">
      <div className="flex items-center gap-3 px-4 py-3 border-b border-card-border/50 bg-black/20 backdrop-blur-md">
        <Link to="/virtual-tours">
          <Button variant="ghost" size="icon"><ArrowRight className="h-5 w-5" /></Button>
        </Link>
        <div className="flex-1 min-w-0">
          <h1 className="text-lg font-bold truncate">{tour.title}</h1>
          <p className="text-[11px] text-muted">
            {isSmartWalk ? 'Poshe Smart Walk — عکس موبایل و اتصال صحنه‌ها' : 'ویرایشگر تور ۳۶۰ — هات‌اسپات و تنظیمات'}
          </p>
        </div>
        <Badge variant={tour.status === 'published' ? 'default' : 'outline'}>
          {tour.status === 'published' ? 'منتشر شده' : 'پیش‌نویس'}
        </Badge>
        {tour.status !== 'published' ? (
          <Button size="sm" onClick={() => publishMutation.mutate()} disabled={publishMutation.isPending}>
            <Globe className="h-4 w-4" />{publishMutation.isPending ? 'در حال انتشار...' : 'انتشار تور'}
          </Button>
        ) : (
          <a href={tour.visibility === 'private' ? (tour.private_url || `/tour/${tour.slug}`) : `/tour/${tour.slug}`} target="_blank" rel="noreferrer">
            <Button size="sm" variant="outline"><ExternalLink className="h-4 w-4" />مشاهده عمومی</Button>
          </a>
        )}
        <a href="/virtual-tour-guide.html" target="_blank" rel="noreferrer">
          <Button variant="ghost" size="sm"><BookOpen className="h-4 w-4" /></Button>
        </a>
      </div>

      <div className="flex flex-1 min-h-0">
        <aside className="w-80 lg:w-96 shrink-0 border-l border-card-border/50 bg-black/30 backdrop-blur-xl overflow-hidden flex flex-col">
          <EditorTabs activeTab={activeTab} onTabChange={setActiveTab} />
          <div className="flex-1 overflow-hidden">{renderSidebar()}</div>
        </aside>

        <main className="flex-1 min-w-0 relative bg-black">
          {liveTour.scenes.length > 0 ? (
            <UnifiedTourViewer
              ref={viewerRef}
              tour={liveTour}
              initialSceneId={activeSceneId}
              onSceneChange={setActiveSceneId}
              className="h-full min-h-0"
              showControls
              showSceneName
              editorMode
              sceneHotspots={activeHotspots}
              isPlacingHotspot={isPlacingHotspot || isLinkingScenes || isRepositioningHotspot}
              isRepositioningHotspot={isRepositioningHotspot}
              repositionHotspot={repositionHotspot}
              onPlaceHotspot={handlePlaceHotspot}
              onHotspotMove={(h, yaw, pitch) => activeScene && updateHotspot(activeScene.id, { ...h, yaw, pitch })}
              onHotspotSelect={(h) => { setSelectedHotspotId(h.id); setActiveTab('hotspots') }}
              onHotspotUpdate={(h) => activeScene && updateHotspot(activeScene.id, h)}
              selectedHotspotId={selectedHotspotId}
            />
          ) : (
            <div className="flex flex-col items-center justify-center h-full text-center p-8">
              <div className="w-24 h-24 rounded-2xl bg-gradient-to-br from-primary/20 to-purple-500/20 flex items-center justify-center text-3xl font-bold mb-4 border border-white/10">
                {isSmartWalk ? 'SW' : '۳۶۰'}
              </div>
              <h2 className="text-lg font-semibold mb-2">اولین صحنه را اضافه کنید</h2>
              <p className="text-sm text-muted max-w-md">
                {isSmartWalk
                  ? 'عکس‌های موبایل را آپلود کنید، سپس نقاط اتصال بین اتاق‌ها را اضافه کنید.'
                  : 'پانوراما را آپلود کنید، سپس هات‌اسپات و تنظیمات را اضافه کنید.'}
              </p>
            </div>
          )}
        </main>
      </div>

      <SceneLinkPickerModal
        open={!!pendingLinkHotspot}
        scenes={liveTour.scenes}
        currentSceneId={activeScene?.id ?? 0}
        onSelect={handleSceneLinkPick}
        onCancel={() => {
          if (pendingLinkHotspot && activeScene) {
            removeHotspot(activeScene.id, pendingLinkHotspot.id)
          }
          setPendingLinkHotspot(null)
        }}
      />
    </div>
  )
}
