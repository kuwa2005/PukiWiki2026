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
├── .github/, .gitignore         … CI・git 設定（root のみ）
└── pukiwiki/                    ← ★ デプロイ / バックアップ対象
    ├── lib/, plugin/, skin/, image/
    ├── wiki/, cache/, backup/, attach/
    ├── pukiwiki.ini.php         … 設定（git 除外）
    ├── pukiwiki.ini.php.example
    ├── README.md, CHANGELOG.md  … 開発用ドキュメント
    ├── docs/, tools/, vendor/    … 開発用（バックアップに含む）
    └── patches/                 … パッチ保管
```

**バックアップ:** `index.php` と `pukiwiki/` をコピーするだけで完了（`docs/` 含む）。詳細: [docs/BACKUP.md](docs/BACKUP.md)

`pukiwiki/vendor/` には**未改造の公式配布物**を別途置き、diff の基準に使います（[docs/UPSTREAM.md](docs/UPSTREAM.md)）。

## クイックスタート

1. Web サーバー（Apache / nginx + PHP 8.x 推奨）のドキュメントルート、または仮想ホストで本フォルダを公開する。
2. `pukiwiki/pukiwiki.ini.php.example` を参考に `pukiwiki/pukiwiki.ini.php` を編集する（初回はコピー）。
3. `pukiwiki/wiki/`・`pukiwiki/cache/`・`pukiwiki/backup/` に書き込み権限を付与する。
4. ブラウザで `index.php` にアクセスし、初期ページが表示されることを確認する。

### 初回ログイン

| 項目 | 値 |
|------|-----|
| ユーザー名 | `editor` |
| パスワード | `pass` |

> **必ず変更して使うこと。** `editor` / `pass` はデモ用初期値です。本番・公開前にパスワードを変更してください。

パスワードハッシュの生成: **`pukiwiki/tools/gen-password-hash.php`**（Web）または [docs/SETUP.md](docs/SETUP.md) の CLI 手順。

詳細: [docs/SETUP.md](docs/SETUP.md) · [docs/DEPLOY.md](docs/DEPLOY.md)

## 改造の進め方

- 上流との差分方針: [docs/UPSTREAM.md](docs/UPSTREAM.md)
- 設計メモ: [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)
- 変更履歴: [CHANGELOG.md](CHANGELOG.md)

## バージョン管理

リポジトリ: **https://github.com/kuwa2005/PukiWiki2026**（ブランチ `main`、上流タグ `upstream-1.5.4-utf8`）

```powershell
cd D:\00_project\pukiwiki2026
git pull origin main   # 作業前に同期
# 変更後: git add … → git commit → git push origin main
```

`.env` や `pukiwiki/wiki/`・`pukiwiki/cache/`・`pukiwiki/vendor/pukiwiki-1.5.4_utf8/` 等は `.gitignore` で除外済みです。

## 参考リンク

- 公式サイト: https://pukiwiki.osdn.jp/
- 開発情報: https://pukiwiki.osdn.jp/dev/
- 上流 README: [README.txt](../README.txt)
