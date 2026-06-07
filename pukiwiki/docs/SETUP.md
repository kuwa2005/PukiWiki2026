# SETUP — 初回セットアップ

PukiWiki2026 の初回配置・ログイン・パスワード変更手順です。

## コンセプト

> PukiWiki2026 は公式 PukiWiki 1.5.4 UTF-8 をベースに、認証必須化・CSRF・スパム対策（Akismet/CAPTCHA 等）・セキュリティ監査対応を加えた**非公式 fork** です。

---

## 1. 設定ファイルの準備

```powershell
cd D:\00_project\pukiwiki2026
# 初回のみ: 雛形をコピー（既に pukiwiki/pukiwiki.ini.php がある場合はスキップ）
Copy-Item pukiwiki\pukiwiki.ini.php.example pukiwiki\pukiwiki.ini.php
```

`pukiwiki/pukiwiki.ini.php` は git 管理外です。初回は `pukiwiki/pukiwiki.ini.php.example` をコピーし、環境に合わせて調整してください。

添付ファイルの上限は `pukiwiki/pukiwiki.ini.php` の **`$attach_max_filesize`**（**バイト**、雛形既定 **2GB** = `2 * 1024 * 1024 * 1024`）で変更します。PukiWiki 設定だけでは PHP / Web サーバーの制限は超えられません — **`upload_max_filesize`** と **`post_max_size`**（php.ini 等）および nginx の **`client_max_body_size`** 等も同上限以上にしてください。

---

## 2. 初回ログイン（デモ用アカウント）

| 項目 | 値 |
|------|-----|
| ユーザー名 | `editor` |
| パスワード | `editor` |

