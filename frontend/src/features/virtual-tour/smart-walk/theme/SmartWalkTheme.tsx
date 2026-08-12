import { createContext, useContext, useEffect, useMemo, useState, type ReactNode } from 'react'

export type SmartWalkThemeMode = 'dark' | 'light' | 'auto'

interface ThemeContextValue {
  mode: SmartWalkThemeMode
  resolved: 'dark' | 'light'
  setMode: (m: SmartWalkThemeMode) => void
}

const ThemeContext = createContext<ThemeContextValue | null>(null)

function resolveTheme(mode: SmartWalkThemeMode): 'dark' | 'light' {
  if (mode === 'auto') {
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
  }
  return mode
}

export function SmartWalkThemeProvider({
  children,
  initialMode = 'dark',
}: {
  children: ReactNode
  initialMode?: SmartWalkThemeMode
}) {
  const [mode, setMode] = useState<SmartWalkThemeMode>(initialMode)
  const [resolved, setResolved] = useState<'dark' | 'light'>(() => resolveTheme(initialMode))

  useEffect(() => {
    setResolved(resolveTheme(mode))
    if (mode !== 'auto') return
    const mq = window.matchMedia('(prefers-color-scheme: dark)')
    const onChange = () => setResolved(resolveTheme('auto'))
    mq.addEventListener('change', onChange)
    return () => mq.removeEventListener('change', onChange)
  }, [mode])

  const value = useMemo(() => ({ mode, resolved, setMode }), [mode, resolved])

  return (
    <ThemeContext.Provider value={value}>
      <div
        className={`smart-walk-root h-full w-full ${resolved === 'dark' ? 'smart-walk-dark' : 'smart-walk-light'}`}
        data-theme={resolved}
      >
        {children}
      </div>
    </ThemeContext.Provider>
  )
}

export function useSmartWalkTheme() {
  const ctx = useContext(ThemeContext)
  if (!ctx) throw new Error('useSmartWalkTheme must be used within SmartWalkThemeProvider')
  return ctx
}
