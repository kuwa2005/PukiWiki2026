# BACKUP — バックアップ・リストア

PukiWiki2026 の Wiki 運用データと本体ファイルのバックアップ手順です。

---

## 1. バックアップ単位

**デプロイ / バックアップに含めるもの（最小構成）:**

| パス | 内容 |
|------|------|
| `index.php` | エントリポイント |
| `pukiwiki/` | Wiki 本体（lib, plugin, skin, image, ini, wiki, cache, attach 等） |
| `pukiwiki/docs/` | 設計・デプロイ文書（`pukiwiki/` に含まれる） |
| `pukiwiki/tools/` | セットアップ支援（`pukiwiki/` に含まれる） |
| `pukiwiki/vendor/` | 上流参照用コピー（`pukiwiki/` に含まれる） |
| `pukiwiki/patches/` | パッチ保管（`pukiwiki/` に含まれる） |
| `pukiwiki/README.md`, `pukiwiki/CHANGELOG.md` | プロジェクト文書 |

**含めないもの（git リポジトリ root のみ）:**

- `.github/` — CI 設定
- `.gitignore` 等の git 管理ファイル

---

## 2. バックアップ取得（PowerShell）

```powershell
$src = "D:\00_project\pukiwiki2026"
$backup = "D:\backup\pukiwiki2026-$(Get-Date -Format 'yyyyMMdd-HHmmss')"
New-Item -ItemType Directory -Path $backup -Force | Out-Null
Copy-Item "$src\index.php", "$src\pukiwiki" -Destination $backup -Recurse
Write-Host "Backed up to $backup"
```

### 2.1 運用上の注意

- **`pukiwiki/pukiwiki.ini.php`** — 認証情報を含むため、バックアップ先のアクセス権限を厳格にすること
- **`pukiwiki/wiki/`** — ページ本文。最重要データ
- **`pukiwiki/attach/`** — 添付ファイル（利用時）
- **`pukiwiki/cache/`** — 再生成可能だが、運用中は差分に注意
- **`pukiwiki/backup/`** — PukiWiki 内部のページ履歴バックアップ

---

## 3. リストア

1. Web サーバーを停止するか、メンテナンスモードにする
2. 現行の `index.php` と `pukiwiki/` を退避（任意）
3. バックアップから `index.php` と `pukiwiki/` を配置先へコピー
4. `pukiwiki/wiki/`・`pukiwiki/cache/` 等の書き込み権限を確認
5. ブラウザで `index.php` を開き、表示・編集の smoke test

```powershell
$backup = "D:\backup\pukiwiki2026-20260607-120000"
$dest = "D:\00_project\pukiwiki2026"
Copy-Item "$backup\index.php" -Destination $dest -Force
Copy-Item "$backup\pukiwiki" -Destination $dest -Recurse -Force
```

---

## 4. 定期バックアップの推奨

| 頻度 | 対象 |
|------|------|
| 毎日 | `pukiwiki/wiki/`, `pukiwiki/attach/` |
| 週次 | `index.php` + `pukiwiki/` 一式 |
| リリース前後 | 上記 + 設定ファイルのスナップショット |

---

## 5. 関連

- [DEPLOY.md](DEPLOY.md) — デプロイ手順
- [ARCHITECTURE.md](ARCHITECTURE.md) — ディレクトリ構成
- [SETUP.md](SETUP.md) — 初回セットアップ
