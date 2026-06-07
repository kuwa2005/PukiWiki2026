interface WikiBodyProps {
  html: string
}

export function WikiBody({ html }: WikiBodyProps) {
  return (
    <article
      id="body"
      className="forge-body wiki-body"
      dangerouslySetInnerHTML={{ __html: html }}
    />
  )
}
