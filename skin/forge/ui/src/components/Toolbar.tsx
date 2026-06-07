import type { ToolbarItem } from '../types'

interface ToolbarProps {
  items: ToolbarItem[]
}

export function Toolbar({ items }: ToolbarProps) {
  if (items.length === 0) return null

  return (
    <div id="toolbar" className="forge-toolbar" role="navigation" aria-label="ツールバー">
      {items.map((item) => (
        <a
          key={item.key}
          href={item.href}
          className="forge-toolbar__item"
          title={item.label}
        >
          {item.icon ? (
            <img
              src={item.icon}
              width={item.width}
              height={item.height}
              alt={item.label}
            />
          ) : (
            <span>{item.label}</span>
          )}
        </a>
      ))}
    </div>
  )
}
