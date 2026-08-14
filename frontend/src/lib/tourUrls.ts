/** Public virtual tour page lives on the main site, not panel subdomain */
export function publicTourUrl(slug: string): string {
  if (typeof window !== 'undefined' && window.location.hostname.startsWith('panel.')) {
    return `https://posheapp.ir/tour/${slug}`
  }

  return `/tour/${slug}`
}

export function virtualTourGuideUrl(): string {
  if (typeof window !== 'undefined' && window.location.hostname.startsWith('panel.')) {
    return 'https://posheapp.ir/virtual-tour-guide.html'
  }

  return '/virtual-tour-guide.html'
}
