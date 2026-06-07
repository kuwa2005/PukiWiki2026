# SECURITY-AUDIT — PukiWiki2026 セキュリティ監査

| 項目 | 内容 |
|------|------|
| 初回監査 | 2026-06-07 |
| **再監査** | **2026-06-07**（Unicode / 国際化攻撃・CSRF 適用漏れ・修正状況の再確認） |
| 対象 | PukiWiki 1.5.4 UTF-8 + PukiWiki2026 改造（`pukiwiki2026` 作業ツリー） |
| 監査方法 | 静的コードレビュー（設定・コア・主要プラグイン・`.htaccess`） |
| 監査者 | Cursor Agent（自動監査） |

## 概要

PukiWiki 1.5.4 は 2022 年公開 CVE（CVE-2022-36350 / CVE-2022-34486 / CVE-2022-27637）修正済みベース。**PukiWiki2026 v1.0.0 以降**では CSRF・セッション Cookie・ログインレート制限・`password_hash` 対応・`wiki/.htaccess` 等、初回監査の Critical/High の多くが **実装済み**（[ISSUES.md](./ISSUES.md) 参照）。

再監査で **Unicode / 双方向テキスト / ホモグリフ** に関する未対策が新たに判明。ページ名・ユーザー名・添付ファイル名の検証に NFKC 正規化・双方向制御文字拒否がなく、フィッシング・なりすましに悪用されうる。

### リスク総評（再監査時点）

| 深刻度 | 初回 | 修正済 | **未修正（Open）** |
|--------|------|--------|---------------------|
| Critical | 2 | 2 | **0** |
| High | 7 | 7 | **1**（SEC-U01） |
| Medium | 8 | 8 | **5**（SEC-M06 + SEC-U02〜U04 + SEC-M10 部分） |
| Low | 5 | 5 | **2**（SEC-L01 運用 + SEC-U05） |
| **Open 合計** | 22 | — | **8** |

