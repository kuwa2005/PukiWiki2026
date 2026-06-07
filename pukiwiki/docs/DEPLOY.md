# DEPLOY — デプロイ手順

pukiwiki2026（PukiWiki 1.5.4 ベース）を開発・ステージング・本番へ載せる手順のテンプレートです。  
環境に合わせて値を埋めて使用してください。

---

## 1. 前提条件

**ディレクトリ構成（概要）:**

| 場所 | 内容 |
|------|------|
| **リポジトリ root** | `index.php`、`.htaccess`、`README.md`、`CHANGELOG.md`、`.github/` 等 |
| **`pukiwiki/`** | Wiki 本体＋公式同梱（`README.txt`, `INSTALL.txt`, `COPYING.txt`, `UPDATING.txt`, `*.en.txt.zip`, `wiki.en.zip`）＋ `docs/` / `tools/` |

デプロイ / バックアップ単位は **`index.php` + `pukiwiki/`** 一式です（[BACKUP.md](BACKUP.md) 参照）。

| 項目 | 推奨 |
|------|------|
| PHP | 8.1 以上（上流は 8.1 まで公式確認。要動作確認） |
| 拡張 | mbstring, session, json 等（標準的な Web 用 PHP） |
| Web サーバー | Apache 2.4 または nginx |
| 作業パス | `D:\00_project\pukiwiki2026` |

---

## 2. ローカル開発（Windows 例）

### 2.1 PHP ビルトインサーバー（簡易確認）

```powershell
cd D:\00_project\pukiwiki2026
php -S localhost:8080
```

ブラウザで http://localhost:8080/index.php を開く。

### 2.2 XAMPP / Laragon 等

