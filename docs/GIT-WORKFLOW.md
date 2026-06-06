# GIT-WORKFLOW — Git 運用ガイド

PukiWiki 2026 リポジトリ（https://github.com/kuwa2005/PukiWiki2026）でのブランチ・コミット・PR の運用方針。

## こまめなローカル commit

### なぜこまめに commit するか

- 大規模改造中でも、**いつでも安全に戻れる**チェックポイントを残せる
- 機能単位・修正単位で履歴が分かれ、後から `git log` / `git blame` で追いやすい
- PR レビュー時に、変更の意図が commit ごとに読み取りやすい

### 粒度の目安

| タイミング | 例 |
|------------|-----|
| 1 機能・1 修正が動いた | プラグイン追加、バグ修正、設定項目の追加 |
| リファクタの区切り | 関数分割、命名統一、ファイル移動（動作は変えない） |
| ドキュメント単位 | `docs/` や `CHANGELOG.md` のまとまり |
| 避ける | 無関係な変更の混在、動かない途中状態の長期放置 |

**push は commit とは別。** ローカル commit を積み、push はユーザー指示または PR 作成時に行う。

## ブランチ運用

| ブランチ | 用途 |
|----------|------|
| `main` | 安定版。小さな doc 修正のみ直接 commit 可 |
| `feature/*` | 機能追加・改造（推奨） |
| `fix/*` | バグ修正 |
| `docs/*` | ドキュメントのみの変更（任意） |

```powershell
cd D:\00_project\pukiwiki2026
git checkout -b feature/my-change
# 作業 … こまめに commit
git push -u origin feature/my-change
```

**禁止:** `git push --force`（`main` への force push も含む）

**merge 後:** PR が `main` に取り込まれたら、対応する feature ブランチはローカル・リモートとも削除する（`git branch -d <branch>`、`git push origin --delete <branch>`）。

### main の branch protection

GitHub 上の `main` は branch protection 済み（force push 禁止・ブランチ削除禁止・admin もルール適用）。CI 未導入のため status check は未要求。機能改造は `feature/*` → PR → `main` を推奨。

## コミットメッセージ

- **日本語可**
- 1 行目: 何をしたか（簡潔）
- 必要なら本文: なぜ・背景

```
認証プラグインにセッションタイムアウトを追加

ログイン状態が長時間維持されセキュリティリスクがあったため、
30 分無操作でセッションを破棄する。
```

## PR 作成手順

1. トピックブランチで作業し、ローカル commit を積む
2. リモートへ push（初回は `-u origin <ブランチ名>`）
3. `gh pr create` で PR を作成（**タイトル・本文・Test plan はすべて日本語**）

### テンプレ例

```powershell
gh pr create --title "〇〇機能を追加" --body "$(@'
## 概要
- 〇〇のために △△ を実装した
- 既存の □□ 動作には影響しない

## 変更内容
- `lib/xxx.php`: …
- `plugin/yyy/`: …

## Test plan
- [ ] ローカルでログイン〜ページ編集ができる
- [ ] プラグイン有効時に期待どおり表示される
- [ ] 既存ページの表示に regress がない

'@)"
```

## タグ `upstream-1.5.4-utf8` との関係

| 項目 | 内容 |
|------|------|
| タグ名 | `upstream-1.5.4-utf8` |
| 意味 | 公式 PukiWiki **1.5.4 UTF-8** 相当の pristine 状態をマークした参照点 |
| 用途 | 改造 diff の基準、上流との比較起点 |

```powershell
# タグの内容を確認
git show upstream-1.5.4-utf8 --stat

# 現在の main との差分（例）
git diff upstream-1.5.4-utf8..main --stat
```

公式配布物との diff 方針・`vendor/` の扱いは [UPSTREAM.md](./UPSTREAM.md) を参照。

## ロールバック例

### 直前の commit を打ち消す（履歴を残す）

```powershell
git revert HEAD
```

### 特定の commit を打ち消す

```powershell
git log --oneline -10
git revert <commit-hash>
```

### 未 commit の作業ファイルを捨てる（注意: 復元不可）

```powershell
# 特定ファイルのみ
git checkout -- path/to/file.php

# 追跡ファイルの変更をすべて破棄（未追跡ファイルは残る）
git checkout -- .
```

### 直前の commit を取り消してやり直す（**未 push のみ**）

```powershell
git reset --soft HEAD~1
```

`--soft` なら変更はステージに残る。既に push した commit には `revert` を使う。

## 関連ドキュメント

- [UPSTREAM.md](./UPSTREAM.md) — 公式との関係・GPL
- [CHANGELOG.md](../CHANGELOG.md) — 変更履歴
- ワークスペース全体: `AGENTS.md`（PukiWiki セクション）、`.cursor/rules/pukiwiki2026.mdc`
