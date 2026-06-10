# 編集画面 D&D / クリップボード貼り付け（添付）

`cmd=edit` の編集画面のみ有効です。

## 機能

1. **ファイル → textarea** — OS からファイルをドロップすると `plugin=attach&pcmd=api` へ POST し、ページに添付。成功後 `#ref(ファイル名)` をカーソル位置に挿入。
2. **既存添付 → textarea** — ページ下部 `#attach` の各ファイルをドラッグし、textarea にドロップするとカーソル位置に `#ref` を挿入。
3. **クリップボード画像 → textarea** — Ctrl+V / Cmd+V でクリップボードに画像（PNG/JPEG/GIF/WebP 等）がある場合、attach API でアップロードし `#ref` を挿入。テキストのみの貼り付けは従来どおり。ファイル名は `paste-YYYYMMDD-HHmmss.png` 形式（複数枚は `-1` 等の連番）。

## `#ref` 記法

| 条件 | 挿入例 |
|------|--------|
| 同一ページの添付 | `#ref(example.png)` |
| 別ページの添付 | `#ref(OtherPage/example.png)` |

PukiWiki 推奨形式は `pagename/filename` です（`plugin/ref.inc.php` 参照）。

## セキュリティ

- 編集権限・ログイン（`$edit_auth`）・CSRF トークンは既存 attach と同様。
- ファイル種別・サイズは `PLUGIN_ATTACH_MAX_FILESIZE` 等の attach 制限に従います。

## 対応ブラウザ（paste）

| 環境 | 備考 |
|------|------|
| Chrome / Edge / Firefox（デスクトップ） | スクリーンショット貼り付け・画像コピーに対応 |
| Safari（macOS） | 概ね対応。OS / バージョンにより clipboard `items` の挙動差あり |
| モバイル | D&D 同様、paste による画像貼り付けは非対応または不安定なことが多い |
| 古いブラウザ | `fetch` / `FormData` / `clipboardData.items` 未対応時は機能無効（テキスト編集は従来どおり） |

## 関連ファイル

- `pukiwiki/skin/edit-dragdrop.js` — クライアント処理（D&D・paste）
- `pukiwiki/plugin/attach.inc.php` — `pcmd=api` JSON API、添付一覧の `data-attach-*` 属性
- `pukiwiki/skin/pukiwiki.skin.php` — edit 時のみ script 読み込み
