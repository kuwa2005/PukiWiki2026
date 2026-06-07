import type { ForgeInitial } from '../types'

interface HeaderProps {
  data: ForgeInitial
}

export function Header({ data }: HeaderProps) {
  return (
    <header className="forge-header">
      <div className="forge-header__brand">
        <a href={data.topHref} className="forge-header__logo-link">
          <img
            id="logo"
            className="forge-header__logo"
            src={data.logo}
            width={56}
            height={56}
            alt={data.logoAlt}
          />
        </a>
        <div className="forge-header__titles">
          <h1 className="forge-header__title title">
            <a href={data.canonicalUrl}>{data.page}</a>
          </h1>
          {data.isPage && data.showTopicpath && data.topicpath && (
            <div className="forge-header__topicpath small" dangerouslySetInnerHTML={{ __html: data.topicpath }} />
          )}
          {data.isPage && !data.showTopicpath && (
            <a href={data.canonicalUrl} className="forge-header__canonical small">
              {data.canonicalUrl}
            </a>
          )}
        </div>
      </div>
      <a href={data.rssHref} className="forge-header__rss" title="RSS">
        RSS
      </a>
    </header>
  )
}
