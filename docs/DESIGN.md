# DESIGN — スキン・機能の設計方針

PukiWiki2026 の軽量・小サイズ方針と、スキン／プラグインの役割分担を定義します。

## 基本方針

| 原則 | 内容 |
|------|------|
| **軽量・小サイズ最優先** | 外部 JS/CSS フレームワークは使わない。スキンは数ファイル・数十 KB 程度を目安とする |
| **classic = オリジナル相当** | `skin/classic/` は PukiWiki 既定デザインの非改変コピー。参照実装兼フォールバック |
| **新スキン = 見た目のみ** | `skin/forge/` 等は配色・タイポグラフィ・余白の調整に留める。HTML 構造の大改造はしない |
| **新機能 = オプション plugin** | ダークモード切替・リッチ UI 等の重い機能は `plugin/` に任意追加。使わなければ負荷ゼロ |

## スキン構成

```
skin/
├── classic/     … 既定（オリジナル相当・非改変）
├── forge/       … モダン寄り UI（classic コピー + CSS 調整）
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
// $skin = 'forge';  // モダン寄り UI
```

`default.ini.php` が `$skin` に応じて `SKIN_FILE` と `SKIN_ASSETS_DIR` を解決する。

## プラグイン方針

- コア（`lib/`）への機能追加は最小限にし、拡張は `plugin/*.inc.php` を優先する
- プラグインは `#plugin()` または `?plugin=` で呼び出されたときのみ動作する
- スキンに組み込まないことで、スキン切替・軽量化と両立する

## 関連ドキュメント

- [ARCHITECTURE.md](ARCHITECTURE.md) — 全体アーキテクチャ
- [CHANGELOG.md](../CHANGELOG.md) — 変更履歴
