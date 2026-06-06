# ANTI-SPAM — 匿名編集スパム対策

PukiWiki2026 で採用しているスパム対策の概要・設定・運用手順です。

## 方針

| 項目 | 内容 |
|------|------|
| 第一目的 | **匿名による書き込みを止める** |
| 閲覧 | 匿名 OK（`$read_auth = 0` のまま） |
| 編集 | **ログイン必須**（`$edit_auth = 1`） |
| 実装方針 | PukiWiki 標準の認証機構を活用した最小差分 |

CAPTCHA プラグインは公式 1.5.4 には含まれないため、即効性の高い「編集認証（フォームログイン）」を採用しています。

---

## 採用した対策（A: 即効性）

### 1. 編集認証の有効化（`$edit_auth`）

`pukiwiki.ini.php` で次を設定しています。

```php
$auth_type = AUTH_TYPE_FORM;
$edit_auth = 1;
$edit_auth_pages = array(
    '#.*#' => 'valid-user',  // 全ページ: 認証済みユーザーのみ編集可
);
$read_auth = 0;  // 閲覧は匿名可（変更なし）
```

- 匿名ユーザーが `cmd=edit` 等にアクセスすると **ログインフォームへリダイレクト** されます（`plugin/loginform`）。
- ロック（`#freeze`）ページは従来どおり編集不可です。

### 2. ゲスト投稿プラグインへの認証チェック

`check_editable()` を通さず `page_write()` だけを呼んでいたプラグインに認証チェックを追加しました。

| プラグイン | 用途 |
|-----------|------|
| `comment` | コメント欄 |
| `memo` | メモ欄 |
| `insert` | テキスト挿入欄 |
| `vote` | 投票欄 |
| `article` | 掲示板風投稿 |
| `paint` | お絵描き欄 |

`edit` / `add` / `pcomment` / `tracker` / `bugtrack` / `attach`（一部）はもともと `check_editable()` を使用しています。

### 3. `page_write()` への防御層

`lib/file.php` の `page_write()` で `is_page_writable()` を確認し、認証をバイパスする経路からの書き込みをブロックします。拒否時は `ensure_page_writable()` 経由でログイン画面へ誘導します。

### 4. リクエスト早期ゲート（POST 直叩き対策）

`lib/pukiwiki.php` でプラグイン実行前に `enforce_edit_auth_for_request()` を呼び出し、未認証の変更系リクエストを **共通フック 1 箇所** で遮断します（実装: `lib/auth.php`）。

| 種別 | 動作 |
|------|------|
| 未認証 POST（`?write=` / `?plugin=comment` 等） | ログインフォームへリダイレクト（`AUTH_TYPE_FORM` 時） |
| 未認証 GET `cmd=edit` 等 | 編集フォームを表示せずログイン誘導 |
| 未認証 GET 閲覧 | 従来どおり許可 |
| 例外 | `plugin=search` / `plugin=loginform` / `plugin=saml` の POST、`attach` の download/list 等 |

ページロック（`#freeze`）に依存せず、`$edit_auth` と `valid-user` グループで **グローバルに編集権限を強制** します。

---

## 本番での有効化手順

### 1. 設定ファイルの確認

`pukiwiki.ini.php`（本番では環境ごとにコピー・調整。雛形は `pukiwiki.ini.php.example`）で以下を確認してください。

1. **`$auth_users` に編集者を1件以上登録**（空のままでは誰もログインできません）

   ```php
   $auth_users = array(
       'editor' => '{x-php-sha256}YOUR_HASH_HERE',
   );
   ```

   ハッシュ生成例（PHP CLI）:

   ```bash
   php -r "echo '{x-php-sha256}' . hash('sha256', 'your-password') . PHP_EOL;"
   ```

2. **`$adminpass` を設定**（凍結解除・管理者操作・添付アップロード等に使用）

   ```php
   $adminpass = '{x-php-sha256}YOUR_ADMIN_HASH';
   ```

3. **`$edit_auth = 1`** および **`$edit_auth_pages`** が上記のとおりであること

### 2. 動作確認

1. ログアウト状態で任意ページの「編集」をクリック → ログイン画面へ遷移すること
2. `?plugin=loginform` で登録ユーザーがログインできること
3. ログイン後に編集・保存できること
4. 匿名のままページ閲覧ができること
5. 未認証で `curl -X POST` による編集 payload を送っても保存されないこと（ログイン誘導または拒否）

#### テスト観点（手動）

| # | 操作 | 期待結果 |
|---|------|----------|
| 1 | 未認証 GET `?FrontPage` | 閲覧 OK |
| 2 | 未認証 GET `?FrontPage&cmd=edit` | 拒否（ログイン誘導） |
| 3 | 未認証 POST 編集 payload | 拒否（保存されない） |
| 4 | 認証後 `cmd=edit` → 保存 | OK |
| 5 | ロックページでも未認証は編集不可 | 拒否 |

### 3. 既存スパムページの削除

1. スパム疑いページを一覧（`list` プラグイン等）または `wiki/` ディレクトリで特定
2. 管理者としてログインし、該当ページを空にして保存（削除）するか `cmd=edit` で内容を消去
3. 大量にある場合は `wiki/` 内の `.txt` をバックアップ後に手動削除も可（**必ずバックアップを取ること**）
4. `cache/` を削除または更新し、リンクキャッシュを再構築

---

## 権限まとめ

| 操作 | 匿名 | ログイン済み（`valid-user`） | 管理者（`$adminpass`） |
|------|------|------------------------------|------------------------|
| ページ閲覧 | OK | OK | OK |
| ページ編集 | **不可** | OK | OK |
| コメント等プラグイン投稿 | **不可** | OK | OK |
| 凍結ページの解除 | 不可 | 不可 | OK（パスワード入力） |
| 添付アップロード | 不可（既定） | 設定次第 | OK（既定は管理者のみ） |

