# Changelog

本リポジトリ（pukiwiki2026）における改造履歴。  
公式 PukiWiki のリリースノートではありません。上流は [PukiWiki 1.5.4 UTF-8](https://pukiwiki.osdn.jp/) です。

形式は [Keep a Changelog](https://keepachangelog.com/ja/1.1.0/) に近い簡易版とします。

## [Unreleased]

### Added

- プロジェクト骨格: `README.md`, `CHANGELOG.md`, `docs/`, `vendor/`, `patches/`, `.gitignore`
- 作業フォルダ `D:\00_project\pukiwiki2026` として非公式フォーク用ドキュメントを整備
- `docs/SECURITY-AUDIT.md` — 静的セキュリティ監査レポート
- `docs/ANTI-SPAM.md` — 匿名編集スパム対策の設定・運用ガイド

### Changed

- **スパム対策:** 編集認証（`$edit_auth`）を既定で有効化し、全ページ編集にログイン必須化（匿名は閲覧のみ）
- `pukiwiki.ini.php`: `$auth_type = AUTH_TYPE_FORM`、`$edit_auth_pages` に `#.*# => valid-user`
- `lib/file.php`: `page_write()` に `is_page_writable()` チェックを追加（防御層）
- ゲスト投稿プラグイン（`comment`, `memo`, `insert`, `vote`, `article`, `paint`）に `check_editable()` を追加

### Security

- 匿名による Wiki 編集・ゲストプラグイン経由の書き込みをブロック（ログイン必須）

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
