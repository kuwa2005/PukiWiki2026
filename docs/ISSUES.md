# ISSUES — GitHub Issue 索引

PukiWiki2026 の GitHub Issue 対応表。新規起票・close 時は本ファイルを更新する。

リポジトリ: https://github.com/kuwa2005/PukiWiki2026/issues

最終更新: 2026-06-07

---

## 凡例

| 状態 | 意味 |
|------|------|
| open | 未対応または部分対応 |
| closed | 実装・ドキュメント化完了 |

---

## オープン Issue（未対応）

| Issue | ID | タイトル | 理由 |
|-------|-----|----------|------|
| [#14](https://github.com/kuwa2005/PukiWiki2026/issues/14) | SPAM-02 | 編集時 CAPTCHA 導入 | 未実装（PukiWiki 1.5.4 に CAPTCHA プラグインなし） |
| [#16](https://github.com/kuwa2005/PukiWiki2026/issues/16) | SPAM-04 | 外部リンク POST 制限 | 未実装 |

---

## 機能追加（完了）

| Issue | ID | タイトル | 状態 | 対応 commit |
|-------|-----|----------|------|-------------|
| — | OEMBED-01 | oEmbed 規格対応 | **実装済**（Issue 未起票） | `d99fa8d`, `8df07f2` |

---

## スパム対策・認証

| Issue | ID | タイトル | 状態 | 対応 commit |
|-------|-----|----------|------|-------------|
| [#10](https://github.com/kuwa2005/PukiWiki2026/issues/10) | — | 匿名編集スパム対策 — ログイン必須化の実装 | **closed** | `76c8d83`, `8df07f2` |
| [#11](https://github.com/kuwa2005/PukiWiki2026/pull/11) | — | PR: 匿名編集スパム対策 | **merged** | `8df07f2` |
| [#12](https://github.com/kuwa2005/PukiWiki2026/issues/12) | SPAM-01 | 匿名編集スパム対策（ログイン必須化） | **closed** | `76c8d83`, `6149417` |
| [#13](https://github.com/kuwa2005/PukiWiki2026/issues/13) | AUTH-01 | 未認証 POST 直叩きによる書き込み遮断 | **closed** | `70c1fbf`, `07320fd` |
| [#14](https://github.com/kuwa2005/PukiWiki2026/issues/14) | SPAM-02 | 編集時 CAPTCHA 導入 | open | — |
| [#15](https://github.com/kuwa2005/PukiWiki2026/issues/15) | SPAM-03 | ログイン・編集 POST のレート制限 | **closed** | `d16d16d`, `8df07f2` |
| [#16](https://github.com/kuwa2005/PukiWiki2026/issues/16) | SPAM-04 | 外部リンク POST 制限 | open | — |
| [#31](https://github.com/kuwa2005/PukiWiki2026/issues/31) | SPAM-05 | Akismet 連携 — 編集 POST スパム判定 | **closed** | `94e5e8c`, `b4d6ebd` |
| [#30](https://github.com/kuwa2005/PukiWiki2026/issues/30) | AUTH-02 | 編集認証必須化と POST 直叩き遮断 | **closed** | `70c1fbf`, `8df07f2` |

---

## セキュリティ監査 — Critical / High（すべて closed）

| Issue | ID | タイトル | 状態 | 対応 commit |
|-------|-----|----------|------|-------------|
| [#1](https://github.com/kuwa2005/PukiWiki2026/issues/1) | SEC-C01 | PKWK_UPDATE_EXEC OS コマンド実行リスク | **closed** | `d16d16d`, `8df07f2` |
| [#2](https://github.com/kuwa2005/PukiWiki2026/issues/2) | SEC-C02 | CSRF トークン未実装 | **closed** | `d16d16d`, `8df07f2` |
| [#3](https://github.com/kuwa2005/PukiWiki2026/issues/3) | SEC-H04 | ログイン後オープンリダイレクト | **closed** | `d16d16d`, `8df07f2` |
| [#4](https://github.com/kuwa2005/PukiWiki2026/issues/4) | SEC-H01 | MD5 パスワードハッシュ | **closed** | `d16d16d`, `8df07f2` |
| [#5](https://github.com/kuwa2005/PukiWiki2026/issues/5) | SEC-H05 | wiki/ 直接アクセス保護不足 | **closed** | `77bebfa`, `8df07f2` |
| [#6](https://github.com/kuwa2005/PukiWiki2026/issues/6) | SEC-H02 | ブルートフォース対策欠如 | **closed** | `d16d16d`, `8df07f2` |
| [#7](https://github.com/kuwa2005/PukiWiki2026/issues/7) | SEC-H06 | 添付 inline 配信 XSS | **closed** | `4ec1b6f`, `8df07f2` |
| [#8](https://github.com/kuwa2005/PukiWiki2026/issues/8) | SEC-H03 | X_FORWARDED_USER なりすまし | **closed** | `d16d16d`, `8df07f2` |
| [#9](https://github.com/kuwa2005/PukiWiki2026/issues/9) | SEC-H07 | セッション Cookie 属性未設定 | **closed** | `d16d16d`, `8df07f2` |

---

## セキュリティ監査 — Medium

| Issue | ID | タイトル | 状態 | 対応 commit |
|-------|-----|----------|------|-------------|
| [#17](https://github.com/kuwa2005/PukiWiki2026/issues/17) | SEC-M01 | 平文パスワードスキーム | **closed** | `d16d16d`, `8df07f2` |
| [#18](https://github.com/kuwa2005/PukiWiki2026/issues/18) | SEC-M02 | 添付パスワード MD5 | **closed** | `4ec1b6f`, `8df07f2` |
| [#19](https://github.com/kuwa2005/PukiWiki2026/issues/19) | SEC-M03 | インライン style XSS | **closed** | `736fce7`, `243806a` |
| [#21](https://github.com/kuwa2005/PukiWiki2026/issues/21) | SEC-M04 | #version バージョン公開 | **closed** | `77bebfa`, `8df07f2` |
| [#23](https://github.com/kuwa2005/PukiWiki2026/issues/23) | SEC-M05 | 添付 info フルパス表示 | **closed** | `4ec1b6f`, `8df07f2` |
| [#25](https://github.com/kuwa2005/PukiWiki2026/issues/25) | SEC-M06 | セキュリティ HTTP ヘッダー | **closed** | `77bebfa`, `8df07f2` |
| [#27](https://github.com/kuwa2005/PukiWiki2026/issues/27) | SEC-M07 | ref SSRF リスク | **closed** | `4ec1b6f`, `8df07f2` |
| [#29](https://github.com/kuwa2005/PukiWiki2026/issues/29) | SEC-M08 | PHP 8.x 非推奨 API | **closed** | `d16d16d`, `4ec1b6f`, `8df07f2` |

---

## セキュリティ監査 — Low

| Issue | ID | タイトル | 状態 | 対応 commit |
|-------|-----|----------|------|-------------|
| [#20](https://github.com/kuwa2005/PukiWiki2026/issues/20) | SEC-L01 | error_reporting 設定 | **closed** | `2c3505e`, `243806a` |
| [#22](https://github.com/kuwa2005/PukiWiki2026/issues/22) | SEC-L02 | 外部 URI インライン画像 | **closed** | `77bebfa`, `8df07f2` |
| [#24](https://github.com/kuwa2005/PukiWiki2026/issues/24) | SEC-L03 | .htaccess dot-ht 拒否 | **closed** | `77bebfa`, `8df07f2` |
| [#26](https://github.com/kuwa2005/PukiWiki2026/issues/26) | SEC-L04 | ゲスト投稿スパム対策 | **closed** | `76c8d83`, `8df07f2` |
| [#28](https://github.com/kuwa2005/PukiWiki2026/issues/28) | SEC-L05 | PHP 8.2+ 回帰テスト | **closed** | `61b9c26`, `1cf4f3f`, `243806a` |

---

## メンテナンス手順

### Issue 運用フロー（追加 → 実装 → close）

1. **起票:** GitHub で日本語 Issue を作成（要件・受け入れ条件を明記）
2. **索引:** 本ファイル（`docs/ISSUES.md`）に行を追加
3. **実装:** `feature/*` ブランチでこまめに commit → push
4. **PR:** 日本語タイトル・本文（Summary / Test plan）で `main` へ
5. **close:** 実装完了コメント + 主要 commit hash を Issue に記載して close
6. **索引更新:** 本表の「状態」と「対応 commit」を **closed** に更新

---

## 関連ドキュメント

- [OEMBED.md](./OEMBED.md) — oEmbed 埋め込み機能
- [SECURITY-AUDIT.md](./SECURITY-AUDIT.md) — 監査レポート本体
- [ANTI-SPAM.md](./ANTI-SPAM.md) — スパム対策・認証ポリシー
- [GIT-WORKFLOW.md](./GIT-WORKFLOW.md) — ブランチ・PR 運用
