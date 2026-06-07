forge — PukiWiki2026 React スキン
==================================

React + Vite + TypeScript で構築したモダン UI スキンです。
PHP サーバーが HTML シェルとページデータ JSON を出力し、React がレイアウトを描画します。

## 特徴

- 外部 CDN 依存なし（ビルド成果物を同梱）
- `prefers-color-scheme` によるダークモード（CSS のみ）
- システムフォントスタック、レスポンシブレイアウト
- PukiWiki 既存 JS（main.js）との互換（#body, #pukiwiki-site-properties）

## 切替

`pukiwiki.ini.php` で次を設定:

```php
$skin = 'forge';
```

既定は `classic` です。

## ビルド

```powershell
cd skin/forge/ui
npm install
npm run build
```

出力先: `skin/forge/dist/`（PHP-only デプロイのため commit 対象）

## 開発プレビュー

```powershell
cd skin/forge/ui
npm run dev
```

## アーキテクチャ

```
PHP (pukiwiki.skin.php)
  → window.__FORGE_INITIAL__ / #pukiwiki-forge-initial (JSON)
  → #pukiwiki-forge-root (React mount)
  → dist/assets/*.js, *.css
```

## ライセンス

GPL v2 or later（PukiWiki 本体に準拠）
