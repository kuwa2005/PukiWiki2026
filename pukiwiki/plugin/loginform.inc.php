<?php
// PukiWiki - Yet another WikiWikiWeb clone
// Copyright 2015-2022 PukiWiki Development Team
// License: GPL v2 or (at your option) any later version
//
// "Login form" plugin

function plugin_loginform_inline()
{
	global $vars, $read_auth, $edit_auth;
	$page = isset($vars['page']) ? $vars['page'] : '';
	if (! is_pagename($page)) {
		$page = '';
	}
	if (! ($read_auth || $edit_auth)) {
		// non auth site
		return 'Note: loginform is for auth enabled site';
	}
	if (! pkwk_is_authenticated()) {
		return '';
	}
	$logout_param = '?plugin=loginform&pcmd=logout&page=' . pagename_urlencode($page);
	return '<a href="' . htmlsc(get_base_uri() . $logout_param) . '">Log out</a>';
}

function plugin_loginform_convert()
{
	return '<div>' . plugin_loginform_inline() . '</div>';
}

function plugin_loginform_action()
{
	global $auth_user, $auth_type, $_loginform_messages;
	global $read_auth, $edit_auth;
	$page = isset($_GET['page']) ? $_GET['page'] : '';
	$pcmd = isset($_GET['pcmd']) ? $_GET['pcmd'] : '';
	if (! is_pagename($page)) {
		$page = '';
	}
	if (! ($read_auth || $edit_auth)) {
		// non auth site
		die_message('Invalid action');
		exit;
	}
	$url_after_login = isset($_GET['url_after_login']) ? $_GET['url_after_login'] : '';
	$page_after_login = $page;
	if (!$url_after_login) {
		$page_after_login = $page;
	}
	$action_url = get_base_uri() . '?plugin=loginform'
		. '&page=' . rawurlencode($page)
		. ($url_after_login ? '&url_after_login=' . rawurlencode($url_after_login) : '')
		. ($page_after_login ? '&page_after_login=' . rawurlencode($page_after_login) : '');
	$username = isset($_POST['username']) ? $_POST['username'] : '';
	$password = isset($_POST['password']) ? $_POST['password'] : '';
	$isset_user_credential = $username || $password ;
	$unsafe_username = ($username !== '' && function_exists('pkwk_is_safe_identifier') &&
		! pkwk_is_safe_identifier($username));
	$changepassword_failed = isset($_GET['changepassword_failed']) && $_GET['changepassword_failed'] === '1';
	$changepassword_manual = NULL;
	$changepassword_error = '';
	$changepassword_hint = '';
	if ($changepassword_failed) {
		pkwk_ensure_session();
		$flash = pkwk_flash_consume('changepassword_manual');
		if (is_array($flash)) {
			if (isset($flash['user'], $flash['hash'])
				&& is_string($flash['user']) && $flash['user'] !== ''
				&& is_string($flash['hash']) && $flash['hash'] !== '') {
				$changepassword_manual = array(
					'user' => $flash['user'],
					'hash' => $flash['hash'],
				);
			}
			if (! empty($flash['error']) && is_string($flash['error'])) {
				require_once(LIB_DIR . 'auth_ini.php');
				$changepassword_error = pkwk_ini_auth_write_error_message($flash['error']);
			}
			if (! empty($flash['hint']) && is_string($flash['hint'])) {
				$changepassword_hint = $flash['hint'];
			}
		}
	}
	if ($username && $password && ! $unsafe_username && form_auth($username, $password)) {
		// Sign in successfully completed
		if (! empty($_SESSION['pkwk_must_change_password'])) {
			$change_url = get_base_uri() . '?plugin=changepassword';
			if ($page_after_login) {
				$change_url .= '&page=' . pagename_urlencode($page_after_login);
			}
			header('HTTP/1.0 302 Found');
			header('Location: ' . $change_url);
			exit;
		}
		form_auth_redirect($url_after_login, $page_after_login);
		exit; // or 'return FALSE;' - Don't double check for FORM_AUTH
	}
	if ($pcmd === 'logout') {
		// logout
		switch ($auth_type) {
			case AUTH_TYPE_BASIC:
				header('WWW-Authenticate: Basic realm="Please cancel to log out"');
				header('HTTP/1.0 401 Unauthorized');
				break;
			case AUTH_TYPE_FORM:
			case AUTH_TYPE_EXTERNAL:
			case AUTH_TYPE_SAML:
			default:
				$_SESSION = array();
				session_regenerate_id(true); // require: PHP5.1+
				session_destroy();
				break;
		}
		$auth_user = '';
		$page_link = '';
		if ($page) {
			$page_link = '<br>' . make_pagelink($page);
		}
		return array(
			'msg' => 'Log out',
			'body' => 'Logged out completely' . $page_link,
		);
	} else {
		// login
		ob_start();
?>
<style>
  .loginformcontainer {
    text-align: center;
  }
  .loginform table {
    margin-top: 1em;
	margin-left: auto;
	margin-right: auto;
  }
  .loginform tbody td {
    padding: .5em;
  }
  .loginform .label {
    text-align: right;
  }
  .loginform .login-button-container {
    text-align: right;
  }
  .loginform .loginbutton {
    margin-top: 1em;
  }
  .loginform .errormessage {
    color: red;
  }
  .loginform .manual-hash {
    font-family: monospace;
    word-break: break-all;
    text-align: left;
    max-width: 36em;
    margin: 1em auto;
  }
  .loginform .debug-hint {
    font-family: monospace;
    font-size: 0.85em;
    color: #555;
    text-align: left;
    max-width: 36em;
    margin: 0.5em auto;
  }
</style>
<div class="loginformcontainer">
<form name="loginform" class="loginform" action="<?php echo htmlsc($action_url) ?>" method="post">
<?php echo pkwk_csrf_hidden_field() ?>
<div>
<table style="border:0">
  <tbody>
  <tr>
    <td class="label"><label for="_plugin_loginform_username"><?php echo htmlsc($_loginform_messages['username']) ?></label></td>
    <td><input type="text" name="username" value="<?php echo htmlsc($username) ?>" id="_plugin_loginform_username"></td>
  </tr>
  <tr>
  <td class="label"><label for="_plugin_loginform_password"><?php echo htmlsc($_loginform_messages['password']) ?></label></td>
  <td><input type="password" name="password" id="_plugin_loginform_password"></td>
  </tr>
<?php if ($changepassword_failed): ?>
  <tr>
    <td></td>
    <td class="errormessage"><?php echo htmlsc($changepassword_error !== '' ? $changepassword_error : 'パスワードの保存に失敗しました。ファイルの書き込み権限を確認してから、再度ログインしてください。') ?></td>
  </tr>
<?php endif ?>
<?php if ($unsafe_username): ?>
  <tr>
    <td></td>
    <td class="errormessage"><?php echo htmlsc($_loginform_messages['unsafe_username']) ?></td>
  </tr>
<?php elseif ($isset_user_credential): ?>
  <tr>
    <td></td>
    <td class="errormessage"><?php echo $_loginform_messages['invalid_username_or_password'] ?></td>
  </tr>
<?php endif ?>
  <tr>
    <td></td>
    <td class="login-button-container"><input type="submit" value="<?php echo htmlsc($_loginform_messages['login']) ?>" class="loginbutton"></td>
  </tr>
  </tbody>
</table>
</div>
<div>
</div>
</form>
<?php if ($changepassword_failed && $changepassword_hint !== ''): ?>
<p class="debug-hint">診断: <?php echo htmlsc($changepassword_hint) ?></p>
<?php endif ?>
<?php if ($changepassword_manual): ?>
<p>手動設定用ハッシュ（<code>$auth_users['<?php echo htmlsc($changepassword_manual['user']) ?>']</code>）:</p>
<p class="manual-hash"><code><?php echo htmlsc($changepassword_manual['hash']) ?></code></p>
<p><small>ハッシュ生成: <code>pukiwiki/tools/gen-password-hash.php</code> または <a href="https://github.com/kuwa2005/PukiWiki2026/blob/main/pukiwiki/docs/SETUP.md">docs/SETUP.md</a></small></p>
<?php elseif ($changepassword_failed): ?>
<p class="errormessage"><small>手動設定用ハッシュの表示に失敗しました。上記の診断情報を確認するか、<code>pukiwiki/tools/gen-password-hash.php</code> でハッシュを生成してください。</small></p>
<?php endif ?>
</div>
<script><!--
window.addEventListener && window.addEventListener("DOMContentLoaded", function() {
  var f = window.document.forms.loginform;
  if (f && f.username && f.password) {
    if (f.username.value) {
     f.password.focus && f.password.focus();
	} else {
     f.username.focus && f.username.focus();
	}
  }
});
//-->
</script>
<?php
		$body = ob_get_contents();
		ob_end_clean();
		return array(
			'msg' => $_loginform_messages['login'],
			'body' => $body,
			);
	}
}
