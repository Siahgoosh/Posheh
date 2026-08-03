/** Theme tokens for public office websites (modern / classic / luxury). */

export interface OfficeThemeInput {
  id?: string
  brand_color?: string
  hero_style?: string
  card_style?: string
  header_style?: string
}

export interface SiteThemeTokens {
  id: string
  brand: string
  page: string
  header: string
  headerText: string
  nav: string
  navHover: string
  hero: string
  heroOverlay: string
  heading: string
  body: string
  muted: string
  card: string
  cardHover: string
  cardBorder: string
  sectionAlt: string
  footer: string
  footerMuted: string
  chipInactive: string
  input: string
}

export function resolveSiteTheme(theme?: OfficeThemeInput, fallbackBrand = '#0f766e'): SiteThemeTokens {
  const id = theme?.id || 'modern'
  const brand = theme?.brand_color || fallbackBrand

  if (id === 'luxury') {
    return {
      id,
      brand,
      page: 'bg-neutral-950 text-neutral-100',
      header: 'bg-neutral-900/95 border-neutral-800 text-neutral-100',
      headerText: 'text-neutral-100',
      nav: 'text-neutral-400',
      navHover: 'hover:text-amber-200',
      hero: 'text-neutral-50',
      heroOverlay: `linear-gradient(135deg, ${brand}33 0%, #0a0a0a 55%, transparent 100%)`,
      heading: 'text-neutral-50',
      body: 'text-neutral-300',
      muted: 'text-neutral-500',
      card: 'bg-neutral-900 border-neutral-800 text-neutral-100',
      cardHover: 'hover:border-amber-500/40 hover:shadow-amber-500/10',
      cardBorder: 'border-neutral-800',
      sectionAlt: 'bg-neutral-900/50 border-neutral-800',
      footer: 'bg-black text-neutral-400 border-neutral-800',
      footerMuted: 'text-neutral-600',
      chipInactive: 'bg-neutral-900 text-neutral-300 border-neutral-700',
      input: 'bg-neutral-900 border-neutral-700 text-neutral-100 placeholder:text-neutral-500',
    }
  }

  if (id === 'classic') {
    return {
      id,
      brand,
      page: 'bg-stone-100 text-stone-900',
      header: 'bg-slate-900 text-white border-slate-800',
      headerText: 'text-white',
      nav: 'text-slate-300',
      navHover: 'hover:text-white',
      hero: 'text-white',
      heroOverlay: `linear-gradient(160deg, ${brand} 0%, #1e293b 70%)`,
      heading: 'text-stone-900',
      body: 'text-stone-700',
      muted: 'text-stone-500',
      card: 'bg-white border-stone-200 text-stone-900 shadow-sm',
      cardHover: 'hover:shadow-md hover:border-stone-300',
      cardBorder: 'border-stone-200',
      sectionAlt: 'bg-white border-stone-200',
      footer: 'bg-slate-900 text-slate-300 border-slate-800',
      footerMuted: 'text-slate-500',
      chipInactive: 'bg-white text-stone-600 border-stone-300',
      input: 'bg-white border-stone-300 text-stone-900',
    }
  }

  // modern (default)
  return {
    id: 'modern',
    brand,
    page: 'bg-slate-50 text-slate-800',
    header: 'bg-white/90 border-slate-200 text-slate-900',
    headerText: 'text-slate-900',
    nav: 'text-slate-600',
    navHover: 'hover:text-slate-900',
    hero: 'text-slate-900',
    heroOverlay: `linear-gradient(135deg, ${brand}18 0%, transparent 65%)`,
    heading: 'text-slate-900',
    body: 'text-slate-600',
    muted: 'text-slate-400',
    card: 'bg-white border-slate-200 text-slate-900 shadow-sm',
    cardHover: 'hover:shadow-lg hover:border-slate-300',
    cardBorder: 'border-slate-200',
    sectionAlt: 'bg-white border-slate-200',
    footer: 'bg-slate-900 text-slate-300 border-slate-800',
    footerMuted: 'text-slate-500',
    chipInactive: 'bg-white text-slate-600 border-slate-200',
    input: 'bg-white border-slate-200 text-slate-900',
  }
}
