import type { ForgeInitial } from './types'
import { Header } from './components/Header'
import { NavBar } from './components/NavBar'
import { WikiBody } from './components/WikiBody'
import { Sidebar } from './components/Sidebar'
import { Toolbar } from './components/Toolbar'
import { Footer } from './components/Footer'
import { MetaSection } from './components/MetaSection'

interface AppProps {
  data: ForgeInitial
}

export default function App({ data }: AppProps) {
  const hasSidebar = Boolean(data.menu || data.rightbar)

  return (
    <div className="forge-app">
      <a href="#forge-main" className="forge-skip-link">
        本文へスキップ
      </a>

      <Header data={data} />

      {data.showNavbar && (
        <NavBar
          primary={data.navPrimary}
          page={data.navPage}
          global={data.navGlobal}
        />
      )}

      <main id="forge-main" className="forge-main">
        <div className={`forge-content${hasSidebar ? ' forge-content--with-sidebar' : ''}`}>
          <WikiBody html={data.body} />
          {hasSidebar && (
            <aside className="forge-aside" aria-label="サイドバー">
              {data.menu && <Sidebar html={data.menu} id="menubar" label="Menu" />}
              {data.rightbar && (
                <Sidebar html={data.rightbar} id="rightbar" label="RightBar" />
              )}
            </aside>
          )}
        </div>

        {data.notes && (
          <section className="forge-notes wiki-body" id="note" dangerouslySetInnerHTML={{ __html: data.notes }} />
        )}

        {data.attaches && (
          <section className="forge-attaches wiki-body" id="attach" dangerouslySetInnerHTML={{ __html: data.attaches }} />
        )}
      </main>

      {data.showToolbar && <Toolbar items={data.toolbar} />}

      <MetaSection lastmodified={data.lastmodified} related={data.related} />

      <Footer footer={data.footer} />
    </div>
  )
}
