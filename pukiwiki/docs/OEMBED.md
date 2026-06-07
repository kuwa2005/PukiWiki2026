# OEMBED — oEmbed 埋め込み機能

PukiWiki2026 の oEmbed consumer 機能の使い方・設定・セキュリティ注意事項。

## 概要

Wiki ページに **YouTube / Vimeo / Twitter(X) / Flickr** 等の URL を書くだけで、oEmbed プロバイダから取得した HTML をサニタイズして埋め込み表示します。

- 外部プロキシ（noembed.com 等）は **使用しません**（自前 consumer）
- **表示側**機能のため、匿名閲覧者にも埋め込み HTML が出力されます
- 編集は別途認証設定（`docs/ANTI-SPAM.md` 等）に従います

## 記法

### 単独 URL 行（自動）

行全体が HTTPS URL のみの場合、自動で oEmbed を試みます。

```text
https://www.youtube.com/watch?v=dQw4w9WgXcQ
```

対応不可・取得失敗時は従来どおり `<a href="...">` リンク表示にフォールバックします。

### ブロックプラグイン

```text
#oembed(https://vimeo.com/123456789)
```

### インラインプラグイン

```text
動画: &oembed(https://www.youtube.com/watch?v=example);
```

## 対応プロバイダ（固定リスト）

| ID | サービス | 例 URL |
|----|----------|--------|
| `youtube` | YouTube | `https://www.youtube.com/watch?v=...`, `https://youtu.be/...` |
| `vimeo` | Vimeo | `https://vimeo.com/123456789` |
| `twitter` | Twitter / X | `https://twitter.com/user/status/123`, `https://x.com/user/status/123` |
| `flickr` | Flickr | `https://www.flickr.com/photos/user/123456789/` |

上記にマッチしない URL でも、ページ HTML 内の oEmbed discovery  
（`<link rel="alternate" type="application/json+oembed" href="...">`）があれば取得を試みます。

## 設定（`pukiwiki.ini.php`）

```php
$oembed_enabled = 1;        // 1 = 有効, 0 = 無効
$oembed_standalone_url = 1; // 1 = 単独 URL 行を自動埋め込み
$oembed_providers = array(  // 許可プロバイダ
    'youtube', 'vimeo', 'twitter', 'flickr',
);
$oembed_maxwidth = 640;
$oembed_maxheight = 480;
$oembed_cache_hours = 24;   // 0 = キャッシュ無効（CACHE_DIR/oembed/）
```

## 実装ファイル

| ファイル | 役割 |
|----------|------|
| `lib/oembed.php` | consumer・SSRF/XSS 対策・キャッシュ |
| `plugin/oembed.inc.php` | `#oembed` / `&oembed` プラグイン |
| `lib/convert_html.php` | 単独 URL 行のフック |
| `lib/init.php` | 設定読み込み・プラグイン初期化 |

## セキュリティ

### SSRF 対策

- `http` / `https` のみ許可
- 内部ネットワーク（RFC1918、loopback、link-local 等）への fetch を拒否
- `localhost` 等のホスト名を拒否
- oEmbed **エンドポイントは HTTPS 必須**
- HTTP タイムアウト: `PKWK_OEMBED_HTTP_TIMEOUT`（既定 10 秒、`pkwk_http_request` 経由）

### XSS 対策

- 返却 HTML を DOM ベースでサニタイズ
- 許可タグ: `iframe`, `blockquote`, `a`, `p`, `div`, `span`, `img`（属性も制限）
- `iframe` の `src` はホワイトリストドメインのみ（YouTube, Vimeo, Twitter/X, Flickr 等）
- `script`, `object`, `embed`, イベント属性、`javascript:` URL を除去

### 運用上の注意

- 埋め込み先の第三者サービス（YouTube 等）の利用規約・Cookie ポリシーを確認してください
- キャッシュディレクトリ `cache/oembed/` に取得 HTML が保存されます（`$oembed_cache_hours` で期限管理）

## 手動テスト手順

1. Web サーバーで PukiWiki を起動（PHP + 外向き HTTP が可能なこと）
2. テストページを編集し、YouTube URL を単独行で記載 → 保存 → プレビュー
3. `#oembed(https://vimeo.com/...)` で Vimeo 埋め込みを確認
4. 対応外 URL（例: 自サイトトップ）→ リンク表示のみになることを確認
5. `$oembed_enabled = 0` に変更 → すべてリンク表示に戻ることを確認

### PHP 構文チェック

```powershell
cd D:\00_project\pukiwiki2026
php -l lib\oembed.php
php -l plugin\oembed.inc.php
```

## 既知の制限・将来拡張

| 項目 | 状態 |
|------|------|
| Instagram / TikTok 等 | 未対応（プロバイダ追加 Issue 化可） |
| サムネイルのみ表示（`type=photo`） | HTML 埋め込み中心。画像 oEmbed の最適化は将来対応 |
| IPv6 内部アドレス | IPv4 中心の SSRF チェック。IPv6 厳密化は将来 Issue 化可 |
| Content-Security-Policy | iframe 許可は HTML サニタイズのみ。CSP ヘッダ連携は SEC-M06 と関連 |

## 関連ドキュメント

- [ISSUES.md](./ISSUES.md) — Issue 運用
- [GIT-WORKFLOW.md](./GIT-WORKFLOW.md) — ブランチ・PR
- [SECURITY-AUDIT.md](./SECURITY-AUDIT.md) — SEC-M07（ref SSRF）等
