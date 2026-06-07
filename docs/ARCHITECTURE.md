# ARCHITECTURE — 大規模改造 設計メモ

本ドキュメントは pukiwiki2026 における**非公式・大規模改造**の設計判断を記録するテンプレートです。  
実装に合わせて随時更新してください。

---

## 1. 概要

| 項目 | 内容 |
|------|------|
| プロジェクト名 | pukiwiki2026 |
| ベース | PukiWiki 1.5.4 UTF-8 |
| 目的 | （例: 社内 Wiki / 公開サイト / プラグイン拡張基盤 など） |
| 対象 PHP | （例: 8.1 〜 8.3） |
| 対象 Web サーバー | （例: Apache 2.4 + mod_php / nginx + php-fpm） |

### 1.1 改造のスコープ

- [ ] コア（`lib/`）改修
- [ ] 既存プラグイン改修（`plugin/`）
- [ ] 新規プラグイン追加
- [ ] スキン・フロント（`skin/`）
- [ ] 認証・権限（`lib/auth.php` 周辺）
- [ ] 外部連携（API / SSO / Webhook 等）
- [ ] その他: _______________

### 1.2 非スコープ（やらないこと）

- （例: 公式互換の 100% 維持は求めない / モバイル専用 skin は対象外 等）

---

## 2. 現状アーキテクチャ（PukiWiki 1.5.4 + pukiwiki/ 集約）

```
Browser
   │
   ▼
index.php（DATA_HOME = ./pukiwiki/）
   │
   └── pukiwiki/
         ├── lib/init.php（bootstrap, S_VERSION）
         │      ├── pukiwiki.ini.php
         │      └── lib/pukiwiki.php（メイン処理）
         ├── plugin/*.inc.php（機能拡張）
         ├── skin/*.skin.php（表示）
         └── wiki/*.txt（ページ本文・データ）
```

**デプロイ / バックアップ単位:** リポジトリ root の `index.php` と `pukiwiki/` ディレクトリのみ。  
`docs/`, `tools/`, `vendor/` 等は開発用でバックアップ対象外。

### 2.1 主要ディレクトリ

| パス | 役割 |
|------|------|
| `index.php` | エントリポイント（`DATA_HOME` 定義のみ） |
| `pukiwiki/lib/` | コアライブラリ、Wiki エンジン |
| `pukiwiki/plugin/` | プラグイン（`plugin=xxx` で呼び出し） |
| `pukiwiki/skin/` | 表示テンプレート・CSS |
| `pukiwiki/wiki/` | ページデータ（テキスト） |
| `pukiwiki/attach/` | 添付ファイル |
| `pukiwiki/cache/` | キャッシュ（ランタイム） |
| `pukiwiki/backup/` | ページバックアップ（ランタイム） |
| `docs/` | 設計・デプロイ文書（開発用） |
| `tools/` | セットアップ支援（開発用） |

---

## 3. 改造方針

### 3.1 レイヤリング

| レイヤ | 方針 | 備考 |
|--------|------|------|
| 設定 | `pukiwiki/pukiwiki.ini.php` / `.env` に集約 | 秘密情報は git 除外 |
| 拡張 | 新規は `pukiwiki/plugin/` 優先 | コア触る理由を必ず記載 |
| 表示 | `pukiwiki/skin/` または専用 CSS | |
| データ | `pukiwiki/wiki/` 構造変更は慎重に | マイグレーション手順を別途 |

### 3.2 互換性

- 上流プラグイン互換: （維持 / 部分 / 破棄）
- URL 形式: （デフォルト `index.php?` を維持 等）
- 文字コード: UTF-8 固定

---

## 4. 機能別設計（記入用）

### 4.1 機能 A: _______________

- **要件**:
- **変更ファイル**:
- **API / データ**:
- **セキュリティ**:
- **テスト観点**:

### 4.2 機能 B: _______________

（同上）

---

## 5. 認証・セキュリティ

| 項目 | 現状 / 予定 |
|------|-------------|
| 編集権限 | （Basic 認証 / セッション / カスタム） |
| CSRF 対策 | PukiWiki 標準 + （追加施策） |
| ファイルアップロード | `attach/` 制限 |
| 本番設定 | `lib/init.php` のデバッグ表示オフ等 |

---

## 6. パフォーマンス・運用

- キャッシュ戦略: （`cache/` / OPcache / 逆プロキシ）
- バックアップ: [BACKUP.md](BACKUP.md) — `index.php` + `pukiwiki/` のコピー
- ログ: （Web サーバー / アプリログ）

---

## 7. 決定ログ（ADR 簡易版）

| 日付 | 決定 | 理由 | 代替案 |
|------|------|------|--------|
| YYYY-MM-DD | （例: vendor に pristine を置く） | diff 基準の明確化 | submodule は使わない |
| | | | |

---

## 8. 関連ドキュメント

- [UPSTREAM.md](UPSTREAM.md) — 上流取得・diff
- [DEPLOY.md](DEPLOY.md) — デプロイ手順
- [BACKUP.md](BACKUP.md) — バックアップ・リストア
- [CHANGELOG.md](../CHANGELOG.md) — 変更履歴
