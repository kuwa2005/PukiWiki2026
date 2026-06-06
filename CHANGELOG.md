# Changelog

本リポジトリ（pukiwiki2026）における改造履歴。  
公式 PukiWiki のリリースノートではありません。上流は [PukiWiki 1.5.4 UTF-8](https://pukiwiki.osdn.jp/) です。

形式は [Keep a Changelog](https://keepachangelog.com/ja/1.1.0/) に近い簡易版とします。

## [Unreleased]

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
