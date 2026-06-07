# DESIGN — スキン・機能の設計方針

PukiWiki2026 の軽量・小サイズ方針と、スキン／プラグインの役割分担を定義します。

## 基本方針

| 原則 | 内容 |
|------|------|
| **classic = オリジナル相当** | `skin/classic/` は PukiWiki 既定デザインの**非改変コピー**。参照実装兼フォールバック。**forge 開発時も変更禁止** |
| **forge = React モダン UI** | `skin/forge/` は React + Vite で構築した見た目専用スキン。PHP が JSON を注入しクライアントで描画 |
| **新機能 = オプション plugin** | ダークモード切替・リッチ UI 等の重い機能は `plugin/` に任意追加。使わなければ負荷ゼロ |

## スキン構成

```
skin/
├── classic/          … 既定（オリジナル相当・非改変）
├── forge/
│   ├── pukiwiki.skin.php   … 最小 HTML シェル + JSON 注入
│   ├── main.js             … PukiWiki 既存 JS（互換用）
│   ├── dist/               … React ビルド成果物（commit 対象）
│   └── ui/                 … React ソース（Vite + TypeScript）
├── tdiary.skin.php, keitai.skin.php  … UA プロファイル用（従来どおり）
└── …
```

### スキン名の選定

- **forge** — 「鍛える・作る」イメージ。PukiWiki2026 の改造 fork と相性がよいため採用。
- 候補として検討した名称: `slate`, `nexus`, `aurora`, `zenith`

### 切替方法

`pukiwiki.ini.php` で次を設定する。

```php
$skin = 'classic';  // 既定（オリジナル相当）
// $skin = 'forge';  // React モダン UI
```

`default.ini.php` が `$skin` に応じて `SKIN_FILE` と `SKIN_ASSETS_DIR` を解決する。

## forge React スキン

### アーキテクチャ

```
┌─────────────────────────────────────────────────────────┐
│  PHP (lib/html.php → catbody → pukiwiki.skin.php)         │
│    ・ページ HTML ($body)、ナビリンク、メタデータを生成      │
│    ・JSON を #pukiwiki-forge-initial に注入               │
│    ・#pukiwiki-forge-root + dist/assets/*.js|.css 出力   │
└──────────────────────────┬──────────────────────────────┘
                           │ HTML + JSON
                           ▼
┌─────────────────────────────────────────────────────────┐
│  React (skin/forge/ui → dist/)                          │
│    ・JSON を読み込み Header / Nav / Body / Footer を描画  │
│    ・Wiki 本文は dangerouslySetInnerHTML (#body)          │
│    ・main.js が #body 内の PukiWiki JS 機能を実行         │
└─────────────────────────────────────────────────────────┘
```

### 設計原則

| 項目 | 方針 |
|------|------|
| **依存** | react / react-dom のみ。外部 CDN なし |
| **デプロイ** | `dist/` を commit し PHP-only 環境でも動作 |
| **ダークモード** | `prefers-color-scheme`（CSS variables、JS 不要） |
| **classic 非改変** | forge 変更は `skin/forge/` + docs + `.gitignore` のみ |

### ビルド

```powershell
cd skin/forge/ui
npm install
npm run build
```

## プラグイン方針

- コア（`lib/`）への機能追加は最小限にし、拡張は `plugin/*.inc.php` を優先する
- プラグインは `#plugin()` または `?plugin=` で呼び出されたときのみ動作する
- スキンに組み込まないことで、スキン切替・軽量化と両立する

## 関連ドキュメント

- [ARCHITECTURE.md](ARCHITECTURE.md) — 全体アーキテクチャ
- [CHANGELOG.md](../CHANGELOG.md) — 変更履歴
