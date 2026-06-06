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

初回セットアップの全体像（ログイン・パスワード変更）は [SETUP.md](./SETUP.md) を参照してください。

### 1. 設定ファイルの確認

`pukiwiki.ini.php`（本番では環境ごとにコピー・調整。雛形は `pukiwiki.ini.php.example`）で以下を確認してください。

1. **`$auth_users` に編集者を1件以上登録**

   雛形にはデモ用の初期ユーザーが含まれます。**本番・公開前に必ずパスワードを変更**してください。

   ```php
   $auth_users = array(
       'editor' => '{x-php-sha256}d74ff0ee8da3b9806b18c877dbf29bbde50b5bd8e4dad7a3a725000feb82e8f1', // pass — 必ず変更
   );
   ```

   ハッシュ生成（推奨）: Web 支援スクリプト **`tools/gen-password-hash.php`**

   - URL 例: `https://your-wiki.example/tools/gen-password-hash.php`
   - `{x-php-sha256}` または `{x-php-password}` を選択可能
   - **本番公開後は削除または IP 制限**（[tools/README.md](../tools/README.md)）

   CLI 例:

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
- **CAPTCHA は任意** — `$captcha_enabled = 0`（既定）では無効。有効化すると編集保存時に reCAPTCHA または honeypot で第2防御を追加できます（[CAPTCHA 節](#captcha-連携spam-02) 参照）。
- **CSRF** — 編集 POST 等に CSRF トークンを付与（`lib/csrf.php`、SEC-C02 対応済み）

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
| **CAPTCHA（編集時）** | **実装済み** | SPAM-02 (#14)。`lib/captcha.php` — reCAPTCHA v2/v3 または honeypot（既定 OFF） |
| 新規ページ作成の追加制限 | 部分対応 | `newpage` → `edit` 経由で認証済み |
| **外部リンク POST 制限** | **実装済み** | SPAM-04 (#16)。`lib/spamfilter.php` — 本文中の外部 URL を拒否（既定 OFF） |
| レート制限 | **実装済み** | SPAM-03 (#15)。`loginform` / 編集 POST の IP 単位制限 |
| CSRF トークン | **実装済み** | SEC-C02 (#2)。`lib/csrf.php` |

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

## CAPTCHA 連携（SPAM-02）

編集フォーム（`cmd=edit`）に CAPTCHA を表示し、**保存 POST** 時に検証します。プレビュー・キャンセルでは検証しません。`enforce_edit_auth` および CSRF 検証の後、`page_write()` の直前（`plugin/edit.inc.php`）で実行します。

### 既定 OFF の理由

ログイン必須運用では第一防御として認証があります。CAPTCHA は **アカウント漏洩・ボット対策の第2防御** として任意有効化とし、reCAPTCHA 未設定時は `$captcha_enabled = 0` で既存動作に影響しません。

### 設定（pukiwiki.ini.php）

```php
$captcha_enabled = 1;
$captcha_provider = 'recaptcha_v2'; // recaptcha_v2 | recaptcha_v3 | honeypot
$recaptcha_site_key = 'your-site-key';
$recaptcha_secret_key = 'your-secret-key';
```

| 変数 | 説明 |
|------|------|
| `$captcha_enabled` | `1` で有効 |
| `$captcha_provider` | `recaptcha_v2`（チェックボックス）、`recaptcha_v3`（スコア判定）、`honeypot`（外部 API 不要の簡易代替） |
| `$recaptcha_site_key` | Google reCAPTCHA のサイトキー（honeypot 時は不要） |
| `$recaptcha_secret_key` | Google reCAPTCHA のシークレットキー（honeypot 時は不要） |

reCAPTCHA キーは [Google reCAPTCHA 管理コンソール](https://www.google.com/recaptcha/admin) で取得します。v3 はスコア閾値 `0.5`（`PKWK_CAPTCHA_RECAPTCHA_V3_THRESHOLD`）未満を拒否します。

### 動作

| 操作 | CAPTCHA 検証 |
|------|--------------|
| 編集フォーム表示 | 有効時のみウィジェット（または honeypot フィールド）を表示 |
| プレビュー | 検証なし |
| 保存（write） | 検証あり（失敗時は日本語エラーで保存拒否） |
| ページ削除（空保存） | 検証スキップ |
| `comment` 等の他プラグイン | 検証なし（編集フォーム経由のみ） |

### テスト手順

#### 無効時（skip）

1. `$captcha_enabled = 0` のまま
2. ログイン後に編集・保存 → **従来どおり成功**

#### reCAPTCHA v2 有効時

1. 有効な site/secret key を設定し `$captcha_enabled = 1`
2. 編集画面に reCAPTCHA ウィジェットが表示されること
3. チェックなしで保存 → 拒否メッセージ
4. チェック後に保存 → 成功

#### honeypot 有効時

1. `$captcha_provider = 'honeypot'`、`$captcha_enabled = 1`
2. 通常保存 → 成功
3. `pkwk_hp_url` フィールドに値を入れて POST → 拒否

---

## 外部リンク POST 制限（SPAM-04）

書き込み POST の本文（Wiki ソース）に含まれる **外部 URL**（`http://` / `https://`、自サイト以外）を制限します。判定は `page_write()` の認証ゲート通過後・保存直前に行い、Akismet と共存します。

### 設定（pukiwiki.ini.php）

```php
$spam_block_external_links = 1;   // 0=無効, 1=全員拒否, 2=管理者のみ許可
$spam_external_link_allowlist = array('youtube.com', 'github.com');
```

| 変数 | 説明 |
|------|------|
| `$spam_block_external_links` | `0` 無効（既定）、`1` 外部リンクを全員拒否、`2` 管理者のみ許可 |
| `$spam_external_link_allowlist` | 許可するドメイン（サブドメイン含む。`www.` は正規化して比較） |

自サイト判定は `get_base_uri(PKWK_URI_ABSOLUTE)` のホストと比較します。モード `2` では `auth_groups` の `admin` グループ所属、または POST の管理者パスワード（`pass` / `adminpass`）検証で許可します。

### 動作

| モード | 外部 URL を含む保存 |
|--------|---------------------|
| `0` | 制限なし |
| `1` | 拒否（許可リスト・自サイト除く） |
| `2` | 一般ユーザーは拒否、管理者は許可 |

拒否時は検出したホスト名を含む日本語エラーメッセージを表示します。

### テスト手順

1. `$spam_block_external_links = 0` → 外部 URL 付き保存が成功すること
2. `$spam_block_external_links = 1` → `https://example.com` を含む保存が拒否されること
3. 自サイト URL（`get_base_uri()` と同ホスト）→ 保存成功
4. `$spam_external_link_allowlist = array('example.com')` → `https://www.example.com/foo` は許可
5. モード `2` で一般ユーザー → 拒否、管理者パスワード入力または `admin` グループで許可

---

## 関連ドキュメント

- [SETUP.md](./SETUP.md) — 初回ログイン・パスワード変更
- [SECURITY-AUDIT.md](./SECURITY-AUDIT.md) — セキュリティ監査（スパム・認証関連: SEC-H02, SEC-L04）
- [DEPLOY.md](./DEPLOY.md) — デプロイ手順
- [CHANGELOG.md](../CHANGELOG.md) — 変更履歴
