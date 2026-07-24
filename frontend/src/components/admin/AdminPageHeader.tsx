import { Link } from 'react-router-dom'
import { ArrowRight } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { adminBase } from '@/lib/adminPaths'

export function AdminPageHeader({
  title,
  description,
  backTo,
}: {
  title: string
  description?: string
  backTo?: string
}) {
  const base = adminBase()
  const back = backTo ?? (base || '/')

  return (
    <div className="flex items-center gap-3 mb-6">
      <Link to={back}>
        <Button variant="ghost" size="icon"><ArrowRight className="h-5 w-5" /></Button>
      </Link>
      <div>
        <h1 className="text-2xl font-bold">{title}</h1>
        {description && <p className="text-sm text-muted mt-0.5">{description}</p>}
      </div>
    </div>
  )
}
