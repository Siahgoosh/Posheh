import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import { BrowserRouter } from 'react-router-dom'
import './index.css'
import { PanelApp } from './panel/PanelApp'

/** Entry فقط برای panel.posheapp.ir — بدون لندینگ و بدون App اصلی */
createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <BrowserRouter>
      <PanelApp />
    </BrowserRouter>
  </StrictMode>,
)
