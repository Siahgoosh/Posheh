import { useParams } from 'react-router-dom'
import { TourEditorLayout } from '@/features/virtual-tour'

export function VirtualTourEditorPage() {
  const { id } = useParams<{ id: string }>()
  if (!id) return null
  return (
    <div className="min-w-0 w-full overflow-hidden">
      <TourEditorLayout tourId={id} />
    </div>
  )
}
