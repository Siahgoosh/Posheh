import { ENAMAD } from '@/constants/site'

/** Official Enamad trust seal — required visible widget for Iranian e-commerce trust. */
export function EnamadBadge({ className = '' }: { className?: string }) {
  return (
    <a
      href={ENAMAD.trustUrl}
      target="_blank"
      rel="noopener noreferrer"
      referrerPolicy="origin"
      className={`inline-block shrink-0 ${className}`}
      aria-label="نماد اعتماد الکترونیکی — اینماد"
    >
      <img
        src={ENAMAD.logoUrl}
        alt="نماد اعتماد الکترونیکی"
        referrerPolicy="origin"
        className="h-[90px] w-auto cursor-pointer"
        data-code={ENAMAD.code}
      />
    </a>
  )
}