---

## トレードオフ

- **匿名編集ができなくなる** — Wiki の「誰でも編集」文化からは離れますが、現代のスパム環境では実用的な落としどころです。
- **アカウント管理が必要** — `$auth_users` の登録・パスワード変更を運用で行う必要があります。
- **CAPTCHA なし** — ログイン情報が漏洩した場合やボットがアカウントを取得した場合は別途対策が必要です。
- **CSRF 未対策** — 認証後の編集リクエストには CSRF トークンがありません（`docs/SECURITY-AUDIT.md` SEC-C02 参照）。

---

## 匿名編集を一時的に戻す場合

スパムが落ち着き、意図的に匿名編集を許可する場合:

```php
$edit_auth = 0;
```

変更後、ゲスト投稿プラグインも再び匿名利用可能になります（`page_write()` の防御層も無効化されます）。

---

## 将来の拡張（B: 追加防御）

| 対策 | 状態 | 備考 |
|------|------|------|
| **Akismet 連携** | **実装済み** | SPAM-05 (#31)。`lib/akismet.php` — 書き込み POST を外部 API で判定（既定 OFF） |
| CAPTCHA（匿名閲覧＋編集時のみ） | 未実装 | プラグイン新規作成または外部サービス連携が必要 |
| 新規ページ作成の追加制限 | 部分対応 | `newpage` → `edit` 経由で認証済み |
| 外部リンク POST 制限 | 未実装 | 記法・プラグインレベルの検証が必要 |
| レート制限 | 未実装 | `loginform` / 編集 POST に IP 単位制限を追加予定 |
| CSRF トークン | 未実装 | SECURITY-AUDIT SEC-C02 |

---

## Akismet 連携（SPAM-05）

[Akismet](https://akismet.com/) REST API の `comment-check` で、Wiki への **書き込み POST**（ページ編集・`comment` / `memo` 等のゲスト投稿プラグイン）をスパム判定します。判定は `page_write()` の実保存直前（認証ゲート通過後）に行います。

### 既定 OFF の理由

ログイン必須運用（SPAM-01）では匿名スパムは既に遮断されています。Akismet は **追加防御** として任意有効化とし、API key の取得・外部送信・可用性への依存を避けるため `$akismet_enabled = 0` を既定としています。

### 設定（pukiwiki.ini.php）

`pukiwiki.ini.php.example` の Akismet ブロックを本番の `pukiwiki.ini.php` にコピーし、値を設定します（`pukiwiki.ini.php` は git 管理外）。

```php
$akismet_enabled = 1;
$akismet_api_key = 'your-12-char-api-key';
$akismet_blog_url = '';         // 空なら Wiki の絶対 URL を自動使用
$akismet_strict = 0;            // 1=API エラー時も保存拒否, 0=エラー時は通す
```

| 変数 | 説明 |
|------|------|
| `$akismet_enabled` | `1` で有効（API key も必須） |
| `$akismet_api_key` | [Akismet](https://akismet.com/) で発行した API key |
| `$akismet_blog_url` | サイト URL。空の場合は `get_base_uri(PKWK_URI_ABSOLUTE)` |
| `$akismet_strict` | `1` なら API 接続失敗時も保存を拒否 |

### API key の取得

1. [Akismet](https://akismet.com/) でアカウント作成（WordPress.com アカウントでも可）
2. サイトを登録し **API key** を取得（12 文字の英数字）
3. `$akismet_api_key` に設定

個人・非商用 Wiki 向けの無償プランがある場合があります。利用条件は Akismet 側の規約に従ってください。

### プライバシー（重要）

Akismet 有効時、保存しようとした **Wiki 本文**（`comment_content`）に加え、投稿者名・IP アドレス・User-Agent・Referer・サイト URL が **Automattic（Akismet 運営）のサーバーへ送信**されます。

- 機密情報や個人情報を Wiki に書かない運用を推奨
- 利用者への告知（プライバシーポリシー等）を検討すること
- EU 等ではデータ越境・委託処理の観点で要確認

### 動作

| 結果 | 動作 |
|------|------|
| ham（非スパム） | 通常どおり保存 |
| spam | 保存拒否。日本語エラーメッセージを表示 |
| API エラー + `$akismet_strict = 0` | 保存を許可（可用性優先） |
| API エラー + `$akismet_strict = 1` | 保存拒否 |

ページ削除（空保存）・内容変更なしの保存は API を呼びません。

### テスト手順

#### API key なし / 無効時（skip）

1. `$akismet_enabled = 0` または `$akismet_api_key = ''` のまま
2. ログイン後にページ編集・保存 → **従来どおり成功**（Akismet は呼ばれない）

#### 有効時

1. 有効な `$akismet_api_key` を設定し `$akismet_enabled = 1`
2. 通常の編集を保存 → 成功
3. 明らかなスパム文（大量 URL・典型的スパムフレーズ等）を投稿 → 拒否メッセージが表示され保存されないこと
4. 無効な API key + `$akismet_strict = 1` → 保存拒否（strict 動作）
5. 無効な API key + `$akismet_strict = 0` → 保存成功（フォールスルー）

---

## 関連ドキュメント

- [SECURITY-AUDIT.md](./SECURITY-AUDIT.md) — セキュリティ監査（スパム・認証関連: SEC-H02, SEC-L04）
- [DEPLOY.md](./DEPLOY.md) — デプロイ手順
- [CHANGELOG.md](../CHANGELOG.md) — 変更履歴
