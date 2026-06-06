# ISSUES — GitHub Issue 索引

PukiWiki2026 の GitHub Issue 対応表。新規起票・close 時は本ファイルを更新する。

リポジトリ: https://github.com/kuwa2005/PukiWiki2026/issues

最終更新: 2026-06-07

---

## 機能追加

| Issue | ID | タイトル | 状態 | ラベル | 対応 commit / 備考 |
|-------|-----|----------|------|--------|-------------------|
| [#30](https://github.com/kuwa2005/PukiWiki2026/issues/30) | OEMBED-01 | oEmbed 規格対応 — URL から自動埋め込み表示 | open | enhancement | 実装中 |

---

## 凡例

| 状態 | 意味 |
|------|------|
| open | 未対応または部分対応 |
| closed | 実装・ドキュメント化完了 |

---

## スパム対策・認証

| Issue | ID | タイトル | 状態 | ラベル | 対応 commit / 備考 |
|-------|-----|----------|------|--------|-------------------|
| [#12](https://github.com/kuwa2005/PukiWiki2026/issues/12) | SPAM-01 | 匿名編集スパム対策（ログイン必須化） | **closed** | spam, auth | `76c8d83`, `6149417` |
| [#13](https://github.com/kuwa2005/PukiWiki2026/issues/13) | AUTH-01 | 未認証 POST 直叩きによる書き込み遮断 | **closed** | auth, spam, security | `76c8d83`, `70c1fbf`, `07320fd` |
| [#14](https://github.com/kuwa2005/PukiWiki2026/issues/14) | SPAM-02 | 編集時 CAPTCHA 導入 | open | spam | 未実装 |
| [#15](https://github.com/kuwa2005/PukiWiki2026/issues/15) | SPAM-03 | ログイン・編集 POST のレート制限 | open | spam, security | #6 と関連 |
| [#16](https://github.com/kuwa2005/PukiWiki2026/issues/16) | SPAM-04 | 外部リンク POST 制限 | open | spam | 未実装 |

---

## セキュリティ監査 — Critical / High

`docs/SECURITY-AUDIT.md` より。いずれも **open**（未修正）。

| Issue | ID | タイトル | 深刻度 | ラベル |
|-------|-----|----------|--------|--------|
| [#1](https://github.com/kuwa2005/PukiWiki2026/issues/1) | SEC-C01 | PKWK_UPDATE_EXEC 設定時の OS コマンド実行リスク | Critical | security, critical |
| [#2](https://github.com/kuwa2005/PukiWiki2026/issues/2) | SEC-C02 | CSRF トークン未実装（全 POST 操作） | Critical | security, critical |
| [#4](https://github.com/kuwa2005/PukiWiki2026/issues/4) | SEC-H01 | MD5 ベースのパスワードハッシュ（弱い方式） | High | security, high |
| [#6](https://github.com/kuwa2005/PukiWiki2026/issues/6) | SEC-H02 | フォームログインへのブルートフォース対策欠如 | High | security, high |
| [#8](https://github.com/kuwa2005/PukiWiki2026/issues/8) | SEC-H03 | HTTP_X_FORWARDED_USER による認証なりすまし | High | security, high |
| [#3](https://github.com/kuwa2005/PukiWiki2026/issues/3) | SEC-H04 | ログイン後オープンリダイレクト（url_after_login） | High | security, high |
| [#5](https://github.com/kuwa2005/PukiWiki2026/issues/5) | SEC-H05 | wiki/ ディレクトリの直接アクセス保護不足 | High | security, high |
| [#7](https://github.com/kuwa2005/PukiWiki2026/issues/7) | SEC-H06 | 添付ファイル inline 配信による XSS リスク | High | security, high |
| [#9](https://github.com/kuwa2005/PukiWiki2026/issues/9) | SEC-H07 | セッション Cookie セキュリティ属性未設定 | High | security, high |

---

## セキュリティ監査 — Medium

| Issue | ID | タイトル | 状態 | ラベル |
|-------|-----|----------|------|--------|
| [#17](https://github.com/kuwa2005/PukiWiki2026/issues/17) | SEC-M01 | 平文パスワードスキームとサンプル設定 | open | security, medium |
| [#18](https://github.com/kuwa2005/PukiWiki2026/issues/18) | SEC-M02 | 添付パスワードの MD5 ハッシュ | open | security, medium |
| [#19](https://github.com/kuwa2005/PukiWiki2026/issues/19) | SEC-M03 | Wiki 記法によるインライン style XSS 残余 | open | security, medium |
| [#21](https://github.com/kuwa2005/PukiWiki2026/issues/21) | SEC-M04 | #version プラグインによるバージョン公開 | open | security, medium |
| [#23](https://github.com/kuwa2005/PukiWiki2026/issues/23) | SEC-M05 | 添付 info 画面のフルパス表示 | open | security, medium |
| [#25](https://github.com/kuwa2005/PukiWiki2026/issues/25) | SEC-M06 | セキュリティ HTTP ヘッダー未設定（既定） | open | security, medium |
| [#27](https://github.com/kuwa2005/PukiWiki2026/issues/27) | SEC-M07 | ref プラグインの SSRF リスク | open | security, medium |
| [#29](https://github.com/kuwa2005/PukiWiki2026/issues/29) | SEC-M08 | PHP 8.x 非推奨 API 使用 | open | security, medium |

---

## セキュリティ監査 — Low

| Issue | ID | タイトル | 状態 | ラベル | 備考 |
|-------|-----|----------|------|--------|------|
| [#20](https://github.com/kuwa2005/PukiWiki2026/issues/20) | SEC-L01 | index.php の error_reporting 設定 | open | security, low | |
| [#22](https://github.com/kuwa2005/PukiWiki2026/issues/22) | SEC-L02 | 外部 URI インライン画像（Web ビーコン） | open | security, low | |
| [#24](https://github.com/kuwa2005/PukiWiki2026/issues/24) | SEC-L03 | .htaccess の dot-ht 拒否が無効 | open | security, low | |
| [#26](https://github.com/kuwa2005/PukiWiki2026/issues/26) | SEC-L04 | ゲスト投稿プラグインのスパム・荒らし対策 | open | security, low, spam | 匿名遮断は #12 で対応済み。CAPTCHA 等は #14/#15 |
| [#28](https://github.com/kuwa2005/PukiWiki2026/issues/28) | SEC-L05 | PHP 8.2/8.3/8.4 回帰テスト未実施 | open | security, low | #29 と関連 |

---

## メンテナンス手順

### Issue 運用フロー（追加 → 実装 → close）

1. **起票:** GitHub で日本語 Issue を作成（要件・受け入れ条件を明記）
2. **索引:** 本ファイル（`docs/ISSUES.md`）に行を追加
3. **実装:** `feature/*` ブランチでこまめに commit → push
4. **PR:** 日本語タイトル・本文（Summary / Test plan）で `main` へ
5. **close:** 実装完了コメント + 主要 commit hash を Issue に記載して close
6. **索引更新:** 本表の「状態」と「対応 commit」を **closed** に更新

### その他

1. 新規 Issue 起票時: 上表に行を追加し、`docs/SECURITY-AUDIT.md` の対応表（該当時）も更新
2. 実装完了時: Issue を close し、本表の「状態」と「対応 commit」を更新
3. 重複 Issue: `duplicate` ラベル付与と cross-reference コメント

---

## 関連ドキュメント

- [OEMBED.md](./OEMBED.md) — oEmbed 埋め込み機能
- [SECURITY-AUDIT.md](./SECURITY-AUDIT.md) — 監査レポート本体
- [ANTI-SPAM.md](./ANTI-SPAM.md) — スパム対策・認証ポリシー
- [GIT-WORKFLOW.md](./GIT-WORKFLOW.md) — ブランチ・PR 運用
