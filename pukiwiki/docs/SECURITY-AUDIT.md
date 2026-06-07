# SECURITY-AUDIT — PukiWiki2026 セキュリティ監査

| 項目 | 内容 |
|------|------|
| 監査日 | 2026-06-07 |
| 対象 | PukiWiki 1.5.4 UTF-8（`pukiwiki2026` 作業ツリー） |
| 監査方法 | 静的コードレビュー（設定・コア・主要プラグイン・`.htaccess`） |
| 監査者 | Cursor Agent（自動監査） |

## 概要

PukiWiki 1.5.4 は 2022 年に公開された CVE（CVE-2022-36350 / CVE-2022-34486 / CVE-2022-27637）の修正済みバージョンであり、**当リポジトリのベースは公式 1.5.4 と一致**する。一方で、Wiki エンジンとしての設計上、CSRF 対策・現代的パスワードハッシュ・セッション Cookie 属性・ディレクトリ保護などに構造的な弱点が残る。

### リスク総評

| 深刻度 | 件数 |
|--------|------|
| Critical | 2 |
| High | 7 |
| Medium | 8 |
| Low | 5 |
| **合計** | **22** |

**総合所見:** デフォルト設定（`PKWK_READONLY=0`、添付は管理者のみ、`PKWK_UPDATE_EXEC` 無効）では即座の RCE には至りにくいが、**CSRF 未対策**と**弱いパスワードハッシュ**は本番運用前に必ず対処すべき。認証・リバースプロキシ・添付 MIME 制限を有効にする場合は設定ミスが High/Critical に直結する。

---

## 既知 CVE / 公開情報

