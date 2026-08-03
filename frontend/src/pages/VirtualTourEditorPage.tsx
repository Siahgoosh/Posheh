import { useParams } from 'react-router-dom'
import { TourEditorLayout } from '@/features/virtual-tour'

export function VirtualTourEditorPage() {
  const { id } = useParams<{ id: string }>()
  if (!id) return null
  return <TourEditorLayout tourId={id} />
}
