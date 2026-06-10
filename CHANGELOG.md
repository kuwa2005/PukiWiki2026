# Changelog

本リポジトリ（pukiwiki2026）における改造履歴。  
公式 PukiWiki のリリースノートではありません。上流は [PukiWiki 1.5.4 UTF-8](https://pukiwiki.osdn.jp/) です。

形式は [Keep a Changelog](https://keepachangelog.com/ja/1.1.0/) に近い簡易版とします。

## [Unreleased]

### Changed

- **編集画面のテキストエリア高さ** — `cmd=edit` で textarea がビューポート上下いっぱいに伸び、下部ツールバー（`#toolbar`）が常に見えるよう CSS を調整（`pukiwiki.css` / `tdiary.css`、flexbox + sticky toolbar）

### Added

- **凍結ページの匿名 comment / article** — `$comment_auth = 0`（既定）で `#comment` / `#pcomment` / `#article` を凍結ページでも匿名投稿可能に。CAPTCHA（reCAPTCHA 未設定時 honeypot）、IP レート制限、既存 Akismet / 外部リンク制限 / CSRF と連携（`lib/comment.php`）

### Fixed

- **凍結ページの comment / article がログイン必須になる問題** — SPAM-01 の `enforce_edit_auth_for_request()`・`check_editable()`・`page_write()` 防御層が guest POST プラグインも遮断していた問題を修正。ページ編集と comment / article 追加を分離

---

## [1.0.1] - 2026-06-07

**PukiWiki2026 v1.0.1** — v1.0.0 からのメンテナンスリリース。ディレクトリ構成の `pukiwiki/` 集約、認証・CSRF・Unicode セキュリティ強化、添付 2GB 上限、スキン利用ドキュメント等。

### Added

- **`docs/PUKIWIKI154-SKIN.md` §8 / `docs/DEPLOY.md` §4.6** — Apache mod_rewrite（および nginx 相当）で legacy `skin/` パスを吸収する**任意**デプロイ手段を追記（公式サポート外・正攻法はスキン内 `SKIN_DIR` 修正）
- **`docs/PUKIWIKI154-SKIN.md`** — PukiWiki 1.5.4 由来スキン利用方針（`SKIN_DIR` / `SKIN_FILE` のみ提供、スキン側パス修正は利用者責任、symlink 推奨の削除）。`README.md`・`SETUP.md`・`DEPLOY.md`・`pukiwiki.ini.php.example` からリンク
- **初回ログイン強制パスワード変更** — デモ用 `editor` / `editor` でログインした場合、`plugin/changepassword` で変更完了まで Wiki 操作をブロック。`lib/auth_ini.php` で `pukiwiki.ini.php` の `$auth_users` 該当行を自動更新（書き込み可能な場合）
- **起動時ディレクトリパーミッションチェック** — `lib/perm.php` を追加。Unix/Linux 本番で書き込みディレクトリ（`wiki/`, `diff/`, `backup/`, `cache/`, `attach/`, `counter/`）自身の mode のみ確認し、不適切な場合のみ chmod と配下の再帰修正。Windows では自動スキップ。`$perm_check_on_boot` 等は `pukiwiki.ini.php` で設定可能（`docs/DEPLOY.md` §3.2）

### Fixed

- **SEC-U01 — Unicode BiDi/RTL 制御文字によるページ名なりすまし** — `pkwk_is_safe_identifier()` / `pkwk_is_safe_pagename()` を `lib/security.php` に追加。`is_pagename()`・read 表示・loginform ユーザー名・添付 upload/rename で BiDi 制御（U+202A–U+202E, U+2066–U+2069 等）・ゼロ幅文字を拒否。テスト: `pukiwiki/tools/test-unicode-identifier.php`（Closes #81）
- **セキュリティ再監査（2026-06-07）** — `is_pagename_bytes_within_hard_limit()` が `PKWK_PAGENAME_BYTES_SOFT_LIMIT` を誤参照していた問題を修正（SEC-M09）。プラグイン POST フォームへ CSRF トークンを自動注入する `pkwk_csrf_inject_forms()` を追加（SEC-M10 部分対応）
- **添付ファイル情報画面の管理者パスワード案内** — ログイン済みでも削除・凍結・rename のラベルに「(管理者パスワードが必要です)」と表示されていた問題を修正。ログイン済みは操作案内のみ、未ログイン（`$edit_auth` 無効時）は従来どおり管理者パスワード案内（`$edit_auth` 有効時は mutation 前にログイン誘導）。凍結・凍結解除（PR #62）と同方針
- **凍結・凍結解除の確認メッセージ** — ログイン済みでも「パスワードを入力してください」と表示されていた問題を修正。ログイン済みはボタン押下案内、未ログイン（`$edit_auth` 無効時）は従来どおり管理者パスワード案内（`$edit_auth` 有効時はログイン誘導でフォーム未到達）
- **強制パスワード変更 — 666 でも ini 保存失敗・ハッシュ非表示** — `{x-php-password}` + Argon2 等（カンマ含む）の hash を `pkwk_ini_is_valid_auth_hash()` が誤って拒否していたため、権限修正前に `invalid_hash` で失敗していた問題を修正。`'editor' => 'editor'` 平文行・ダブルクォート形式も置換対象に。保存失敗時は理由コード・perm 診断 hint を loginform に表示。flash は session 破棄せず認証キーのみクリアして引き継ぎ。chmod 後 1 回再試行 — `pkwk_is_authenticated()` が `pkwk_must_change_password` 中は FALSE を返すよう変更。`pukiwiki.ini.php` への hash 保存失敗時はセッションを破棄して loginform へリダイレクト（ナビの「ログアウト」非表示）。ini 保存失敗時は `lib/perm.php` で親ディレクトリ（`$perm_dir_mode` 既定 0777）と ini ファイル自身（`$perm_file_mode` 既定 0666）の chmod を **1 回だけ**試行し、保存を **1 回だけ**再試行（Windows では perm 修正スキップ）
- **強制パスワード変更の保存失敗時に手動設定用ハッシュを再表示** — ini 保存失敗で loginform へリダイレクトした直後のみ、POST から生成した hash を 1 回限り表示（`pkwk_flash_set` / `pkwk_flash_consume`）。`gen-password-hash.php` / `docs/SETUP.md` 案内を併記
- **pukiwiki.ini.php 書き込み時のパーミッション修正** — mode 0644 でも Web サーバー実行ユーザーが書けない場合、`is_writable()` 判定で ini（0666）と親ディレクトリ（0777）を chmod してから atomic rename。書き込み処理内で chmod した分だけ完了後（失敗時も）元 mode へ復元。もともと書き込み可能・666 固定などプログラムが触っていない場合は変更しない。失敗時は owner/euid 等の debug hint を返す

### Changed

- **`docs/SECURITY-AUDIT.md`** — 2026-06-07 再監査。初回 Critical/High の Fixed 反映、Unicode/BiDi 攻撃（SEC-U01〜U05）とテストケース追記
- **添付ファイル上限を ini 設定化（既定 2GB）** — `pukiwiki.ini.php` の `$attach_max_filesize`（バイト）で変更可能に。雛形既定を 1MB から **2GB**（`2 * 1024 * 1024 * 1024`）へ変更。PHP / Web サーバー側の上限も合わせて引き上げる必要あり（`docs/SETUP.md`）
- **フッタに PukiWiki2026 のクレジットを追加** — `S_COPYRIGHT_2026`（`lib/init.php`）と `pkwk_footer_credits_html()`（`lib/func.php`）を追加。上流 PukiWiki・PukiWiki2026・PHP バージョン・HTML convert time を全スキン（`pukiwiki` / `tdiary` / `keitai`）で統一表示
- **ログイン済みユーザーは `$adminpass` 入力不要** — `pkwk_is_authenticated()` / `pkwk_admin_authorized()` を `lib/auth.php` に追加。凍結・凍結解除、rename、diff/backup 削除、dump、links/update_entities、attach 管理者操作、編集の「更新日時を変更しない」、外部リンク制限（モード 2）で、フォームログイン済みなら `$adminpass` 再入力をスキップ。未ログイン時は従来どおり（`$edit_auth` 有効時は mutation 前にログイン誘導）
- **公式同梱ファイルを `pukiwiki/` へ集約** — `COPYING.txt`・`README.txt`・`UPDATING.txt`・`INSTALL.txt`・`*.en.txt.zip`・`wiki.en.zip` を root から `pukiwiki/` へ移動。root は `index.php`・`.htaccess`・プロジェクト文書（`README.md` / `CHANGELOG.md`）等のみ。`BACKUP.md`・`ARCHITECTURE.md`・`DEPLOY.md`・`SETUP.md`・`UPSTREAM.md` の参照を更新
- **デフォルト編集者パスワード** — 初期値を `pass` から `editor` に変更（ハッシュ `{x-php-sha256}1553cc62ff246044c683a61e203e65541990e7fcd4af9443d22b9557ecc9ac54`）。`pukiwiki.ini.php.example`・各種ドキュメントを更新。**必ず変更してから使うこと** を強調

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

リリースタグを切る場合は `[1.0.1] - YYYY-MM-DD` の見出しを追加してください。
