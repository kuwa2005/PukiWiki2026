forge — PukiWiki2026 軽量モダンスキン
======================================

classic をベースに、配色・タイポグラフィ・余白のみを調整した軽量スキンです。
外部フレームワーク（React / Vue 等）・ビルドツールは使いません。
CSS 1 ファイル + 既存 JS（main.js / search2.js）をそのまま利用します。

ファイル構成（合計 約 80 KB）:
  pukiwiki.skin.php  … スキン PHP（classic 同等構造）
  pukiwiki.css       … 見た目調整（CSS のみ）
  main.js, search2.js … PukiWiki 既存 JS

方針: 軽量・小サイズ最優先。新機能は plugin/ に任意追加。
React forge（PR #39）は revert 済み。

選定理由: 「鍛える・作る」イメージ — PukiWiki2026 の改造 fork と相性がよいため。

切替: pukiwiki.ini.php で $skin = 'forge';