`pukiwiki.ini.php.example` およびローカル `pukiwiki.ini.php` には、上記の平文パスワード（デモ用）が既定で入っています。ハッシュ形式（`{x-php-sha256}...` 等）でも設定可能です（[方法 B](#方法-b-web-支援スクリプト手動変更) 参照）。

> **警告:** `editor` / `editor` は**デモ・動作確認用の初期値**です。**必ず変更してから使ってください。** 本番公開・インターネット公開前にも必ずパスワードを変更してください。変更しないまま公開すると第三者に編集され得ます。

ログイン手順:

1. ブラウザで Wiki を開く
2. 任意ページの「編集」をクリック（未ログイン時はログインフォームへ）
3. または `?plugin=loginform` を開く
4. ユーザー名 `editor`、パスワード `editor` でログイン
5. **初回ログイン時はパスワード変更画面**（`?plugin=changepassword`）が表示されます。新しいパスワードを設定するまで Wiki の他操作はできません

---

## 3. パスワードの変更

### 方法 A: 初回ログイン時の強制変更（推奨）

1. 上記のデモアカウントでログイン
2. 表示される **パスワード変更（必須）** 画面で新しいパスワードを入力（8 文字以上、`editor` は不可）
3. 成功すると `pukiwiki.ini.php` の `$auth_users['editor']` が自動更新されます（ファイルが書き込み可能な場合）
4. 自動更新できない場合は、画面に表示されたハッシュを手動で ini に反映してください

### 方法 B: Web 支援スクリプト（手動変更）

1. ブラウザで **`/pukiwiki/tools/gen-password-hash.php`** を開く  
   例: `http://localhost:8080/pukiwiki/tools/gen-password-hash.php`
2. 新しい平文パスワードを入力
3. ハッシュ方式を選択（通常は `{x-php-sha256}` または `{x-php-password}`）
4. 生成された文字列をコピー
5. `pukiwiki/pukiwiki.ini.php` の `$auth_users` を更新:

   ```php
   $auth_users = array(
       'editor' => '{x-php-sha256}新しいハッシュ...',
   );
   ```

6. **本番公開後は `pukiwiki/tools/gen-password-hash.php` を削除**するか、`pukiwiki/tools/.htaccess` 等で IP 制限する（[tools/README.md](../tools/README.md)）

### 方法 C: PHP CLI

```bash
php -r "echo '{x-php-sha256}' . hash('sha256', 'your-new-password') . PHP_EOL;"
```

`{x-php-password}`（`password_hash`）を使う場合:

```bash
php -r "echo '{x-php-password}' . password_hash('your-new-password', PASSWORD_DEFAULT) . PHP_EOL;"
```

### 管理者パスワード（`$adminpass`）

**`$edit_auth` とフォームログインが有効な構成では、ログイン済みユーザーは `$adminpass` の再入力は不要です**（凍結・凍結解除、rename、添付の管理者操作、diff/backup 削除、dump 等）。`$adminpass` は未ログイン時のレガシー手段および `$edit_auth` 無効環境向けに `pukiwiki.ini.php` に残します。

デモ用初期値は `$adminpass = 'editor'`（平文）です。本番では [方法 B](#方法-b-web-支援スクリプト手動変更) と同様にハッシュを生成し、`pukiwiki/pukiwiki.ini.php` の `$adminpass` に設定してください（`$edit_auth` 無効時や API 連携用）。

### ini 自動更新の制限

- `pukiwiki/pukiwiki.ini.php` が **書き込み可能** な場合のみ、強制変更 UI から `$auth_users` の該当行を更新します
- 更新対象はログイン中ユーザーの hash 行のみ（ファイル全体の上書きはしません）
- 書き込み不可の場合はハッシュを画面表示し、[方法 B](#方法-b-web-支援スクリプト手動変更) の手順を案内します

---

## 4. 動作確認

- [ ] ログアウト状態で編集 → ログイン画面へ誘導される
- [ ] 初回ログイン（`editor` / `editor`）→ パスワード変更画面へ誘導される
- [ ] 新パスワード設定後に Wiki の閲覧・編集ができる
- [ ] 旧パスワード `editor` ではログインできない
- [ ] ログイン後にページ編集・保存できる
- [ ] 匿名のままページ閲覧できる

詳細なテスト観点: [ANTI-SPAM.md](./ANTI-SPAM.md)

---

## 5. ディレクトリパーミッション（Unix/Linux 本番）

PukiWiki2026 は Unix/Linux 本番環境で、Wiki 起動時に書き込みディレクトリ**自身の mode のみ**を確認します。不適切な場合のみ chmod と配下の再帰修正を行います（全件無条件チェックはしません）。Windows 開発環境では自動スキップされます。

`pukiwiki/pukiwiki.ini.php` で無効化する例:

```php
$perm_check_on_boot = FALSE;
```

その他の設定（修正 mode・許容 mode・追加チェック対象）は `pukiwiki/pukiwiki.ini.php.example` を参照。詳細: [DEPLOY.md §3.2](./DEPLOY.md#32-パーミッション)

---

## 6. スキン（任意）

カスタムスキンは `pukiwiki/pukiwiki.ini.php` の **`SKIN_DIR`** と **`SKIN_FILE`** で指定します。PukiWiki2026 が提供するのはこの設定のみで、1.5.4 由来スキンのパス修正は利用者側の責務です。

- サブディレクトリ skin（modernskin / bluebox 等）では **`SKIN_FILE` も必須**
- 詳細: [PUKIWIKI154-SKIN.md](./PUKIWIKI154-SKIN.md)
- 任意（Apache のみ）: [mod_rewrite で legacy `skin/` パスを吸収](./PUKIWIKI154-SKIN.md#8-任意-apache-mod_rewrite-で-legacy-skin-パスを吸収)

---

## 7. 関連ドキュメント

- [DEPLOY.md](./DEPLOY.md) — デプロイ・ディレクトリ権限
- [PUKIWIKI154-SKIN.md](./PUKIWIKI154-SKIN.md) — PukiWiki 1.5.4 スキンのパス修正
- [ANTI-SPAM.md](./ANTI-SPAM.md) — 編集認証・スパム対策
- [tools/README.md](../tools/README.md) — 支援ツールのセキュリティ注意
- 公式 [INSTALL.txt](../INSTALL.txt) — インストール・アップグレード手順（上流同梱）
- 公式 [README.txt](../README.txt) — PukiWiki 1.5.4 同梱説明