| CVE / 通報 | 内容 | 影響バージョン | 1.5.4 での状態 |
|------------|------|----------------|----------------|
| [CVE-2022-36350](https://nvd.nist.gov/vuln/detail/CVE-2022-36350) | 保存型 XSS | 1.3.1〜1.5.3 | **修正済み**（ベース 1.5.4） |
| [CVE-2022-34486](https://nvd.nist.gov/vuln/detail/CVE-2022-34486) | パストラバーサル（rename 等） | 1.4.5〜1.5.3 | **修正済み** |
| [CVE-2022-27637](https://nvd.nist.gov/vuln/detail/CVE-2022-27637) | 反射型 XSS | 1.5.1〜1.5.3 | **修正済み** |
| [JVNVU#96002401](https://jvn.jp/en/vu/JVNVU96002401/index.html) | 上記複合 | 1.5.3 以前 | 1.5.4 へのアップグレード推奨 |

**1.5.4 以降の公式修正:** 2026-06-07 時点で公式最新は 1.5.4。本フォーク（PukiWiki2026）は未改造に近い 1.5.4 ベースのため、上記 CVE パッチは取り込み済みと判断。今後公式 1.5.5+ が出た場合は `docs/UPSTREAM.md` の diff 方針で追従すること。

---

## 発見事項一覧

| ID | 深刻度 | カテゴリ | 概要 | 該当ファイル / 機能 | 推奨対応 |
|----|--------|----------|------|---------------------|----------|
| SEC-C01 | Critical | コマンドインジェクション | `PKWK_UPDATE_EXEC` 設定時、ページ更新のたびに `system()` で任意シェルコマンド実行 | `lib/file.php`（`page_write`）、`pukiwiki.ini.php` | 本番では未設定を維持。使用する場合は固定スクリプトパスのみ許可し、`system()` を `proc_open` + 引数配列等に置換 |
| SEC-C02 | Critical | CSRF | 全 POST 操作（編集・添付・rename・管理者操作）に CSRF トークンなし。`digest` は衝突検出のみ | `lib/html.php`（`edit_form`）、各 `plugin/*` POST ハンドラ | セッションベース CSRF トークンを共通 middleware 化。SameSite=Strict Cookie と併用 |
| SEC-H01 | High | 認証 | 管理者・ユーザー既定ハッシュが MD5（`{x-php-md5}`）。レインボーテーブル・GPU 総当たりに脆弱 | `lib/auth.php`（`pkwk_hash_compute`）、`pukiwiki.ini.php`（`$adminpass`） | `password_hash()` / `password_verify()`（bcrypt/argon2id）へ移行。既存ハッシュの段階的 rehash |
| SEC-H02 | High | 認証 | フォームログイン（`loginform`）にブルートフォース対策なし。`pkwk_login()` の `sleep(2)` は管理者パスワード専用 | `plugin/loginform.inc.php`、`lib/auth.php` | ログイン失敗時の指数バックオフ、IP/アカウント単位レート制限、必要なら CAPTCHA / アカウントロック |
| SEC-H03 | High | 認証 | `AUTH_TYPE_EXTERNAL_X_FORWARDED_USER` 使用時、`HTTP_X_FORWARDED_USER` を無検証で信頼 | `lib/auth.php`（`ensure_valid_auth_user`） | 信頼プロキシ IP 限定、ヘッダー署名検証、または REMOTE_USER 等への限定 |
| SEC-H04 | High | 認証 | ログイン成功後 `url_after_login` パラメータで任意 URL へリダイレクト（フィッシング） | `plugin/loginform.inc.php`、`lib/auth.php`（`form_auth_redirect`） | 同一オリジン相対パスのみ許可。許可リスト方式に変更 |
| SEC-H05 | High | 情報漏洩 / 設定 | `wiki/`（`DATA_DIR`）に `.htaccess` 未同梱。Web サーバー設定次第で `.txt` ページソース直アクセス可能 | `wiki/`（未保護）、`backup/`/`cache/`/`attach/` は保護済み | **推奨:** `wiki/.htaccess` で `Require all denied`、nginx では `location deny`。`.htaccess` は任意だが、DocumentRoot 配下では保護を推奨（[DEPLOY.md §4.5](DEPLOY.md#45-htaccess任意推奨)） |
| SEC-H06 | High | ファイルアップロード | 添付 MIME 検証が拡張子ベース。`Content-Disposition: inline` 配信のため HTML/SVG 等アップロード時に XSS 化しうる（管理者のみでも被害拡大） | `plugin/attach.inc.php`（`attach_mime_content_type`、`open()`） | 許可拡張子ホワイトリスト、`Content-Disposition: attachment`、CSP `default-src 'self'` |
| SEC-H07 | High | セッション | セッション Cookie に `HttpOnly` / `Secure` / `SameSite` 未設定 | `lib/init.php`（session ini のみ） | `session_set_cookie_params()` で本番必須属性を設定 |
| SEC-M01 | Medium | 認証 | 平文パスワードスキーム `{cleartext}` およびサンプル `$auth_users` に平文例 | `lib/auth.php`、`pukiwiki.ini.php` | 平文スキームを廃止または警告。サンプルから平文例を削除 |
| SEC-M02 | Medium | 認証 | 添付パスワードを MD5 で保存・比較 | `plugin/attach.inc.php` | `password_hash` 相当へ。ソルト付き |
| SEC-M03 | Medium | XSS | Wiki 記法の `COLOR()` / `SIZE()` 等でインライン `style` 生成。`default.ini.php` の `$line_rules` | `default.ini.php`、`lib/convert_html.php` | CSP、許可 CSS プロパティ制限、サニタイズ強化 |
| SEC-M04 | Medium | 情報漏洩 | `#version` プラグインでバージョン文字列公開（`PKWK_SAFE_MODE` 時のみ抑制） | `plugin/version.inc.php`、`lib/init.php`（`S_VERSION`） | 本番で `PKWK_SAFE_MODE=1` またはプラグイン無効化 |
| SEC-M05 | Medium | 情報漏洩 | 添付 info 画面にサーバー上のフルパス表示 | `plugin/attach.inc.php`（`info()`） | 表示から物理パスを除去 |
| SEC-M06 | Medium | 設定 | セキュリティ HTTP ヘッダー（HSTS, X-Content-Type-Options, CSP）がコメントアウト既定 | `pukiwiki.ini.php`（`$http_response_custom_headers`） | 本番で有効化。CSP は段階的導入 |
| SEC-M07 | Medium | SSRF | `PLUGIN_REF_URL_GET_IMAGE_SIZE=TRUE` かつ `allow_url_fopen` 有効時、外部 URL へ `getimagesize()` | `plugin/ref.inc.php` | 既定 OFF 維持。有効化禁止または URL スキーム/ホスト制限 |
| SEC-M08 | Medium | PHP 互換 | `set_file_buffer()`（PHP 8 削除）、`mb_ereg()`（非推奨）使用 | `lib/file.php`、`lib/func.php` | PHP 8.x CI 追加。非推奨 API 置換 |
| SEC-L01 | Low | 設定 | `index.php` で `error_reporting(E_ERROR \| E_PARSE)` — 一部環境で情報漏洩余地 | `index.php` | 本番は `0` またはログファイル出力のみ |
| SEC-L02 | Low | プライバシー | `PKWK_DISABLE_INLINE_IMAGE_FROM_URI=0` 既定 — 外部 URI インライン画像で Web ビーコン可能 | `pukiwiki.ini.php`、`lib/make_link.php` | 必要なら `1` に設定 |
| SEC-L03 | Low | 設定 | （2026-06 時点）ルート・`pukiwiki/.htaccess` で `^\.ht` 拒否は有効 | `.htaccess` | Apache 利用時は `AllowOverride` を有効化。`.htaccess` 自体は任意（[DEPLOY.md §4.5](DEPLOY.md#45-htaccess任意推奨)） |
| SEC-L04 | Low | 監査 | `pcomment` / `comment` 等、ゲスト投稿プラグインはスパム・荒らし対象 | `plugin/pcomment.inc.php` 等 | CAPTCHA、レート制限、凍結運用 |
| SEC-L05 | Low | 依存 | PHP 8.1+ 公式確認済みだが 8.2/8.3/8.4 での回帰未検証 | 全体 | CI で複数 PHP バージョンの smoke test |

---

## 重点調査サマリ

### 認証・セッション

- パスワード: MD5 既定。bcrypt/argon2 非対応。平文スキーム存在。
- セッション: `session.use_strict_mode` 等は設定済みだが Cookie 属性不足。
- CSRF: **実装なし**（`csrf` / `token` / `nonce` 文字列はコードベースに存在しない）。
- ブルートフォース: 管理者パスワードのみ `sleep(2)`。

### 入力・出力

- XSS: 1.5.4 公式 CVE 修正済み。記法・プラグイン経由のインライン CSS、添付 inline 配信に残余リスク。
- SQLi: `counter` プラグイン DB モードは PDO プリペアド（`?`）使用 — 低リスク（DB 利用時のみ）。
- パストラバーサル: `encode()` + `is_pagename()` + attach の basename 正規化で緩和。rename は encoded 名ベース。
- コマンド実行: `PKWK_UPDATE_EXEC`、`popen(chasen/kakasi)`（ページ読み機能、通常 OFF）。

### ファイル・アップロード

- 添付: 管理者のみアップロード（既定 `PLUGIN_ATTACH_UPLOAD_ADMIN_ONLY=TRUE`）。拡張子/MIME ホワイトリストなし。
- `attach/.htaccess`: `Require all denied` — 直接 HTTP アクセスは拒否（良好）。

### 設定・デプロイ

- `$adminpass = '{x-php-md5}!'` — デフォルトは常時ログイン失敗（良好だが弱い方式）。
- `PKWK_READONLY=0`、`PKWK_SAFE_MODE=0` 既定。
- `wiki/` 未保護が最大のデプロイ依存リスク。

---

## 優先度付きロードマップ

### 短期（1〜2 スプリント）

1. **SEC-C02** CSRF トークン基盤の追加（編集・管理者 POST から）
2. **SEC-H01** パスワードハッシュを `password_hash` へ移行
3. **SEC-H05** `wiki/.htaccess` 追加（推奨）と DEPLOY 手順整備 — `.htaccess` は Wiki 動作に必須ではない
4. **SEC-H07** セッション Cookie 属性の本番設定
5. **SEC-H02** ログインレート制限
6. **SEC-H06** 添付 MIME/拡張子ホワイトリスト + `attachment` 配信

### 中期（1〜3 ヶ月）

1. **SEC-H03/H04** 外部認証・リダイレクトの hardening
2. **SEC-M06** CSP / HSTS / X-Content-Type-Options 本番適用
3. **SEC-M08** PHP 8.2+ 互換テストと非推奨 API 除去
4. **SEC-C01** `PKWK_UPDATE_EXEC` の安全な代替設計（または廃止）
5. 動的テスト（OWASP ZAP、認証フロー、CSRF PoC）の CI 組み込み

---

## GitHub Issues 対応表

Critical / High は GitHub Issue を起票済み（ラベル `security` + 深刻度ラベル）。

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

---

## 参考リンク

- [PukiWiki 公式 Errata（日本語）](https://pukiwiki.osdn.jp/?PukiWiki/Errata)
- [JVN#43979089 — CVE-2022-36350](https://jvn.jp/jp/JVN43979089/)
- [JVNVU#96002401 — CVE-2022-34486 / CVE-2022-27637](https://jvn.jp/en/vu/JVNVU96002401/index.html)
- [OWASP CSRF Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html)
- [OWASP Password Storage Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Password_Storage_Cheat_Sheet.html)
- 本リポジトリ: [docs/DEPLOY.md](./DEPLOY.md)、[docs/UPSTREAM.md](./UPSTREAM.md)

---

## 監査の限界

- **静的解析のみ** — 実行時ペネトレーション、ファジング、本番 Web サーバー設定の実地確認は未実施。
- **プラグイン全件** — 80+ プラグインの網羅的監査は未実施（attach, edit, rename, loginform, ref, dump, counter 等を重点調査）。
- **カスタム改造** — PukiWiki2026 固有の大規模改造が今後入った場合は再監査が必要。
- **依存関係** — Composer/npm 等の外部依存は本プロジェクトでは未使用。
