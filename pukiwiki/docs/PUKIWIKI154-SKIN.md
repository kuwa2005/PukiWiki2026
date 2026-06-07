# PUKIWIKI154-SKIN — PukiWiki 1.5.4 のスキンを動かす際は

PukiWiki2026 で **PukiWiki 1.5.4 由来のサードパーティスキン**（modernskin、bluebox 等）を使うときの方針と手順です。

## 方針（PukiWiki2026 の責務）

PukiWiki2026 がカスタムスキン向けに提供するのは **`SKIN_DIR` と `SKIN_FILE` の設定のみ**です。

| 項目 | 内容 |
|------|------|
| **提供する** | `pukiwiki.ini.php` で `SKIN_DIR` / `SKIN_FILE` を指定する仕組み |
| **提供しない** | legacy スキンの `skin/main.js` 等をコア側で吸収する処理 |
| **提供しない** | Wiki ルートへの `skin` symlink 自動作成 |
| **提供しない** | 1.5.4 スキン内のパス修正（**利用者がスキン側を書き換える**） |

> **スキンの改造・互換対応は利用者責任**です。本リポジトリ同梱の `pukiwiki.skin.php` は PukiWiki2026 構成（`pukiwiki/` プレフィックス）に合わせた参考実装です。

---

## 目次

