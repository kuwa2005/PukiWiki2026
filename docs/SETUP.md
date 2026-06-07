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

`pukiwiki/pukiwiki.ini.php` は git 管理外です。`pukiwiki/pukiwiki.ini.php.example` の内容を環境に合わせて反映してください。

---

## 2. 初回ログイン（デモ用アカウント）

| 項目 | 値 |
|------|-----|
| ユーザー名 | `editor` |
| パスワード | `pass` |

`pukiwiki.ini.php.example` およびローカル雛形には、上記の SHA-256 ハッシュが既定で入っています。

> **警告:** `editor` / `pass` は**デモ・動作確認用**です。**本番公開・インターネット公開前に必ずパスワードを変更**してください。変更しないまま公開すると第三者に編集され得ます。

ログイン手順:

1. ブラウザで Wiki を開く
2. 任意ページの「編集」をクリック（未ログイン時はログインフォームへ）
3. または `?plugin=loginform` を開く
4. ユーザー名 `editor`、パスワード `pass` でログイン

---

## 3. パスワードの変更

### 方法 A: Web 支援スクリプト（推奨・初回セットアップ時）

1. ブラウザで **`/tools/gen-password-hash.php`** を開く  
   例: `http://localhost:8080/tools/gen-password-hash.php`
2. 新しい平文パスワードを入力
3. ハッシュ方式を選択（通常は `{x-php-sha256}`）
4. 生成された文字列をコピー
5. `pukiwiki/pukiwiki.ini.php` の `$auth_users` を更新:

   ```php
   $auth_users = array(
       'editor' => '{x-php-sha256}新しいハッシュ...',
   );
   ```

6. **本番公開後は `tools/gen-password-hash.php` を削除**するか、`tools/.htaccess` 等で IP 制限する（[tools/README.md](../tools/README.md)）

### 方法 B: PHP CLI

```bash
php -r "echo '{x-php-sha256}' . hash('sha256', 'your-new-password') . PHP_EOL;"
```

`{x-php-password}`（`password_hash`）を使う場合:

```bash
php -r "echo '{x-php-password}' . password_hash('your-new-password', PASSWORD_DEFAULT) . PHP_EOL;"
```

### 管理者パスワード（`$adminpass`）

凍結解除・添付アップロード等に使用します。同様にハッシュを生成し、`pukiwiki/pukiwiki.ini.php` の `$adminpass` に設定してください（初期の `{x-php-md5}!` のままでは管理者操作ができません）。

---

## 4. 動作確認

- [ ] ログアウト状態で編集 → ログイン画面へ誘導される
- [ ] 新パスワードで `editor` がログインできる
- [ ] 旧パスワード `pass` ではログインできない
- [ ] ログイン後にページ編集・保存できる
- [ ] 匿名のままページ閲覧できる

詳細なテスト観点: [ANTI-SPAM.md](./ANTI-SPAM.md)

---

## 5. 関連ドキュメント

- [DEPLOY.md](./DEPLOY.md) — デプロイ・ディレクトリ権限
- [ANTI-SPAM.md](./ANTI-SPAM.md) — 編集認証・スパム対策
- [tools/README.md](../tools/README.md) — 支援ツールのセキュリティ注意
