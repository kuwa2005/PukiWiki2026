import type { ForgeFooter } from '../types'

interface FooterProps {
  footer: ForgeFooter
}

export function Footer({ footer }: FooterProps) {
  return (
    <footer id="footer" className="forge-footer">
      <p className="forge-footer__admin">
        Site admin:{' '}
        <a href={footer.modifierLink}>{footer.modifier}</a>
      </p>
      <p className="forge-footer__meta">
        {footer.copyright}. Powered by PHP {footer.phpVersion}. HTML convert time:{' '}
        {footer.convertTime} sec.
      </p>
    </footer>
  )
}
