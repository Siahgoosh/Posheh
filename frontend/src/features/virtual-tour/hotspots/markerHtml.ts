import type { TourHotspot } from '../types'
import { DEFAULT_HOTSPOT_STYLE, getHotspotTypeDef } from './constants'
import { buildSceneLinkArrowHtml, SCENE_LINK_ARROW_CSS } from './sceneLinkArrow'

export function buildHotspotMarkerHtml(hotspot: TourHotspot, brandColor = '#2dd4bf'): string {
  const style = { ...DEFAULT_HOTSPOT_STYLE, ...hotspot.style }
  const typeDef = getHotspotTypeDef(hotspot.type)
  const color = style.color || brandColor
  const size = style.size || 36

  if (hotspot.type === 'scene') {
    return buildSceneLinkArrowHtml({
      color,
      size: Math.max(size, 44),
      label: hotspot.label || hotspot.title || hotspot.target_scene?.name,
      tooltip: hotspot.tooltip || hotspot.label || hotspot.title,
      rotation: style.rotation ?? 0,
      pulse: style.pulse,
      glow: style.glow,
      icon: hotspot.icon || (style.icon as string) || 'arrow',
    })
  }

  const label = hotspot.label || hotspot.title || typeDef.label
  const emoji = typeDef.emoji
  const pulseClass = style.pulse ? 'vt-marker-pulse' : ''
  const glowStyle = style.glow ? `box-shadow: 0 0 20px ${color}88, 0 4px 12px rgba(0,0,0,0.4);` : 'box-shadow: 0 4px 12px rgba(0,0,0,0.4);'
  const border = style.border || '2px solid white'
  const opacity = style.opacity ?? 1
  const hoverAnim = style.hoverAnimation === 'bounce' ? 'vt-marker-bounce' : style.hoverAnimation === 'scale' ? 'vt-marker-scale' : ''

  return `
    <div class="vt-hotspot-marker ${pulseClass} ${hoverAnim}" style="
      width:${size}px;height:${size}px;border-radius:50%;
      background:${color};border:${border};opacity:${opacity};
      display:flex;align-items:center;justify-content:center;
      cursor:pointer;${glowStyle}
      font-size:${Math.round(size * 0.45)}px;
      transition: transform 0.2s ease;
    " title="${hotspot.tooltip || label}">
      <span>${emoji}</span>
    </div>
    ${label && style.label !== '' ? `<div class="vt-hotspot-label" style="margin-top:4px;font-size:10px;color:white;text-shadow:0 1px 3px black;text-align:center;white-space:nowrap;">${label}</div>` : ''}
  `
}

export const HOTSPOT_MARKER_CSS = `
  ${SCENE_LINK_ARROW_CSS}
  .vt-hotspot-marker.vt-marker-pulse { animation: vt-pulse 2s ease-in-out infinite; }
  .vt-hotspot-marker.vt-marker-scale:hover { transform: scale(1.15); }
  .vt-hotspot-marker.vt-marker-bounce:hover { animation: vt-bounce 0.5s ease; }
  @keyframes vt-pulse { 0%,100%{transform:scale(1)} 50%{transform:scale(1.08)} }
  @keyframes vt-bounce { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-4px)} }
  .vt-marker-ripple { position:absolute;border-radius:50%;background:rgba(45,212,191,0.4);animation:vt-ripple 0.6s ease-out forwards;pointer-events:none; }
  @keyframes vt-ripple { from{transform:scale(0);opacity:1} to{transform:scale(2.5);opacity:0} }
`
