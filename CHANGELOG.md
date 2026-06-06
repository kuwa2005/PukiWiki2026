# Changelog

本リポジトリ（pukiwiki2026）における改造履歴。  
公式 PukiWiki のリリースノートではありません。上流は [PukiWiki 1.5.4 UTF-8](https://pukiwiki.osdn.jp/) です。

形式は [Keep a Changelog](https://keepachangelog.com/ja/1.1.0/) に近い簡易版とします。

## [Unreleased]

### Added

- プロジェクト骨格: `README.md`, `CHANGELOG.md`, `docs/`, `vendor/`, `patches/`, `.gitignore`
- 作業フォルダ `D:\00_project\pukiwiki2026` として非公式フォーク用ドキュメントを整備

### Notes

- ルート直下に PukiWiki 1.5.4 UTF-8 ソースが既に存在（`lib/init.php` → `S_VERSION = '1.5.4'`）
- 大規模改造はこれ以降 `CHANGELOG.md` と `docs/ARCHITECTURE.md` に記録すること

---

## 記載ルール（メモ）

- **Added** … 新機能・新ファイル
- **Changed** … 既存挙動・設定の変更
- **Fixed** … バグ修正
- **Removed** … 削除・非推奨化
- **Security** … セキュリティ関連

リリースタグを切る場合は `[1.0.0] - YYYY-MM-DD` の見出しを追加してください。
