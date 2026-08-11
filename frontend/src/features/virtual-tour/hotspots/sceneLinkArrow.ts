export interface SceneLinkArrowOptions {
  color?: string
  size?: number
  label?: string | null
  tooltip?: string | null
  rotation?: number
  pulse?: boolean
  glow?: boolean
  icon?: string
}

function escapeHtml(text: string): string {
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
}

/** SVG arrow path variants for scene-link icons */
function arrowSvg(icon: string, color: string): string {
  const stroke = color
  if (icon === 'chevron') {
    return `<path d="M8 6l6 6-6 6" fill="none" stroke="${stroke}" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>`
  }
  if (icon === 'door') {
    return `<path d="M7 5h8v14H7z" fill="none" stroke="${stroke}" stroke-width="2"/><path d="M11 12h2" stroke="${stroke}" stroke-width="2" stroke-linecap="round"/>`
  }
  return `<path d="M6 12h10M12 6l6 6-6 6" fill="none" stroke="${stroke}" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>`
}

/** Professional scene-link marker HTML for Photo Sphere Viewer markers */
export function buildSceneLinkArrowHtml(options: SceneLinkArrowOptions = {}): string {
  const color = options.color || '#2dd4bf'
  const size = options.size || 48
  const rotation = options.rotation ?? 0
  const pulse = options.pulse ?? true
  const glow = options.glow ?? true
  const icon = options.icon || 'arrow'
  const label = options.label?.trim()
  const tooltip = options.tooltip || label || 'رفتن به صحنه بعدی'
  const inner = Math.round(size * 0.55)
  const glowStyle = glow
    ? `filter: drop-shadow(0 0 8px ${color}aa) drop-shadow(0 4px 12px rgba(0,0,0,0.45));`
    : 'filter: drop-shadow(0 4px 8px rgba(0,0,0,0.4));'

  return `
    <div class="vt-scene-link ${pulse ? 'vt-scene-link-pulse' : ''}" style="transform: rotate(${rotation}deg); ${glowStyle}" title="${escapeHtml(tooltip)}">
      <div class="vt-scene-link-ring" style="width:${size}px;height:${size}px;border-color:${color};">
        <div class="vt-scene-link-core" style="width:${inner}px;height:${inner}px;background:linear-gradient(145deg, ${color}, ${color}cc);">
          <svg viewBox="0 0 24 24" width="${Math.round(inner * 0.55)}" height="${Math.round(inner * 0.55)}" aria-hidden="true">
            ${arrowSvg(icon, '#ffffff')}
          </svg>
        </div>
      </div>
      ${label ? `<div class="vt-scene-link-label">${escapeHtml(label)}</div>` : ''}
    </div>
  `
}

export const SCENE_LINK_ARROW_CSS = `
  .vt-scene-link {
    display: flex;
    flex-direction: column;
    align-items: center;
    cursor: pointer;
    transition: transform 0.2s ease;
  }
  .vt-scene-link:hover { transform: scale(1.12); }
  .vt-scene-link-ring {
    position: relative;
    border-radius: 50%;
    border: 2px solid rgba(255,255,255,0.85);
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0,0,0,0.25);
    backdrop-filter: blur(4px);
  }
  .vt-scene-link-core {
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.25);
  }
  .vt-scene-link-label {
    margin-top: 6px;
    padding: 3px 10px;
    font-size: 11px;
    font-weight: 600;
    color: #fff;
    background: rgba(0,0,0,0.55);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 999px;
    white-space: nowrap;
    text-shadow: 0 1px 2px rgba(0,0,0,0.5);
    backdrop-filter: blur(6px);
    max-width: 140px;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .vt-scene-link-pulse .vt-scene-link-ring::before {
    content: '';
    position: absolute;
    inset: -4px;
    border-radius: 50%;
    border: 2px solid currentColor;
    opacity: 0.45;
    animation: vt-scene-link-ripple 2s ease-out infinite;
    pointer-events: none;
  }
  @keyframes vt-scene-link-ripple {
    0% { transform: scale(0.85); opacity: 0.6; }
    70% { transform: scale(1.15); opacity: 0; }
    100% { transform: scale(1.15); opacity: 0; }
  }
`
