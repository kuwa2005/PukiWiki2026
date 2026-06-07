# UPSTREAM — 上流（公式）との関係

## ベースバージョン

| 項目 | 値 |
|------|-----|
| 名称 | PukiWiki |
| バージョン | **1.5.4** |
| 文字コード | **UTF-8** 版 |
| ライセンス | GPL v2 or later |
| 確認箇所 | `pukiwiki/lib/init.php` の `S_VERSION` |

## 公式配布元

- トップ: https://pukiwiki.osdn.jp/
- 開発・ダウンロード: https://pukiwiki.osdn.jp/dev/
- OSDN プロジェクト: https://osdn.jp/projects/pukiwiki/

## 取得方法

### 1. 公式サイトからアーカイブを取得

1. [開発・ダウンロード](https://pukiwiki.osdn.jp/dev/) から **pukiwiki-1.5.4_utf8**（または同等の UTF-8 配布物）をダウンロードする。
2. 展開した内容を `pukiwiki/vendor/pukiwiki-1.5.4_utf8/` に配置する（未改造の参照用コピー）。

```powershell
# 例: vendor 配下に展開済みアーカイブがある場合
Expand-Archive -Path .\downloads\pukiwiki-1.5.4_utf8.zip -DestinationPath .\pukiwiki\vendor\pukiwiki-1.5.4_utf8
```

### 2. 本リポジトリのルートとの関係

- **ルート直下** … `index.php`、`.github/`、`.gitignore` 等（git / CI 用）
- **`pukiwiki/`** … 実際に改造・デプロイ・バックアップする Wiki 本体（`docs/`・`tools/`・`vendor/`・`patches/` を含む）
- **`pukiwiki/vendor/`** … diff 比較用の** pristine（未改造）** コピー。git には `.gitkeep` のみ追跡し、実体はローカル任意（`.gitignore` 参照）

## diff 方針

### 目的

改造箇所を公式との差分として把握し、上流更新時のマージコストを下げる。

### 推奨ワークフロー

1. `pukiwiki/vendor/pukiwiki-1.5.4_utf8/` を公式配布物で更新する（改造しない）。
2. 作業ツリー（`pukiwiki/` の lib 等）との diff を取る。

```powershell
# PowerShell（git 利用時）
git diff --no-index pukiwiki/vendor/pukiwiki-1.5.4_utf8 pukiwiki/

# lib のみ比較
git diff --no-index pukiwiki/vendor/pukiwiki-1.5.4_utf8/lib pukiwiki/lib

# または diff ツール
# WinMerge / Beyond Compare 等で vendor と pukiwiki/ を比較
```

3. 変更が大きい場合は `pukiwiki/patches/` にパッチファイル（`.patch` / `.diff`）として保存し、`pukiwiki/CHANGELOG.md` に概要を書く。
4. 公式 1.5.5 等へ追従する場合は、まず `pukiwiki/vendor/` を新バージョンで更新し、パッチの当て直し可否を確認する。

### 改造時の原則

- **GPL 遵守**: 配布・公開する場合はソース開示義務に留意する。
- **最小差分**: 公式ファイルは必要な箇所だけ変更し、理由を `pukiwiki/docs/ARCHITECTURE.md` に残す。
- **プラグイン分離**: 可能なら `plugin/` への追加で済ませ、コア改変は計画的に行う。
- **設定分離**: 環境依存値は `pukiwiki/pukiwiki.ini.php`（git 除外推奨）や `.env` に寄せる。

## 上流 README

公式同梱の説明はルートの [README.txt](../../README.txt) を参照。
