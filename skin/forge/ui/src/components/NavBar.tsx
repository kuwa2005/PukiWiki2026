import type { NavItem } from '../types'

interface NavBarProps {
  primary: NavItem[]
  page: NavItem[]
  global: NavItem[]
}

function NavGroup({ items, label }: { items: NavItem[]; label: string }) {
  if (items.length === 0) return null
  return (
    <nav className="forge-nav__group" aria-label={label}>
      <ul className="forge-nav__list">
        {items.map((item) => (
          <li key={item.key}>
            <a href={item.href}>{item.label}</a>
          </li>
        ))}
      </ul>
    </nav>
  )
}

export function NavBar({ primary, page, global }: NavBarProps) {
  return (
    <div id="navigator" className="forge-nav">
      <NavGroup items={primary} label="主要ナビ" />
      <NavGroup items={page} label="ページ操作" />
      <NavGroup items={global} label="サイトナビ" />
    </div>
  )
}
