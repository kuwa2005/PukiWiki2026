# Changelog

本リポジトリ（pukiwiki2026）における改造履歴。  
公式 PukiWiki のリリースノートではありません。上流は [PukiWiki 1.5.4 UTF-8](https://pukiwiki.osdn.jp/) です。

形式は [Keep a Changelog](https://keepachangelog.com/ja/1.1.0/) に近い簡易版とします。

## [Unreleased]

### Added

- **起動時ディレクトリパーミッションチェック** — `lib/perm.php` を追加。Unix/Linux 本番で書き込みディレクトリ（`wiki/`, `diff/`, `backup/`, `cache/`, `attach/`, `counter/`）自身の mode のみ確認し、不適切な場合のみ chmod と配下の再帰修正。Windows では自動スキップ。`$perm_check_on_boot` 等は `pukiwiki.ini.php` で設定可能（`docs/DEPLOY.md` §3.2）

### Changed

- **フッタの PukiWiki Development Team リンク** — `S_COPYRIGHT`（`lib/init.php`）の href を `https://pukiwiki.sourceforge.io/` に更新
- **`.htaccess` の位置付け** — 任意・推奨であることを `pukiwiki/docs/DEPLOY.md` §4.5、`ARCHITECTURE.md`、`README.md`、`SECURITY-AUDIT.md` に明記。ルート / `pukiwiki/.htaccess` の役割分担を整理
- **`README.md` / `CHANGELOG.md` を root へ戻す** — git / プロジェクト文書はリポジトリ root。`docs/`・`tools/` は `pukiwiki/` 内のまま
- **upstream diff を git タグ基準に** — `vendor/` ローカルコピー不要。`upstream-1.5.4-utf8` と `pukiwiki/docs/UPSTREAM.md` を参照
- **ディレクトリ構成を `pukiwiki/` へ集約（案 B 改）** — Wiki 運用に必要なファイルを `pukiwiki/` 配下へ。デプロイ / バックアップ単位は `index.php` + `pukiwiki/`（`docs/`・`tools/` 含む）。`.github/` のみ root
- **パス参照** — `.gitignore`, CI (`php.yml`), `.htaccess`, `AGENTS.md`, docs 相互リンク, `plugin/saml.inc.php`（`DATA_HOME` 基準）等を更新
- **スキン構成を v1.0.0 に復元** — PR #37（classic/forge サブディレクトリ化）・PR #39（React forge）・PR #41（React revert）を巻き戻し。`skin/pukiwiki.skin.php` 等を `skin/` 直下に戻し、`default.ini.php` の SKIN 解決ロジックを簡素化
- **`pukiwiki.ini.php.example`** — `$skin` 設定を削除（サブディレクトリ方式廃止）

### Removed

- **`vendor/`** — 公式 pristine コピーのローカル置き場（git タグ `upstream-1.5.4-utf8` で代替）
- **`patches/`** — 未使用のパッチ保管プレースホルダ
- **`skin/classic/`**, **`skin/forge/`** — サブディレクトリ方式のスキン
- **`docs/DESIGN.md`** — スキン分離設計ドキュメント

---

## [1.0.0] - 2026-06-07

**PukiWiki2026 v1.0.0** — PukiWiki 1.5.4 UTF-8 ベースのセキュリティ強化 fork 初回リリース。

### 主要機能

- **セキュリティ強化** — 編集認証必須、CSRF トークン、静的監査対応（SEC-M03/L01/L05 等）
- **スパム対策** — Akismet 連携、編集時 CAPTCHA、外部リンク POST 制限（いずれも既定 OFF で任意有効化）
- **oEmbed** — YouTube / Vimeo / Twitter(X) / Flickr 等の URL 埋め込み（SSRF・HTML サニタイズ付き）
- **初回セットアップ** — デモ用 `editor` / `pass` 初期値、`tools/gen-password-hash.php`、[`docs/SETUP.md`](docs/SETUP.md)

### Added

- **`tools/gen-password-hash.php`** — Web フォームで `{x-php-sha256}` / `{x-php-password}` ハッシュを生成（開発・初回セットアップ用）
- **`tools/README.md`**, **`tools/.htaccess`** — 支援ツールのセキュリティ注意・IP 制限例
- **`docs/SETUP.md`** — 初回ログイン（`editor` / `pass`）、パスワード変更フロー、支援ツール案内
- **`pukiwiki.ini.php.example`** — デモ用初期ユーザー `editor`（SHA-256 ハッシュ、`pass` 相当）を既定値として記載

### Changed

- **`README.md`** — PukiWiki2026 セキュリティ強化 fork のコンセプト、初回ログイン・パスワード変更必須の注意を追記
- **`docs/ANTI-SPAM.md`** — 初回セットアップ・`tools/gen-password-hash.php` 案内、CSRF 対応済み表記に更新
- **`docs/DEPLOY.md`** — 初回セットアップ手順（ログイン・パスワード変更）を追記

### Security

- 初期 `editor` / `pass` はデモ用であることを ini 雛形・ドキュメントで明示。本番公開前の変更を必須化
- パスワード生成支援スクリプトに XSS 対策（`htmlsc`）と本番削除/IP 制限の警告を記載

### Added

- **CAPTCHA（SPAM-02）:** `lib/captcha.php` — 編集フォーム向け reCAPTCHA v2/v3 または honeypot（既定 OFF）
- **外部リンク制限（SPAM-04）:** `lib/spamfilter.php` — 書き込み POST 本文の外部 URL 拒否（既定 OFF）
- `pukiwiki.ini.php.example` — CAPTCHA・外部リンク設定雛形
- `docs/ANTI-SPAM.md` — CAPTCHA・外部リンク節（設定・テスト手順）

### Changed

- `lib/html.php` — `edit_form()` に CAPTCHA マークアップを追加
- `lib/file.php` — `page_write()` に外部リンク検証を追加
- `plugin/edit.inc.php` — 編集保存前に CAPTCHA 検証を追加
- `lib/init.php` — CAPTCHA・外部リンク設定の既定値
- `lib/pukiwiki.php` — `captcha.php` / `spamfilter.php` を読み込み

### Security

- 編集保存に CAPTCHA 第2防御を追加（任意有効化、reCAPTCHA 未設定時は無影響）
- 書き込み POST 本文の外部リンクを設定で拒否可能に

### Added

- `.github/workflows/php.yml` — PHP 8.1〜8.4 の構文チェック CI（SEC-L05）
- `lib/security.php` — インライン `style` サニタイズ（`pkwk_sanitize_style_attribute` 等、SEC-M03）

### Changed

- `index.php` — 本番既定で `error_reporting(0)`、`PKWK_DEBUG` 定数でデバッグ切替（SEC-L01）
- `lib/html.php` — `make_line_rules()` 出力に style 属性サニタイズを適用（SEC-M03）
- `lib/convert_html.php` — 表セルの COLOR/SIZE/width を許可値のみに制限（SEC-M03）
- `docs/DEPLOY.md` — 本番 `error_reporting` / `PKWK_DEBUG` 手順を追記（SEC-L01）

### Security

- SEC-M03: Wiki 記法インライン style の許可プロパティ制限と危険パターン除去
- SEC-L01: 本番向け error_reporting 既定を無効化
- SEC-L05: PHP 8.2+ 回帰の CI smoke test（構文チェック）

### Added

- **Akismet:** `lib/akismet.php` — 書き込み POST の Akismet `comment-check` 連携（既定 OFF）
- `pukiwiki.ini.php.example` — Akismet 設定雛形
- `docs/ANTI-SPAM.md` — Akismet 節（設定・API key・プライバシー・テスト手順）

### Changed

- `lib/file.php` — `page_write()` 保存直前に Akismet 判定を追加
- `lib/init.php` — Akismet 設定の既定値
- `lib/pukiwiki.php` — `akismet.php` を読み込み

### Security

- 書き込み POST に Akismet 外部判定を追加（任意有効化、既定 OFF）

### Added

- プロジェクト骨格: `README.md`, `CHANGELOG.md`, `docs/`, `vendor/`, `patches/`, `.gitignore`
- 作業フォルダ `D:\00_project\pukiwiki2026` として非公式フォーク用ドキュメントを整備
- **oEmbed:** `lib/oembed.php`, `plugin/oembed.inc.php` — URL から YouTube / Vimeo / Twitter(X) / Flickr 等を埋め込み表示
- `docs/OEMBED.md` — oEmbed 使い方・設定・セキュリティ・手動テスト手順
- `pukiwiki.ini.php.example` — oEmbed 設定雛形（抜粋）

### Changed

- `lib/convert_html.php` — 単独 HTTPS URL 行の oEmbed 自動検出
- `lib/init.php` — oEmbed 設定の既定値とプラグイン初期化
- `pukiwiki.ini.php` — oEmbed 設定ブロックを追加

### Security

- **セキュリティ強化:** `lib/csrf.php`, `lib/security.php` — CSRF トークン、SameSite Cookie、ログインレート制限、安全リダイレクト等
- `wiki/.htaccess` — ページソース直アクセス拒否（SEC-H05）
- パスワード検証を `pkwk_hash_verify()` に統一（`password_hash` / レガシーハッシュ両対応）
- 添付: `Content-Disposition: attachment`（危険 MIME）、パスワードを `password_hash` 化
- `PKWK_UPDATE_EXEC` の `system()` をホワイトリスト付き `proc_open` に置換
- oEmbed consumer: SSRF 対策（内部 IP / localhost 拒否、エンドポイント HTTPS 必須）
- oEmbed consumer: 返却 HTML のサニタイズ（許可タグ・iframe src ホワイトリスト）
- `docs/SECURITY-AUDIT.md` — 静的セキュリティ監査レポート
- `docs/ANTI-SPAM.md` — 匿名編集スパム対策の設定・運用ガイド
- `docs/ISSUES.md` — GitHub Issue 索引（監査 ID・スパム対策との対応表）
- `pukiwiki.ini.php.example` — 編集認証の設定雛形

### Changed

- **スパム対策:** 編集認証（`$edit_auth`）を既定で有効化し、全ページ編集にログイン必須化（匿名は閲覧のみ）
- `pukiwiki.ini.php`: `$auth_type = AUTH_TYPE_FORM`、`$edit_auth_pages` に `#.*# => valid-user`
- `lib/auth.php`: `enforce_edit_auth_for_request()` で未認証の変更系 GET/POST を早期遮断
- `lib/pukiwiki.php`: プラグイン実行前に編集認証ゲートを呼び出し
- `lib/file.php`: `page_write()` に `is_page_writable()` チェックを追加（拒否時はログイン誘導）
- ゲスト投稿プラグイン（`comment`, `memo`, `insert`, `vote`, `article`, `paint`）に `check_editable()` を追加

### Security

- 匿名による Wiki 編集・ゲストプラグイン経由の書き込みをブロック（ログイン必須）
- 編集フォームを経由しない未認証 POST 直叩きをリクエスト早期段階で拒否

### Notes

- ルート直下に PukiWiki 1.5.4 UTF-8 ソースが既に存在（`lib/init.php` → `S_VERSION = '1.5.4'`）
- 大規模改造はこれ以降 `CHANGELOG.md` と `docs/ARCHITECTURE.md` に記録すること
- 本番適用時は `pukiwiki.ini.php` の `$auth_users` と `$adminpass` を必ず設定すること（`docs/ANTI-SPAM.md` 参照）

---

## 記載ルール（メモ）

- **Added** … 新機能・新ファイル
- **Changed** … 既存挙動・設定の変更
- **Fixed** … バグ修正
- **Removed** … 削除・非推奨化
- **Security** … セキュリティ関連

リリースタグを切る場合は `[1.0.0] - YYYY-MM-DD` の見出しを追加してください。
