# pukiwiki2026

**非公式フォーク** — PukiWiki 1.5.4 UTF-8 をベースにした大規模改造用の作業リポジトリです。  
公式 PukiWiki Development Team の配布物ではありません。

| 項目 | 内容 |
|------|------|
| ベース | [PukiWiki](https://pukiwiki.osdn.jp/) **1.5.4 UTF-8** |
| ライセンス | **GPL v2** または（あなたの選択で）それ以降の GPL（上流に準拠） |
| 作業フォルダ | `D:\00_project\pukiwiki2026` |

## ディレクトリ構成

```
pukiwiki2026/
├── README.md          … 本ファイル
├── CHANGELOG.md       … 改造履歴
├── docs/              … 設計・デプロイ・上流メモ
├── vendor/            … 未改造の上流参照用（任意）
├── patches/           … パッチファイル置き場（任意）
├── lib/, plugin/, …   … 作業中の PukiWiki 本体（改造対象）
├── wiki/              … ページデータ（運用時に生成）
├── cache/, backup/    … ランタイム生成物
└── .gitignore
```

ルート直下にはすでに PukiWiki 1.5.4 のソースが配置されています（`lib/init.php` の `S_VERSION` を参照）。  
`vendor/` には**未改造の公式配布物**を別途置き、diff の基準に使います。

## クイックスタート

1. Web サーバー（Apache / nginx + PHP 8.x 推奨）のドキュメントルート、または仮想ホストで本フォルダを公開する。
2. `pukiwiki.ini.php` を環境に合わせて編集する（初回は `pukiwiki.ini.php` の雛形をコピー）。
3. `wiki/`・`cache/`・`backup/` に書き込み権限を付与する。
4. ブラウザで `index.php` にアクセスし、初期ページが表示されることを確認する。

詳細は [docs/DEPLOY.md](docs/DEPLOY.md) を参照。

## 改造の進め方

- 上流との差分方針: [docs/UPSTREAM.md](docs/UPSTREAM.md)
- 設計メモ: [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)
- 変更履歴: [CHANGELOG.md](CHANGELOG.md)

## バージョン管理

本フォルダは **git 未初期化** の状態で始められます。履歴管理が必要な場合は、作業者の判断で初期化してください。

```powershell
cd D:\00_project\pukiwiki2026
git init
```

`.env` や `wiki/`・`cache/` 等は `.gitignore` で除外済みです。

## 参考リンク

- 公式サイト: https://pukiwiki.osdn.jp/
- 開発情報: https://pukiwiki.osdn.jp/dev/
- 上流 README: [README.txt](README.txt)
