import { useCallback, useRef, useState } from 'react'
import type { TourHotspot } from '../../types'

const MAX_HISTORY = 50

export function useHotspotEditor(initial: TourHotspot[]) {
  const [hotspots, setHotspots] = useState<TourHotspot[]>(initial)
  const undoStack = useRef<TourHotspot[][]>([])
  const redoStack = useRef<TourHotspot[][]>([])
  const clipboard = useRef<TourHotspot[]>([])
  const [selectedIds, setSelectedIds] = useState<Set<number | string>>(new Set())

  const pushHistory = useCallback((prev: TourHotspot[]) => {
    undoStack.current.push(prev.map((h) => ({ ...h, style: { ...h.style }, action: { ...h.action }, popup: { ...h.popup } })))
    if (undoStack.current.length > MAX_HISTORY) undoStack.current.shift()
    redoStack.current = []
  }, [])

  const commit = useCallback((next: TourHotspot[]) => {
    pushHistory(hotspots)
    setHotspots(next)
  }, [hotspots, pushHistory])

  const updateHotspot = useCallback((id: number | string, patch: Partial<TourHotspot>) => {
    commit(hotspots.map((h) => (h.id === id ? { ...h, ...patch, style: { ...h.style, ...patch.style } } : h)))
  }, [hotspots, commit])

  const moveHotspot = useCallback((h: TourHotspot, x: number, y: number) => {
    setHotspots((prev) =>
      prev.map((item) => (item.id === h.id ? { ...item, position_x: x, position_y: y } : item)),
    )
  }, [])

  const commitMove = useCallback(() => {
    pushHistory(hotspots)
  }, [hotspots, pushHistory])

  const resizeHotspot = useCallback((h: TourHotspot, size: number) => {
    updateHotspot(h.id, { style: { ...h.style, size } })
  }, [updateHotspot])

  const rotateHotspot = useCallback((h: TourHotspot, rotation: number) => {
    updateHotspot(h.id, { style: { ...h.style, rotation } })
  }, [updateHotspot])

  const deleteHotspot = useCallback((id: number | string) => {
    commit(hotspots.filter((h) => h.id !== id))
    setSelectedIds((s) => {
      const n = new Set(s)
      n.delete(id)
      return n
    })
  }, [hotspots, commit])

  const duplicateHotspot = useCallback((id: number | string) => {
    const h = hotspots.find((x) => x.id === id)
    if (!h) return
    const copy: TourHotspot = {
      ...h,
      id: `temp-${Date.now()}`,
      position_x: (h.position_x ?? 50) + 3,
      position_y: (h.position_y ?? 50) + 3,
      style: { ...h.style },
      action: { ...h.action },
      popup: { ...h.popup },
    }
    commit([...hotspots, copy])
    setSelectedIds(new Set([copy.id]))
  }, [hotspots, commit])

  const undo = useCallback(() => {
    const prev = undoStack.current.pop()
    if (!prev) return
    redoStack.current.push(hotspots)
    setHotspots(prev)
  }, [hotspots])

  const redo = useCallback(() => {
    const next = redoStack.current.pop()
    if (!next) return
    undoStack.current.push(hotspots)
    setHotspots(next)
  }, [hotspots])

  const copySelected = useCallback(() => {
    clipboard.current = hotspots.filter((h) => selectedIds.has(h.id))
  }, [hotspots, selectedIds])

  const paste = useCallback(() => {
    if (!clipboard.current.length) return
    const pasted = clipboard.current.map((h, i) => ({
      ...h,
      id: `temp-${Date.now()}-${i}`,
      position_x: (h.position_x ?? 50) + 5,
      position_y: (h.position_y ?? 50) + 5,
    }))
    commit([...hotspots, ...pasted])
  }, [hotspots, commit])

  const toggleLock = useCallback((id: number | string) => {
    const h = hotspots.find((x) => x.id === id)
    if (!h) return
    updateHotspot(id, { style: { ...h.style, locked: !h.style?.locked } })
  }, [hotspots, updateHotspot])

  const setLayer = useCallback((id: number | string, zIndex: number) => {
    const h = hotspots.find((x) => x.id === id)
    if (!h) return
    updateHotspot(id, { style: { ...h.style, zIndex } })
  }, [hotspots, updateHotspot])

  const selectHotspot = useCallback((id: number | string | null, multi = false) => {
    if (!id) {
      setSelectedIds(new Set())
      return
    }
    if (multi) {
      setSelectedIds((s) => {
        const n = new Set(s)
        if (n.has(id)) n.delete(id)
        else n.add(id)
        return n
      })
    } else {
      setSelectedIds(new Set([id]))
    }
  }, [])

  const reset = useCallback((list: TourHotspot[]) => {
    undoStack.current = []
    redoStack.current = []
    setHotspots(list)
    setSelectedIds(new Set())
  }, [])

  return {
    hotspots,
    selectedIds,
    selectedId: selectedIds.size === 1 ? [...selectedIds][0] : null,
    setHotspots: commit,
    updateHotspot,
    moveHotspot,
    commitMove,
    resizeHotspot,
    rotateHotspot,
    deleteHotspot,
    duplicateHotspot,
    undo,
    redo,
    canUndo: undoStack.current.length > 0,
    canRedo: redoStack.current.length > 0,
    copySelected,
    paste,
    toggleLock,
    setLayer,
    selectHotspot,
    reset,
  }
}
