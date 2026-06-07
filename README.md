# pukiwiki2026

[![Release](https://img.shields.io/github/v/release/kuwa2005/PukiWiki2026?label=PukiWiki2026)](https://github.com/kuwa2005/PukiWiki2026/releases/tag/v1.0.0)

**PukiWiki 1.5.4 UTF-8 ベース + PukiWiki2026 v1.0.0 セキュリティ強化 fork**（非公式）

> PukiWiki2026 は公式 PukiWiki 1.5.4 UTF-8 をベースに、認証必須化・CSRF・スパム対策（Akismet/CAPTCHA 等）・セキュリティ監査対応を加えた非公式 fork です。  
> 公式 PukiWiki Development Team の配布物ではありません。

| 項目 | 内容 |
|------|------|
| バージョン | **PukiWiki2026 v1.0.0** |
| ベース | [PukiWiki](https://pukiwiki.osdn.jp/) **1.5.4 UTF-8** |
| ライセンス | **GPL v2** または（あなたの選択で）それ以降の GPL（上流に準拠） |
| リリース | [v1.0.0](https://github.com/kuwa2005/PukiWiki2026/releases/tag/v1.0.0) |
| 作業フォルダ | `D:\00_project\pukiwiki2026` |

## ディレクトリ構成

```
pukiwiki2026/                    ← git リポジトリ root
├── index.php                    ← エントリ（DATA_HOME 定義）
├── .htaccess, README.md, CHANGELOG.md
├── .gitignore, .github/         … git / CI 用
└── pukiwiki/                    ← ★ デプロイ / バックアップ対象
    ├── lib/, plugin/, skin/, image/
    ├── wiki/, cache/, backup/, attach/
    ├── pukiwiki.ini.php         … 設定（git 除外）
    ├── pukiwiki.ini.php.example
    ├── docs/, tools/            … 開発用（バックアップに含む）
    ├── README.txt, UPDATING.txt, COPYING.txt, INSTALL.txt  … 公式同梱
    ├── *.en.txt.zip, wiki.en.zip            … 公式同梱（英語・初期 wiki）
    └── …
```

**バックアップ:** `index.php` と `pukiwiki/` をコピーするだけで完了。詳細: [pukiwiki/docs/BACKUP.md](pukiwiki/docs/BACKUP.md)

**`.htaccess`:** 同梱の `.htaccess` は**任意・推奨**（Apache で直接アクセス拒否に使える。無くても Wiki 本体は動作）。詳細: [pukiwiki/docs/DEPLOY.md §4.5](pukiwiki/docs/DEPLOY.md#45-htaccess任意推奨)

**起動時パーミッションチェック:** Unix/Linux 本番で書き込みディレクトリの mode を起動時に確認・修正（不適切な場合のみ配下も再帰修正）。Windows 開発環境では自動スキップ。詳細: [pukiwiki/docs/DEPLOY.md §3.2](pukiwiki/docs/DEPLOY.md#32-パーミッション)

公式との diff は git タグ **`upstream-1.5.4-utf8`** を基準に取ります（[pukiwiki/docs/UPSTREAM.md](pukiwiki/docs/UPSTREAM.md)）。ローカルに vendor コピーは不要です。

## クイックスタート

1. Web サーバー（Apache / nginx + PHP 8.x 推奨）のドキュメントルート、または仮想ホストで本フォルダを公開する。
2. `pukiwiki/pukiwiki.ini.php.example` を参考に `pukiwiki/pukiwiki.ini.php` を編集する（初回はコピー）。
3. `pukiwiki/wiki/`・`pukiwiki/cache/`・`pukiwiki/backup/` 等に書き込み権限を付与する（Unix/Linux 本番では起動時パーミッションチェックが自動実行 — [pukiwiki/docs/DEPLOY.md §3.2](pukiwiki/docs/DEPLOY.md#32-パーミッション)）。
4. ブラウザで `index.php` にアクセスし、初期ページが表示されることを確認する。

### 初回ログイン

| 項目 | 値 |
|------|-----|
| ユーザー名 | `editor` |
| パスワード | `editor` |

> **必ず変更して使うこと。** `editor` / `editor` はデモ用初期値です。初回ログイン時にパスワード変更画面が表示されます。本番・公開前にも必ずパスワードを変更してください。

パスワード変更: 初回ログイン時の **強制変更 UI**（`?plugin=changepassword`）または **`pukiwiki/tools/gen-password-hash.php`**（Web）/ [pukiwiki/docs/SETUP.md](pukiwiki/docs/SETUP.md) の CLI 手順。

詳細: [pukiwiki/docs/SETUP.md](pukiwiki/docs/SETUP.md) · [pukiwiki/docs/DEPLOY.md](pukiwiki/docs/DEPLOY.md)

## 改造の進め方

- 上流との差分方針: [pukiwiki/docs/UPSTREAM.md](pukiwiki/docs/UPSTREAM.md)
- 設計メモ: [pukiwiki/docs/ARCHITECTURE.md](pukiwiki/docs/ARCHITECTURE.md)
- **PukiWiki 1.5.4 スキン利用:** [pukiwiki/docs/PUKIWIKI154-SKIN.md](pukiwiki/docs/PUKIWIKI154-SKIN.md) — `SKIN_DIR` / `SKIN_FILE` のみ提供。サブディレクトリ skin では `SKIN_FILE` 必須。任意: [Apache rewrite で legacy `skin/` 吸収](pukiwiki/docs/PUKIWIKI154-SKIN.md#8-任意-apache-mod_rewrite-で-legacy-skin-パスを吸収)
- 変更履歴: [CHANGELOG.md](CHANGELOG.md)

## バージョン管理

リポジトリ: **https://github.com/kuwa2005/PukiWiki2026**（ブランチ `main`、上流タグ `upstream-1.5.4-utf8`）

```powershell
cd D:\00_project\pukiwiki2026
git pull origin main   # 作業前に同期
# 変更後: git add … → git commit → git push origin main
```

`.env` や `pukiwiki/wiki/`・`pukiwiki/cache/` 等は `.gitignore` で除外済みです。

## 参考リンク

- 公式サイト: https://pukiwiki.osdn.jp/
- 開発情報: https://pukiwiki.osdn.jp/dev/
- 上流 README: [README.txt](pukiwiki/README.txt)
