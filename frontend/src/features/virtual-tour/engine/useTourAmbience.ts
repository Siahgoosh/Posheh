import { useEffect, useRef, useState } from 'react'
import type { TourData } from '../types'

/** Guided tour autoplay — unchanged */
export function useGuidedTour(
  tour: TourData,
  goToScene: (sceneId: number, options?: { yaw?: number; pitch?: number }) => void,
  editorMode = false,
) {
  const [active, setActive] = useState(false)
  const [stepIndex, setStepIndex] = useState(0)
  const timerRef = useRef<ReturnType<typeof setTimeout> | null>(null)

  const steps = tour.settings?.guided_tour_steps ?? []
  const enabled = !editorMode && tour.settings?.guided_tour && steps.length > 0
  const currentStep = enabled ? steps[stepIndex] : null

  const clearTimer = () => {
    if (timerRef.current) {
      clearTimeout(timerRef.current)
      timerRef.current = null
    }
  }

  const stop = () => {
    clearTimer()
    setActive(false)
    setStepIndex(0)
  }

  const runStep = (index: number) => {
    const step = steps[index]
    if (!step) {
      stop()
      return
    }
    goToScene(step.scene_id, { yaw: step.yaw, pitch: step.pitch })
    setStepIndex(index)
    const delay = (step.delay ?? 6) * 1000
    timerRef.current = setTimeout(() => {
      if (index + 1 >= steps.length) {
        stop()
      } else {
        runStep(index + 1)
      }
    }, delay)
  }

  const start = () => {
    if (!enabled) return
    clearTimer()
    setActive(true)
    runStep(0)
  }

  useEffect(() => () => clearTimer(), [])

  return { enabled, active, currentStep, stepIndex, start, stop }
}
