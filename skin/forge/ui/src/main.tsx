import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import App from './App'
import { readForgeInitial } from './types'
import './styles/forge.css'
import './styles/wiki.css'

const rootEl = document.getElementById('pukiwiki-forge-root')
if (!rootEl) {
  throw new Error('#pukiwiki-forge-root not found')
}

const initial = readForgeInitial()
window.__FORGE_INITIAL__ = initial

createRoot(rootEl).render(
  <StrictMode>
    <App data={initial} />
  </StrictMode>,
)
