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

## 本リポジトリとの関係

| 場所 | 内容 |
|------|------|
| **git タグ `upstream-1.5.4-utf8`** | 公式 PukiWiki 1.5.4 UTF-8 の**未改造スナップショット**（diff 基準） |
| **ルート直下** | `index.php`、`.htaccess`、`README.md`、`CHANGELOG.md`、`.github/` 等 |
| **`pukiwiki/`** | 改造済み Wiki 本体（`lib/`、`plugin/`、`docs/`、`tools/` 等） |

ローカルに `vendor/` ディレクトリを置く必要はありません。公式との比較は git タグを使います。

## diff 方針

### 目的

改造箇所を公式との差分として把握し、上流更新時のマージコストを下げる。

### 推奨ワークフロー

1. 作業前にタグが存在することを確認する。

```powershell
git tag -l upstream-*
git show upstream-1.5.4-utf8 --stat
```

2. 改造ディレクトリごとに diff を取る（タグ側は公式 1.5.4 のルート構成、`pukiwiki/` 側は本 fork）。

```powershell
# lib のみ
git diff upstream-1.5.4-utf8:lib -- pukiwiki/lib

# plugin
git diff upstream-1.5.4-utf8:plugin -- pukiwiki/plugin

# skin
git diff upstream-1.5.4-utf8:skin -- pukiwiki/skin
```

3. 全体をファイル比較ツールで見る場合は、タグを一時展開して `--no-index` で比較する。

```powershell
$tmp = Join-Path $env:TEMP "upstream-1.5.4-utf8"
git archive upstream-1.5.4-utf8 | tar -x -C $tmp
git diff --no-index $tmp pukiwiki/
```

4. 変更が大きい場合は `CHANGELOG.md`（リポジトリ root）に概要を書く。
5. 公式 1.5.5 等へ追従する場合は、新バージョン用の upstream タグを追加し、上記 diff で当て直し可否を確認する。

### 改造時の原則

- **GPL 遵守**: 配布・公開する場合はソース開示義務に留意する。
- **最小差分**: 公式ファイルは必要な箇所だけ変更し、理由を `docs/ARCHITECTURE.md` に残す。
- **プラグイン分離**: 可能なら `plugin/` への追加で済ませ、コア改変は計画的に行う。
- **設定分離**: 環境依存値は `pukiwiki/pukiwiki.ini.php`（git 除外推奨）や `.env` に寄せる。

## 上流 README

公式同梱の説明はルートの [README.txt](../../README.txt) を参照。
