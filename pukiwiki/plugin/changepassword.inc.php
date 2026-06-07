<?php
// PukiWiki2026 - Force password change plugin
// License: GPL v2 or (at your option) any later version

define('PKWK_CHANGE_PASSWORD_MIN_LENGTH', 8);

function plugin_changepassword_action()
{
	global $auth_user, $auth_type, $defaultpage, $vars;

	if ($auth_type !== AUTH_TYPE_FORM || $auth_user === '') {
		die_message('ログインが必要です');
		exit;
	}

	if (! pkwk_auth_user_must_change_password()) {
		$dest = isset($vars['page']) && is_pagename($vars['page'])
			? get_page_uri($vars['page'], PKWK_URI_ROOT)
			: get_page_uri($defaultpage, PKWK_URI_ROOT);
		header('HTTP/1.0 302 Found');
		header('Location: ' . $dest);
		exit;
	}

	$errors = array();
	$manual_hash = '';
	$done = FALSE;

	if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
		$new_password = isset($_POST['new_password']) ? (string)$_POST['new_password'] : '';
		$confirm_password = isset($_POST['confirm_password']) ? (string)$_POST['confirm_password'] : '';

		if ($new_password === '') {
			$errors[] = '新しいパスワードを入力してください。';
		} else if (strlen($new_password) < PKWK_CHANGE_PASSWORD_MIN_LENGTH) {
			$errors[] = '新しいパスワードは ' . PKWK_CHANGE_PASSWORD_MIN_LENGTH . ' 文字以上にしてください。';
		} else if (strlen($new_password) > PKWK_PASSPHRASE_LIMIT_LENGTH) {
			$errors[] = '新しいパスワードが長すぎます。';
		} else if ($new_password === PKWK_DEFAULT_PASSWORD) {
			$errors[] = '初期パスワード「' . PKWK_DEFAULT_PASSWORD . '」は使用できません。別のパスワードを設定してください。';
		} else if ($new_password !== $confirm_password) {
			$errors[] = '確認用パスワードが一致しません。';
		} else {
			$new_hash = pkwk_hash_store($new_password);
			require_once(LIB_DIR . 'auth_ini.php');
			$result = pkwk_ini_update_auth_user_hash($auth_user, $new_hash);
			if (! empty($result['ok'])) {
				unset($_SESSION['pkwk_must_change_password']);
				$done = TRUE;
			} else {
				$manual_hash = $new_hash;
				if (! empty($result['manual'])) {
					$errors[] = 'pukiwiki.ini.php を自動更新できませんでした。ファイルの書き込み権限を確認するか、下記ハッシュを手動で設定してください。';
				} else {
					$errors[] = 'パスワードの保存に失敗しました。';
				}
			}
		}
	}

	if ($done) {
		$dest = isset($vars['page']) && is_pagename($vars['page'])
			? get_page_uri($vars['page'], PKWK_URI_ROOT)
			: get_page_uri($defaultpage, PKWK_URI_ROOT);
		return array(
			'msg' => 'パスワード変更完了',
			'body' => '<p>パスワードを変更しました。Wiki を利用できます。</p>'
				. '<p><a href="' . htmlsc($dest) . '">続ける</a></p>',
		);
	}

	$action_url = get_base_uri() . '?plugin=changepassword';
	if (isset($vars['page']) && is_pagename($vars['page'])) {
		$action_url .= '&page=' . pagename_urlencode($vars['page']);
	}

	ob_start();
?>
<style>
  .changepasswordcontainer {
    max-width: 36em;
    margin: 1em auto;
  }
  .changepassword table {
    margin-top: 1em;
    margin-left: auto;
    margin-right: auto;
  }
  .changepassword tbody td {
    padding: .5em;
  }
  .changepassword .label {
    text-align: right;
    white-space: nowrap;
  }
  .changepassword .submit-container {
    text-align: right;
  }
  .changepassword .errormessage {
    color: #c00;
  }
  .changepassword .notice {
    color: #630;
    font-weight: bold;
  }
  .changepassword .manual-hash {
    font-family: monospace;
    word-break: break-all;
  }
</style>
<div class="changepasswordcontainer">
<p class="notice">初期パスワードのままです。Wiki を利用する前に、必ず新しいパスワードを設定してください。</p>
<?php if ($errors): ?>
<ul class="errormessage">
<?php foreach ($errors as $error): ?>
  <li><?php echo htmlsc($error) ?></li>
<?php endforeach ?>
</ul>
<?php endif ?>
<?php if ($manual_hash !== ''): ?>
<p>手動設定用ハッシュ（<code>$auth_users['<?php echo htmlsc($auth_user) ?>']</code>）:</p>
<p class="manual-hash"><code><?php echo htmlsc($manual_hash) ?></code></p>
<p><small>ハッシュ生成: <code>pukiwiki/tools/gen-password-hash.php</code> または <a href="https://github.com/kuwa2005/PukiWiki2026/blob/main/pukiwiki/docs/SETUP.md">docs/SETUP.md</a></small></p>
<?php endif ?>
<form name="changepassword" class="changepassword" action="<?php echo htmlsc($action_url) ?>" method="post">
<?php echo pkwk_csrf_hidden_field() ?>
<table style="border:0">
  <tbody>
  <tr>
    <td class="label"><label for="_plugin_changepassword_new">新しいパスワード</label></td>
    <td><input type="password" name="new_password" id="_plugin_changepassword_new" autocomplete="new-password" required minlength="<?php echo PKWK_CHANGE_PASSWORD_MIN_LENGTH ?>"></td>
  </tr>
  <tr>
    <td class="label"><label for="_plugin_changepassword_confirm">確認</label></td>
    <td><input type="password" name="confirm_password" id="_plugin_changepassword_confirm" autocomplete="new-password" required minlength="<?php echo PKWK_CHANGE_PASSWORD_MIN_LENGTH ?>"></td>
  </tr>
  <tr>
    <td></td>
    <td class="submit-container"><input type="submit" value="パスワードを変更"></td>
  </tr>
  </tbody>
</table>
</form>
</div>
<?php
	$body = ob_get_contents();
	ob_end_clean();

	return array(
		'msg' => 'パスワード変更（必須）',
		'body' => $body,
	);
}