1. [PukiWiki2026 のパス構成（PR #44 以降）](#1-pukiwiki2026-のパス構成pr-44-以降)
2. [設定: SKIN_DIR と SKIN_FILE](#2-設定-skin_dir-と-skin_file)
3. [よくある問題](#3-よくある問題)
4. [修正例（スキン側）](#4-修正例スキン側)
5. [参考: modernskin / bluebox](#5-参考-modernskin--bluebox)
6. [アセット（icon / logo 等）の配置](#6-アセットicon--logo-等の配置)
7. [動作確認（DevTools Network）](#7-動作確認devtools-network)
8. [任意: Apache mod_rewrite で legacy `skin/` パスを吸収](#8-任意-apache-mod_rewrite-で-legacy-skin-パスを吸収)
9. [関連ドキュメント](#9-関連ドキュメント)

---

## 1. PukiWiki2026 のパス構成（PR #44 以降）

公式 PukiWiki 1.5.4 は **Wiki ルート = `index.php` と `skin/` が同階** でした。PukiWiki2026 は PR [#44](https://github.com/kuwa2005/PukiWiki2026/pull/44) 以降、次の構成です。

```
pukiwiki2026/                 ← リポジトリ root / Web 公開ルート
├── index.php                 ← エントリ（DATA_HOME を定義）
└── pukiwiki/                 ← Wiki 本体（DATA_HOME）
    ├── lib/, plugin/, skin/, image/
    ├── pukiwiki.ini.php
    └── wiki/, cache/, …
```

| 種別 | 1.5.4 公式 | PukiWiki2026 |
|------|------------|--------------|
| エントリ | `./index.php` | `./index.php`（変更なし） |
| 本体 | `./lib/`, `./skin/` 等 | `./pukiwiki/lib/`, `./pukiwiki/skin/` 等 |
| Web 上の CSS/JS | `skin/main.js` | **`pukiwiki/skin/main.js`**（`SKIN_DIR` 経由） |
| スキン PHP | `skin/*.skin.php` | `pukiwiki/skin/*.skin.php`（`SKIN_FILE` = `DATA_HOME` 基準） |

`index.php` では `DATA_HOME` が `__DIR__ . '/pukiwiki/'` に固定されています。1.5.4 スキンをそのまま置くだけでは **相対 URL がずれて 404** になります。

---

## 2. 設定: SKIN_DIR と SKIN_FILE

`pukiwiki/pukiwiki.ini.php` で指定します（雛形: `pukiwiki.ini.php.example`）。

### 同梱デフォルトスキン（`skin/` 直下）

```php
define('SKIN_DIR', 'pukiwiki/skin/');
// SKIN_FILE 未指定時は default.ini.php が DATA_HOME . 'skin/pukiwiki.skin.php' を使用
```

### サブディレクトリ skin（modernskin / bluebox 等）

**`SKIN_FILE` も必ず指定**してください。未指定のまま `SKIN_DIR` だけ変えると、表示テンプレートと CSS/JS の参照先が一致しません。

```php
define('SKIN_DIR', 'pukiwiki/skin/modernskin/');
define('SKIN_FILE', DATA_HOME . 'skin/modernskin/pukiwiki.skin.php');
```

| 定数 | 意味 |
|------|------|
| `SKIN_DIR` | ブラウザから見える CSS/JS/画像への **Web パス**（`index.php` からの相対。末尾 `/`） |
| `SKIN_FILE` | `*.skin.php` の **ファイルシステムパス**（`DATA_HOME` 基準） |

---

## 3. よくある問題

| 症状 | 原因 | 対処 |
|------|------|------|
| ページは出るが CSS/JS が効かない | `SKIN_DIR` が `skin/` のまま（`pukiwiki/` プレフィックス不足） | `SKIN_DIR` を `pukiwiki/skin/…/` に修正 |
| デフォルト見た目のまま | サブディレクトリ skin で **`SKIN_FILE` 未設定** | `SKIN_FILE` を `DATA_HOME . 'skin/…/….skin.php'` に設定 |
| Network で **`skin/main.js` 404** | 1.5.4 スキンが **`skin/` 固定パス**のまま | スキン内の `<script>` / `<link>` を `SKIN_DIR` 使用に書き換え（[§4](#4-修正例スキン側)） |
| ロゴ・アイコン 404 | `IMAGE_DIR` / スキン内の画像パスが 1.5.4 前提 | [§6](#6-アセットicon--logo-等の配置) を参照 |
| symlink で一時回避 | Wiki ルートに `skin` → `pukiwiki/skin` を置く方法 | **PukiWiki2026 公式方針では推奨しない**。スキン側を直す |

---

## 4. 修正例（スキン側）

対象は **`*.skin.php` 内の HTML** およびスキン付属 JS が参照する URL です。PukiWiki2026 同梱の `pukiwiki.skin.php` が正しい例です。

### CSS / JS（`<link>` / `<script>`）

**修正前（1.5.4 固定パス）:**

```html
<link rel="stylesheet" href="skin/pukiwiki.css" />
<script src="skin/main.js" defer></script>
<script src="skin/search2.js" defer></script>
```

**修正後（`SKIN_DIR` 使用）:**

```html
<link rel="stylesheet" href="<?php echo SKIN_DIR ?>pukiwiki.css" />
<script src="<?php echo SKIN_DIR ?>main.js" defer></script>
<script src="<?php echo SKIN_DIR ?>search2.js" defer></script>
```

属性を分ける場合:

```html
<script type="text/javascript" src="<?php echo SKIN_DIR ?>main.js" defer></script>
```

### ロゴ（`IMAGE_DIR`）

同梱スキンでは `IMAGE_DIR`（既定 `pukiwiki/image/`）を使います。

```html
<img src="<?php echo IMAGE_DIR . $image['logo'] ?>" alt="…" />
```

スキン専用画像を `skin/` 配下に置く場合:

```html
<img src="<?php echo SKIN_DIR ?>logo.png" alt="…" />
```

### JS 内の相対パス

`main.js` 等が `skin/` や `./` 固定で他ファイルを読む場合も、**スキン側でパスを見直す**必要があります（コア側での吸収は行いません）。

---

## 5. 参考: modernskin / bluebox

[modernskin](https://pukiwiki.osdn.jp/?PukiWiki/modernskin) や [bluebox](https://pukiwiki.osdn.jp/?PukiWiki/bluebox) など、コミュニティ製 1.5.4 スキンを使う場合の **設定例** です（リポジトリ同梱ではありません）。

1. スキンファイル一式を `pukiwiki/skin/<名前>/` に配置する。
2. `pukiwiki.ini.php` で `SKIN_DIR` と `SKIN_FILE` をセットする。
3. スキン内の CSS/JS/画像参照を [§4](#4-修正例スキン側) のとおり **`SKIN_DIR` / `IMAGE_DIR`** に合わせて書き換える。

```php
// modernskin の例
define('SKIN_DIR', 'pukiwiki/skin/modernskin/');
define('SKIN_FILE', DATA_HOME . 'skin/modernskin/pukiwiki.skin.php');

// bluebox の例
define('SKIN_DIR', 'pukiwiki/skin/bluebox/');
define('SKIN_FILE', DATA_HOME . 'skin/bluebox/pukiwiki.skin.php');
```

同梱の `pukiwiki/skin/pukiwiki.skin.php` も、上記と同じパターンで `SKIN_DIR` を使っています。新規スキンを書くときの雛形にしてください。

---

## 6. アセット（icon / logo 等）の配置

| 種類 | 推奨配置 | Web パス例 |
|------|----------|------------|
| サイト共通画像（ロゴ等） | `pukiwiki/image/` | `IMAGE_DIR`（`pukiwiki/image/`） |
| スキン専用 CSS/JS | `pukiwiki/skin/<skin>/` | `SKIN_DIR` |
| スキン専用 icon/ 等 | `pukiwiki/skin/<skin>/icon/` 等 | `<?php echo SKIN_DIR ?>icon/…` |
| favicon | スキンまたは `pukiwiki/image/` | スキン内で `SKIN_DIR` / `IMAGE_DIR` を明示 |

ファイルのコピー・ディレクトリ作成も **利用者側** で行います。PukiWiki2026 はスキン用アセットの自動配置をしません。

---

## 7. 動作確認（DevTools Network）

1. ブラウザで Wiki のトップ（または任意ページ）を開く。
2. **開発者ツール** → **Network**（ネットワーク）タブを開く。
3. ページを **再読み込み**（キャッシュ無効化が望ましい: Ctrl+Shift+R）。
4. 次を確認する。

| 確認項目 | 期待 |
|----------|------|
| CSS（例: `pukiwiki.css`） | Status **200**、パスが `…/pukiwiki/skin/…` |
| JS（例: `main.js`, `search2.js`） | Status **200**、同上 |
| **`skin/main.js`（ルート直下）** | **404 にならないこと**（出る場合はスキン未修正） |
| ロゴ・favicon | Status **200** |

Console に JS エラー（`main.js` 未定義等）が出ていないことも合わせて確認してください。

rewrite で `/skin/` を吸収している場合、Network 上の URL は `/skin/main.js` のまま **200** になることがあります（[§8](#8-任意-apache-mod_rewrite-で-legacy-skin-パスを吸収) 参照）。正攻法ではパスが `…/pukiwiki/skin/…` になることを期待します。

---

## 8. 任意: Apache mod_rewrite で legacy `skin/` パスを吸収

> **PukiWiki2026 公式サポート外・推奨正攻法ではありません。** [PR #78](https://github.com/kuwa2005/PukiWiki2026/pull/78) の方針どおり、コアが提供するのは `SKIN_DIR` / `SKIN_FILE` のみです。1.5.4 スキン内で `SKIN_DIR` を使う修正（[§4](#4-修正例スキン側)）が**正攻法**です。
>
> 以下は **Apache 環境の利用者** が、スキン改造を避けたい場合に試せる**任意のデプロイ手段**です。同梱 `.htaccess` には含まれません（利用者がルート `.htaccess` 等に**自行追加**）。

### 前提

| 項目 | 内容 |
|------|------|
| Web サーバー | **Apache** + `mod_rewrite` 有効 |
| `AllowOverride` | **`FileInfo`** 以上（または `All`）。rewrite ルールを `.htaccess` で使うため |
| 配置 | Wiki ルート（`index.php` と同階）の `.htaccess` または vhost 設定 |

nginx 等 `.htaccess` 非対応環境の同等設定は [DEPLOY.md §4.6](./DEPLOY.md#46-任意-legacy-skin-パスの-rewritenginx-相当) を参照してください。

### RewriteRule 例

同梱デフォルトスキン（`pukiwiki/skin/` 直下）向け。1.5.4 スキンが `skin/main.js` 等を参照している場合、内部転送で実体 `pukiwiki/skin/` から配信できます。

```apache
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /

  # 1.5.4 固定パス skin/* → pukiwiki/skin/*（内部転送）
  RewriteRule ^skin/(.*)$ pukiwiki/skin/$1 [L]
</IfModule>
```

| リクエスト | 転送先 |
|------------|--------|
| `/skin/main.js` | `pukiwiki/skin/main.js` |
| `/skin/pukiwiki.css` | `pukiwiki/skin/pukiwiki.css` |
| `/skin/bluebox/external_link.gif` | `pukiwiki/skin/bluebox/external_link.gif` |

Wiki を `https://example.com/wiki/` 配下に置く場合は `RewriteBase /wiki/` に合わせてください。

静的アセットのみに限定する例（任意）:

```apache
RewriteRule ^skin/(.+\.(css|js|gif|png|jpe?g|ico|svg|woff2?|webp))$ pukiwiki/skin/$1 [L,NC]
```

転送先の `pukiwiki/skin/.htaccess` が `*.skin.php` 直接アクセスを拒否するため、基本形でも `/skin/*.skin.php` は **403** になります。

### 制限（rewrite だけでは足りないケース）

| ケース | 理由 |
|--------|------|
| **サブディレクトリ skin**（modernskin / bluebox） | `SKIN_DIR` = `pukiwiki/skin/bluebox/` なのに 1.5.4 スキンが `skin/main.js`（`bluebox/` なし）を参照すると、転送先は `pukiwiki/skin/main.js`（**存在しない**）。実体は `pukiwiki/skin/bluebox/main.js` |
| **JS 内の固定パス** | `main.js` 等が `skin/` 固定で他ファイルを読む場合、HTML の `<script>` だけ直しても不十分なことがある（[§4](#4-修正例スキン側)） |
| **`image/` パス** | `IMAGE_DIR`（`pukiwiki/image/`）向けの `image/logo.png` 等は**別ルール**かスキン修正が必要 |

### 位置付け

| 手段 | PukiWiki2026 視点 |
|------|-------------------|
| スキン内で `SKIN_DIR` を使う | **推奨（正攻法）** |
| mod_rewrite（本節） | 任意・限定的（移行のつなぎ） |
| Wiki ルート symlink | **非推奨**（[§3](#3-よくある問題)） |

---

## 9. 関連ドキュメント

- [SETUP.md](./SETUP.md) — 初回セットアップ
- [DEPLOY.md](./DEPLOY.md) — デプロイ・トラブルシューティング（CSS/JS 404）
- [ARCHITECTURE.md](./ARCHITECTURE.md) — ディレクトリ構成
- [UPSTREAM.md](./UPSTREAM.md) — 公式 1.5.4 との diff
- `pukiwiki/pukiwiki.ini.php.example` — `SKIN_DIR` / `SKIN_FILE` の雛形