1. 本フォルダを vhost の DocumentRoot に指定、または `htdocs/pukiwiki2026` へシンボリックリンク。
2. 同梱の `.htaccess` を使う場合は、`httpd.conf` / vhost で `AllowOverride` を有効にする（**任意・推奨** — 詳細は [4.5 `.htaccess`（任意・推奨）](#45-htaccess任意推奨)）。

---

## 3. 初回セットアップ

### 3.1 設定ファイル

```powershell
cd D:\00_project\pukiwiki2026
Copy-Item pukiwiki\pukiwiki.ini.php.example pukiwiki\pukiwiki.ini.php   # 初回のみ
```

`pukiwiki/pukiwiki.ini.php` で最低限確認する項目:

- ページ保存ディレクトリ（通常 `pukiwiki/wiki/` — `DATA_DIR` は `DATA_HOME . 'wiki/'`）
- **`$auth_users`** — 雛形の `editor` / `editor` はデモ用。**必ず変更してから使うこと**
- 管理者パスワード `$adminpass`（凍結解除・添付等）
- タイムゾーン・文字コード（UTF-8）
- 本番ではデバッグ表示を無効化（`index.php` の `error_reporting(0)` が既定。開発時のみ `define('PKWK_DEBUG', 1)` を `index.php` 先頭付近に追加）

#### 初回ログインとパスワード変更

| 項目 | 初期値（デモ用） |
|------|------------------|
| ユーザー名 | `editor` |
| パスワード | `editor` |

> **必ず変更して使うこと。** 初回ログイン時にパスワード変更画面が表示されます。詳細: [SETUP.md](SETUP.md)

パスワードハッシュ生成:

- Web: **`/pukiwiki/tools/gen-password-hash.php`**（セットアップ後は削除または IP 制限）
- CLI: [SETUP.md](SETUP.md) 参照

### 3.2 ディレクトリ権限

Web サーバー実行ユーザーが書き込めること:

| 定数 | パス | 期待 mode（修正時） |
|------|------|---------------------|
| `DATA_DIR` | `pukiwiki/wiki/` | `0777` |
| `DIFF_DIR` | `pukiwiki/diff/` | `0777` |
| `BACKUP_DIR` | `pukiwiki/backup/` | `0777` |
| `CACHE_DIR` | `pukiwiki/cache/` | `0777` |
| `UPLOAD_DIR` | `pukiwiki/attach/` | `0777` |
| `COUNTER_DIR` | `pukiwiki/counter/` | `0777` |

再帰修正時のファイル mode 既定は `0666`（`$perm_file_mode`）。

#### 起動時パーミッションチェック（PukiWiki2026）

Unix/Linux 本番では、Wiki 起動時に上記ディレクトリ**自身の mode のみ**を確認します。

- **許容 mode**（既定）: `0777`, `0775`, `0770` — いずれかなら配下には触れない
- **不適切な場合のみ**: 当該ディレクトリを `$perm_dir_mode`（既定 `0777`）に修正し、配下のディレクトリ・ファイルも再帰的に `$perm_dir_mode` / `$perm_file_mode` へ設定
- **存在しない場合**: `$perm_dir_mode` で `mkdir`（既存の書き込みチェック前）
- **Windows 開発環境**: `PHP_OS_FAMILY` / `DIRECTORY_SEPARATOR` 判定で**自動スキップ**（`chmod` は意味を持たないため）

`pukiwiki/pukiwiki.ini.php` で無効化する例:

```php
$perm_check_on_boot = FALSE;
```

追加のチェック対象（定数名）:

```php
$perm_check_dirs_extra = array(); // 例: カスタム定数を追加
```

```powershell
# Windows（IIS / 特定ユーザー向け例 — 環境に応じて調整）
# icacls wiki /grant "IIS_IUSRS:(OI)(CI)M"
# 注: Windows では起動時 chmod は行われません。ACL / 実行ユーザーの書き込み権を手動で設定してください。
```

### 3.3 動作確認チェックリスト

- [ ] トップページが表示される
- [ ] 新規ページ作成・編集・保存
- [ ] 差分・履歴（該当機能）
- [ ] プラグイン（利用予定分）
- [ ] 添付アップロード（利用する場合）
- [ ] HTTPS（本番）

---

## 4. 本番デプロイ

### 4.1 配置方針

| 方式 | 説明 |
|------|------|
| フルコピー | `index.php` + `pukiwiki/` を rsync / scp / FTP で配置 |
| git pull | 開発環境で `git pull` 後、`index.php` + `pukiwiki/` のみ本番へ同期 |

**配置から除外するもの（またはサーバー側のみ）:**

- `.env`（秘密情報）
- `pukiwiki/wiki/`（本番データ — 初回以外は上書きしない）
- `pukiwiki/cache/`, `pukiwiki/backup/`（再生成可だが運用中は注意）
- `pukiwiki/docs/`, `pukiwiki/tools/`（開発用 — 本番では配置から除外するか、Apache なら `.htaccess` で Web アクセス拒否を**推奨**）

### 4.2 Apache 例（抜粋）

```apache
<VirtualHost *:443>
    ServerName wiki.example.com
    DocumentRoot "D:/00_project/pukiwiki2026"

    <Directory "D:/00_project/pukiwiki2026">
        AllowOverride All
        Require all granted
    </Directory>

    # SSL 設定 ...
</VirtualHost>
```

### 4.3 nginx 例（抜粋）

```nginx
server {
    listen 443 ssl;
    server_name wiki.example.com;
    root /path/to/pukiwiki2026;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

---

### 4.4 本番 PHP エラー表示（SEC-L01）

本番では PHP エラーをブラウザに出さないこと。

| 環境 | 設定 |
|------|------|
| 本番（既定） | `index.php` — `error_reporting(0)`（`PKWK_DEBUG` 未定義時） |
| 開発・デバッグ | `index.php` 先頭付近に `define('PKWK_DEBUG', 1);` を追加 → `error_reporting(E_ALL)` |

サーバー側の `display_errors = Off` も併用すること。詳細は PHP エラーログへ出力する。

---

### 4.5 `.htaccess`（任意・推奨）

**方針:** 同梱の `.htaccess` は**任意**です。Apache で `AllowOverride` が有効な環境では、Web から直接見られたくないディレクトリやファイルへのアクセス拒否に使えます。**無くても Wiki 本体**（`index.php` 経由の表示・編集・保存）は動作します。nginx 等 `.htaccess` 非対応の Web サーバーでは、vhost / `location` 設定で同等の保護を行ってください。

| ファイル | 役割 |
|----------|------|
| **ルート `.htaccess`** | git checkout 全体を DocumentRoot にした**開発環境**向け。`.github/` 等への直接アクセス拒否、`.` 始まり ht 系ファイル（`.htaccess` 等）の直接参照拒否 |
| **`pukiwiki/.htaccess`** | `*.ini.php` / `*.lng.php` / `*.txt` 等の直接参照拒否。`pukiwiki/docs/`, `tools/` への直接アクセス拒否 |
| **`pukiwiki/attach/.htaccess`** 等 | `wiki/`, `cache/`, `backup/`, `attach/` 等のデータディレクトリへの直接 HTTP アクセス拒否（**推奨**。`wiki/.htaccess` は未同梱の場合あり — 下記参照） |
| **`pukiwiki/tools/.htaccess`** | IP 制限の記述例（コメントアウト済み）。本番で `gen-password-hash.php` を残す場合の参考 |

**`wiki/` の保護:** ページソース（`.txt`）が DocumentRoot 配下にある場合、直接 URL で読まれるリスクがあります。Apache では `pukiwiki/wiki/.htaccess` で `Require all denied` を置くことを**推奨**します（リポジトリに未同梱の場合は手動作成）。nginx では例:

```nginx
location ^~ /pukiwiki/wiki/ { deny all; }
location ^~ /pukiwiki/docs/  { deny all; }
location ^~ /pukiwiki/tools/ { deny all; }
```

**配置から除外する方式**（開発用 `docs/` / `tools/` を本番に載せない）でも同等の効果が得られます。`.htaccess` は「載せたまま Web から隠す」ための**追加防御**と考えてください。

---

## 5. リリース後

1. [CHANGELOG.md](../../CHANGELOG.md) にリリース内容を記載
2. キャッシュクリア（必要に応じて `pukiwiki/cache/` 内の生成ファイル削除）
3. バックアップ取得 — [BACKUP.md](BACKUP.md) 参照

```powershell
Copy-Item index.php, pukiwiki -Destination $backup -Recurse
```

---

## 6. ロールバック

1. 直前の `pukiwiki/wiki/`・`pukiwiki/pukiwiki.ini.php` のバックアップを復元
2. 前バージョンの `index.php` + `pukiwiki/` に差し戻し（git tag / アーカイブ）
3. 表示・編集の smoke test

---

## 7. トラブルシューティング

| 症状 | 確認 |
|------|------|
| 500 エラー | PHP エラーログ、`pukiwiki/pukiwiki.ini.php` の syntax |
| 保存できない | `pukiwiki/wiki/` 権限 |
| CSS/JS 404 | `SKIN_DIR` / `IMAGE_DIR` が `pukiwiki/skin/` 等になっているか |
| プラグインエラー | `pukiwiki/plugin/` の PHP 互換、改造差分 |

---

## 8. 関連

- [README.md](../../README.md)
- [SETUP.md](SETUP.md) — 初回ログイン・パスワード変更
- [ARCHITECTURE.md](ARCHITECTURE.md)
- [BACKUP.md](BACKUP.md) — バックアップ・リストア
- 公式 [README.txt](../README.txt) · [INSTALL.txt](../INSTALL.txt)