**総合所見:** 構造的 CSRF・認証・セッションの弱点は v1.0.0 で大幅に改善済み。残る最大リスクは **Unicode 表示なりすまし（SEC-U01）** と、本番設定依存の **HTTP セキュリティヘッダー（SEC-M06）**。Arabic RTL override（U+202E）等の具体例は [Unicode 攻撃テストケース](#unicode--国際化攻撃テストケース) を参照。

---

## 既知 CVE / 公開情報

| CVE / 通報 | 内容 | 影響バージョン | 1.5.4 / PukiWiki2026 |
|------------|------|----------------|----------------------|
| [CVE-2022-36350](https://nvd.nist.gov/vuln/detail/CVE-2022-36350) | 保存型 XSS | 1.3.1〜1.5.3 | **修正済み** |
| [CVE-2022-34486](https://nvd.nist.gov/vuln/detail/CVE-2022-34486) | パストラバーサル | 1.4.5〜1.5.3 | **修正済み** |
| [CVE-2022-27637](https://nvd.nist.gov/vuln/detail/CVE-2022-27637) | 反射型 XSS | 1.5.1〜1.5.3 | **修正済み** |

---

## 発見事項一覧

**状態:** Fixed = v1.0.0 系で対応済 / Open = 未対応または部分対応

| ID | 深刻度 | 状態 | カテゴリ | 概要 | 該当 | 推奨対応 |
|----|--------|------|----------|------|------|----------|
| SEC-C01 | Critical | **Fixed** | コマンド実行 | `PKWK_UPDATE_EXEC` — `proc_open` + ホワイトリスト | `lib/security.php`, `lib/file.php` | 本番未設定維持。`PKWK_UPDATE_EXEC_ALLOWED` 必須 |
| SEC-C02 | Critical | **Fixed** | CSRF | セッション CSRF トークン + SameSite Cookie + Origin/Referer 副次防御 | `lib/csrf.php`, `lib/pukiwiki.php` | 全 POST フォームへトークン注入（`pkwk_csrf_inject_forms`） |
| SEC-H01 | High | **Fixed** | 認証 | `password_hash` / `pkwk_hash_verify` 対応。MD5 レガシー互換残存 | `lib/security.php`, `lib/auth.php` | 新規ハッシュは `{x-php-password}` 推奨 |
| SEC-H02 | High | **Fixed** | 認証 | IP 単位ログインレート制限（指数バックオフ） | `lib/security.php`, `lib/auth.php` | — |
| SEC-H03 | High | **Fixed** | 認証 | `X_FORWARDED_USER` — 信頼プロキシ限定 | `lib/auth.php`, `lib/security.php` | `$auth_trusted_proxies` を本番で最小化 |
| SEC-H04 | High | **Fixed** | 認証 | ログイン後リダイレクト — 同一オリジンのみ | `lib/security.php`, `plugin/loginform.inc.php` | — |
| SEC-H05 | High | **Fixed** | 情報漏洩 | `wiki/.htaccess` でページソース直アクセス拒否 | `pukiwiki/wiki/.htaccess` | nginx 等は `location` deny |
| SEC-H06 | High | **Fixed** | アップロード | 危険 MIME / 拡張子は `Content-Disposition: attachment` | `lib/security.php`, `plugin/attach.inc.php` | 拡張子ホワイトリスト追加を検討 |
| SEC-H07 | High | **Fixed** | セッション | Cookie `HttpOnly` / `Secure` / `SameSite=Strict` | `lib/csrf.php` | — |
| **SEC-U01** | **High** | **Open** | **Unicode** | **ページ名に RTL override（U+202E）等の双方向制御文字を拒否しない — 表示なりすまし** | `lib/func.php`（`is_pagename`）, `lib/init.php`（`$BracketName`） | `pkwk_sanitize_identifier()` で BiDi 制御・ゼロ幅文字拒否。表示前に `U+202A–202E` / `U+2066–2069` 等をブロック |
| SEC-M01 | Medium | **Fixed** | 認証 | 平文スキーム非推奨化（error_log） | `lib/auth.php` | 平文スキーム廃止 |
| SEC-M02 | Medium | **Fixed** | 認証 | 添付パスワード `password_hash` 対応 | `lib/security.php`, `plugin/attach.inc.php` | — |
| SEC-M03 | Medium | **Fixed** | XSS | インライン `style` 許可リスト化 | `lib/security.php`, `lib/convert_html.php` | CSP 併用 |
| SEC-M04 | Medium | **Fixed** | 情報漏洩 | `#version` — `PKWK_SAFE_MODE` 時抑制 | `plugin/version.inc.php` | 本番 `PKWK_SAFE_MODE=1` |
| SEC-M05 | Medium | **Fixed** | 情報漏洩 | 添付 info の物理パス非表示 | `plugin/attach.inc.php` | — |
| SEC-M06 | Medium | **Open** | 設定 | HSTS / CSP / X-Content-Type-Options がコメントアウト既定 | `pukiwiki.ini.php.example` | 本番で段階的有効化 |
| SEC-M07 | Medium | **Fixed** | SSRF | ref 外部 URL — スキーム/プライベート IP 制限 | `lib/security.php`, `plugin/ref.inc.php` | 既定 OFF 維持 |
| SEC-M08 | Medium | **Fixed** | PHP 互換 | `set_file_buffer` → `stream_set_write_buffer` | `lib/security.php`, `lib/file.php` | PHP 8.2+ CI 継続 |
| **SEC-M09** | Medium | **Fixed** | バグ | `is_pagename_bytes_within_hard_limit()` が SOFT_LIMIT を参照 | `lib/func.php` | **再監査で修正**（HARD_LIMIT 使用） |
| **SEC-M10** | Medium | **Partial** | CSRF | rename/freeze/comment 等の POST フォームに CSRF トークン未同梱（Origin/Referer フォールバック依存） | `plugin/*.inc.php`, `lib/plugin.php` | **`pkwk_csrf_inject_forms()` で自動注入（再監査で追加）**。フォールバック削除は将来検討 |
| **SEC-U02** | Medium | **Open** | **Unicode** | NFKC 正規化なし — ラテン/キリル/アラビア等ホモグリフで別ページ・別ユーザー名 | `lib/func.php`, `lib/auth.php` | 識別子保存前に NFKC + 混合スクリプト警告/拒否 |
| **SEC-U03** | Medium | **Open** | **Unicode** | ログインユーザー名・表示名に BiDi/制御文字検証なし | `plugin/loginform.inc.php`, `lib/auth.php` | SEC-U01 と共通サニタイザ |
| **SEC-U04** | Medium | **Open** | **Unicode** | 添付ファイル名 — パス除去のみ。RTL/ゼロ幅で表示欺瞞 | `plugin/attach.inc.php`（`AttachFile`） | アップロード時ファイル名検証 + 表示エスケープ強化 |
| SEC-L01 | Low | **Open** | 設定 | `error_reporting(E_ERROR \| E_PARSE)` — 本番情報漏洩余地 | `index.php` | 本番 `0` またはログのみ |
| SEC-L02 | Low | **Fixed** | プライバシー | 外部 URI インライン画像（Web ビーコン） | 設定 | 必要なら `PKWK_DISABLE_INLINE_IMAGE_FROM_URI=1` |
| SEC-L03 | Low | **Fixed** | 設定 | `.htaccess` dot-ht 拒否 | `.htaccess` | Apache `AllowOverride` |
| SEC-L04 | Low | **Fixed** | 監査 | ゲスト投稿プラグイン — 編集認証必須化で緩和 | 全体 | CAPTCHA 任意 |
| SEC-L05 | Low | **Fixed** | 依存 | PHP 8.2+ CI smoke | `.github/workflows` | 8.3/8.4 追加検討 |
| **SEC-U05** | Low | **Open** | **エスケープ** | `htmlsc()` 既定 `ENT_COMPAT` — 単一引用符属性コンテキストで理論上 XSS 余地 | `lib/func.php` | 既定を `ENT_QUOTES` に（回帰確認後） |

---

## Unicode / 国際化攻撃テストケース

手動または E2E で確認する具体例（UTF-8 エンコーディング前提）。

### 1. RTL override ページ名なりすまし（SEC-U01 — High）

| 項目 | 内容 |
|------|------|
| 入力 | ページ名 `FrontPag\u202EgnipaeP`（U+202E RIGHT-TO-LEFT OVERRIDE を `FrontPage` 直前に挿入） |
| 期待表示（攻撃成功時） | ブラウザが BiDi アルゴリズムで **「FrontPage」に見える別 URL** |
| 現状 | `is_pagename()` は有効 UTF-8 なら **受理**（`lib/func.php` `$BracketName` は `\r\n\t\f\[\]<>#&":` のみ除外） |
| 確認手順 | 1) 上記ページ名で新規作成 2) RecentChanges / 検索結果 / `<title>` で表示 3) URL と見た目の不一致を確認 |
| 推奨修正 | 識別子から `[\x{202A}-\x{202E}\x{2066}-\x{2069}\x{200B}-\x{200F}\x{FEFF}]` 等を拒否 |

### 2. アラビア文字ホモグリフ（SEC-U02 — Medium）

| 項目 | 内容 |
|------|------|
| 入力 | `admin`（ラテン） vs `\u0430dmin`（先頭 U+0430 キリル **а**） |
| 攻撃シナリオ | `$edit_auth_pages` の `/^admin/` 等を **別ページで迂回**、リンク先フィッシング |
| 現状 | NFKC 未適用のため **別ファイル**（`encode()` はバイト列そのまま hex 化） |
| 確認 | 両ページを作成し一覧で視覚的区別が付くか |

### 3. ゼロ幅文字混入（SEC-U02 — Medium）

| 項目 | 内容 |
|------|------|
| 入力 | `Front\u200BPage`（U+200B ZERO WIDTH SPACE） |
| 攻撃シナリオ | 見た目同一・URL 不同による **リンク混同**、検索キーワードバイパス |
| 現状 | 受理される |

### 4. 添付ファイル名 RTL（SEC-U04 — Medium）

| 項目 | 内容 |
|------|------|
| 入力 | ファイル名 `safe.pdf\u202Eexe.` |
| 攻撃シナリオ | 添付一覧で拡張子表示が **逆転**しユーザーが危険ファイルと認識しない |
| 現状 | `AttachFile` は `preg_replace('#^.*/#','',$file)` のみ（`plugin/attach.inc.php`） |
| 表示 | `htmlsc($this->file)` はエスケープするが **BiDi 制御は除去しない** |

### 5. 検索語の Arabic / 混合スクリプト（Low — 現状安全）

| 項目 | 内容 |
|------|------|
| 入力 | 検索語 `<script>` や `\u202Etest` |
| 現状 | 結果タイトル・一覧は `htmlsc()` 済み（`plugin/search.inc.php`, `do_search()`） — **反射 XSS は低リスク** |
| 残余 | BiDi による **結果一覧の表示欺瞞** は SEC-U01 と同根 |

### 6. IDN / ホスト名（参考 — 本 Wiki 外）

PukiWiki 本体は IDN を処理しない。`pkwk_safe_redirect_url()` / CSRF Origin チェックは **Punycode 後の `HTTP_HOST` 文字列比較** — サブドメイン攻撃は `$auth_trusted_proxies` 設定に依存。

---

## 重点調査サマリ（再監査）

### 認証・セッション

- パスワード: `password_hash` 対応済。ini 既定は `{x-php-sha256}` / デモ平文 — 本番変更必須。
- セッション: `SameSite=Strict` + `HttpOnly` + `Secure`（HTTPS 時）。
- CSRF: `pkwk_csrf_verify_or_die()` が全 POST を検証。トークン欠落時は Origin/Referer フォールバック（**SEC-M10**）。
- ブルートフォース: IP 単位レート制限 + 管理者 `sleep(2)`。

### 入力・出力

- XSS: 1.5.4 CVE 修正 + style サニタイズ。添付 inline は危険型のみ attachment。
- SQLi: counter DB モードのみ PDO プリペアド — 低リスク。
- パストラバーサル: `encode()` + `is_pagename()` + attach basename 正規化。
- **Unicode: 制御文字・BiDi・NFKC 未検証（新規 SEC-U01〜U05）。**

### ファイル・アップロード

- 添付: 管理者のみ（既定）。拡張子ホワイトリストなし。
- `wiki/.htaccess`: `Require all denied` **同梱済み**。

---

## 優先度付きロードマップ（更新）

### 短期

1. **SEC-U01** ページ名 / ユーザー名 / 添付名の Unicode 識別子サニタイズ
2. **SEC-M06** 本番 HTTP セキュリティヘッダー有効化
3. **SEC-M10** CSRF Origin フォールバックの段階的廃止（トークン必須化）
4. **SEC-U05** `htmlsc()` 既定 `ENT_QUOTES` 化

### 中期

1. **SEC-U02** NFKC + 混合スクリプト検出
2. OWASP ZAP / Unicode フィッシング PoC の CI 組み込み
3. プラグイン全件の網羅監査（80+）

---

## GitHub Issues 対応表

### 初回監査 Critical / High（すべて closed）

| ID | Issue |
|----|-------|
| SEC-C01 | https://github.com/kuwa2005/PukiWiki2026/issues/1 |
| SEC-C02 | https://github.com/kuwa2005/PukiWiki2026/issues/2 |
| SEC-H01 | https://github.com/kuwa2005/PukiWiki2026/issues/4 |
| SEC-H02 | https://github.com/kuwa2005/PukiWiki2026/issues/6 |
| SEC-H03 | https://github.com/kuwa2005/PukiWiki2026/issues/8 |
| SEC-H04 | https://github.com/kuwa2005/PukiWiki2026/issues/3 |
| SEC-H05 | https://github.com/kuwa2005/PukiWiki2026/issues/5 |
| SEC-H06 | https://github.com/kuwa2005/PukiWiki2026/issues/7 |
| SEC-H07 | https://github.com/kuwa2005/PukiWiki2026/issues/9 |

### 再監査で新規起票（Open）

| ID | Issue | 深刻度 |
|----|-------|--------|
| SEC-U01 | https://github.com/kuwa2005/PukiWiki2026/issues/81 | High |
| SEC-U02 | （Medium — 必要に応じて起票） | Medium |

---

## 参考リンク

- [PukiWiki 公式 Errata](https://pukiwiki.osdn.jp/?PukiWiki/Errata)
- [Unicode TR39 — Security Mechanisms](https://unicode.org/reports/tr39/)
- [OWASP CSRF Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html)
- 本リポジトリ: [DEPLOY.md](./DEPLOY.md)、[ISSUES.md](./ISSUES.md)

---

## 監査の限界

- **静的解析のみ** — Unicode 表示はブラウザ依存。Chrome/Firefox/Safari での BiDi 実地確認推奨。
- **プラグイン全件** — attach, edit, rename, loginform, search, comment, freeze 等を重点調査。全 80+ プラグインは未網羅。
- **カスタム改造** — 今後の大規模改造時は再監査必須。
