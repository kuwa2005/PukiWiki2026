interface MetaSectionProps {
  lastmodified: string
  related: string
}

export function MetaSection({ lastmodified, related }: MetaSectionProps) {
  if (!lastmodified && !related) return null

  return (
    <section className="forge-meta">
      {lastmodified && (
        <div
          id="lastmodified"
          className="forge-meta__item"
          dangerouslySetInnerHTML={{ __html: `Last-modified: ${lastmodified}` }}
        />
      )}
      {related && (
        <div
          id="related"
          className="forge-meta__item wiki-body"
          dangerouslySetInnerHTML={{ __html: `Link: ${related}` }}
        />
      )}
    </section>
  )
}
