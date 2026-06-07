# tools/ — セットアップ支援ツール

PukiWiki2026 の初回セットアップ・運用補助用スクリプトを置きます。

## gen-password-hash.php

`$auth_users` および `$adminpass` 用のハッシュ文字列を Web フォームから生成します。

| 項目 | 内容 |
|------|------|
| URL 例 | `https://your-wiki.example/pukiwiki/tools/gen-password-hash.php` |
| 用途 | 開発環境・初回セットアップ時のパスワードハッシュ作成 |
| 保存 | 平文パスワードはファイルに書き込みません |

### セキュリティ（必読）

1. **本番公開後は削除**するか、Web から到達できないようにする
2. Apache 利用時は `tools/.htaccess` の IP 制限例を参考にアクセスを限定する
3. nginx 等では `location` で `deny all` または社内 IP のみ許可する
4. 初期ユーザー `editor` / パスワード `pass` は**デモ用**。公開前に必ず変更する（[docs/SETUP.md](../docs/SETUP.md)）

### CLI での代替

```bash
php -r "echo '{x-php-sha256}' . hash('sha256', 'your-password') . PHP_EOL;"
```

`{x-php-password}` の場合:

```bash
php -r "echo '{x-php-password}' . password_hash('your-password', PASSWORD_DEFAULT) . PHP_EOL;"
```
