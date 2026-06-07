interface SidebarProps {
  html: string
  id: string
  label: string
}

export function Sidebar({ html, id, label }: SidebarProps) {
  return (
    <div
      id={id}
      className="forge-sidebar wiki-body"
      aria-label={label}
      dangerouslySetInnerHTML={{ __html: html }}
    />
  )
}
