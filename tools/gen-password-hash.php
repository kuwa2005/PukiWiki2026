<?php
/**
 * PukiWiki2026 — パスワードハッシュ生成支援（開発・初回セットアップ用）
 *
 * 平文パスワードを PukiWiki の $auth_users / $adminpass 用ハッシュ文字列に変換します。
 * 本番公開後は必ず削除するか、IP 制限等でアクセスを遮断してください。
 *
 * License: GPL v2 or (at your option) any later version
 */

define('PKWK_PASSPHRASE_LIMIT_LENGTH', 512);

function htmlsc($string = '', $flags = ENT_COMPAT, $charset = 'UTF-8')
{
	return htmlspecialchars($string, $flags, $charset);
}

function gen_password_hash($password, $scheme_key)
{
	if (! is_string($password) || $password === '') {
		return '';
	}
	if (strlen($password) > PKWK_PASSPHRASE_LIMIT_LENGTH) {
		return '';
	}

	switch ($scheme_key) {
	case 'x-php-password':
		return '{x-php-password}' . password_hash($password, PASSWORD_DEFAULT);

	case 'x-php-sha256':
	default:
		return '{x-php-sha256}' . hash('sha256', $password);
	}
}

$scheme = 'x-php-sha256';
$hash = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$password = isset($_POST['password']) ? (string)$_POST['password'] : '';
	$scheme = isset($_POST['scheme']) ? (string)$_POST['scheme'] : 'x-php-sha256';

	if ($password === '') {
		$error = 'パスワードを入力してください。';
	} else if (strlen($password) > PKWK_PASSPHRASE_LIMIT_LENGTH) {
		$error = 'パスワードが長すぎます（最大 ' . PKWK_PASSPHRASE_LIMIT_LENGTH . ' 文字）。';
	} else if (! in_array($scheme, array('x-php-sha256', 'x-php-password'), TRUE)) {
		$error = '不正なハッシュ方式です。';
	} else {
		$hash = gen_password_hash($password, $scheme);
	}
}

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>PukiWiki2026 — パスワードハッシュ生成</title>
<style>
body { font-family: sans-serif; line-height: 1.6; max-width: 40rem; margin: 2rem auto; padding: 0 1rem; }
.warning { background: #fff3cd; border: 1px solid #ffc107; padding: 1rem; border-radius: 4px; }
.error { color: #842029; }
.result { margin-top: 1.5rem; }
.result textarea { width: 100%; font-family: monospace; font-size: 0.9rem; }
label { display: block; margin: 0.75rem 0 0.25rem; }
button { margin-top: 1rem; padding: 0.5rem 1rem; }
fieldset { border: 1px solid #ccc; padding: 0.5rem 1rem 1rem; margin-top: 1rem; }
legend { padding: 0 0.25rem; }
</style>
</head>
<body>
<h1>パスワードハッシュ生成</h1>

<div class="warning">
<strong>開発・初回セットアップ専用</strong><br>
このスクリプトは <code>pukiwiki.ini.php</code> の <code>$auth_users</code> / <code>$adminpass</code> 設定用です。
<strong>本番公開後は削除するか、IP 制限等で外部からアクセスできないようにしてください。</strong>
平文パスワードはサーバーに保存されません（POST 処理時のみメモリ上でハッシュ化）。
</div>

<form method="post" action="">
<label for="password">平文パスワード</label>
<input type="password" name="password" id="password" autocomplete="new-password" required>

<fieldset>
<legend>ハッシュ方式</legend>
<label>
<input type="radio" name="scheme" value="x-php-sha256"<?php echo ($scheme === 'x-php-sha256') ? ' checked' : '' ?>>
<code>{x-php-sha256}</code> — SHA-256（PukiWiki2026 推奨・固定長）
</label>
<label>
<input type="radio" name="scheme" value="x-php-password"<?php echo ($scheme === 'x-php-password') ? ' checked' : '' ?>>
<code>{x-php-password}</code> — PHP <code>password_hash()</code>（bcrypt 等）
</label>
</fieldset>

<button type="submit">ハッシュを生成</button>
</form>

<?php if ($error !== '') { ?>
<p class="error"><?php echo htmlsc($error) ?></p>
<?php } ?>

<?php if ($hash !== '') { ?>
<div class="result">
<p>生成結果（<code>pukiwiki.ini.php</code> に貼り付け）:</p>
<textarea id="hash-output" rows="3" readonly onclick="this.select();"><?php echo htmlsc($hash) ?></textarea>
<p><small>クリックで全選択。例: <code>'editor' =&gt; '<?php echo htmlsc($hash) ?>'</code></small></p>
</div>
<?php } ?>

<p><small>詳細: <a href="../docs/SETUP.md">docs/SETUP.md</a></small></p>
</body>
</html>
