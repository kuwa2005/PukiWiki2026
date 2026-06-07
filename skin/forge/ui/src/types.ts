export interface NavItem {
  key: string
  label: string
  href: string
}

export interface ToolbarItem extends NavItem {
  icon: string
  width: number
  height: number
}

export interface ForgeFooter {
  modifier: string
  modifierLink: string
  copyright: string
  phpVersion: string
  convertTime: string
}

export interface ForgeInitial {
  title: string
  page: string
  pageTitle: string
  body: string
  menu: string
  rightbar: string
  notes: string
  attaches: string
  lastmodified: string
  related: string
  canonicalUrl: string
  topicpath: string
  showTopicpath: boolean
  showNavbar: boolean
  showToolbar: boolean
  isPage: boolean
  isRead: boolean
  readonly: boolean
  isFreeze: boolean
  functionFreeze: boolean
  doBackup: boolean
  fileUploads: boolean
  logo: string
  logoAlt: string
  topHref: string
  rssHref: string
  navPrimary: NavItem[]
  navPage: NavItem[]
  navGlobal: NavItem[]
  toolbar: ToolbarItem[]
  footer: ForgeFooter
}

declare global {
  interface Window {
    __FORGE_INITIAL__?: ForgeInitial
  }
}

export function readForgeInitial(): ForgeInitial {
  if (window.__FORGE_INITIAL__) {
    return window.__FORGE_INITIAL__
  }
  const el = document.getElementById('pukiwiki-forge-initial')
  if (el?.textContent) {
    return JSON.parse(el.textContent) as ForgeInitial
  }
  throw new Error('Forge initial data not found')
}
