<?php
// PukiWiki2026 - pukiwiki.ini.php auth_users update helpers
// License: GPL v2 or (at your option) any later version

/**
 * Validate stored password hash format for ini persistence.
 *
 * @param string $hash
 * @return bool
 */
function pkwk_ini_is_valid_auth_hash($hash)
{
	if (! is_string($hash) || $hash === '') {
		return FALSE;
	}
	if (preg_match('/^\{x-php-(sha256|password|md5|sha1|sha384|sha512)\}[a-zA-Z0-9_$./+=:-]+$/', $hash)) {
		return TRUE;
	}
	if (preg_match('/^\$(2[ayb]|argon2)/', $hash)) {
		return TRUE;
	}
	return FALSE;
}

/**
 * Single attempt to update one $auth_users entry in pukiwiki.ini.php.
 *
 * @param string $username
 * @param string $new_hash
 * @param string $ini_file Path to pukiwiki.ini.php
 * @return array{ok:bool,error?:string,manual?:bool}
 */
function pkwk_ini_attempt_write_auth_user_hash($username, $new_hash, $ini_file)
{
	global $auth_users;

	$real_ini = realpath($ini_file);
	if ($real_ini === FALSE || ! is_file($real_ini) || ! is_readable($real_ini)) {
		return array('ok' => FALSE, 'error' => 'ini_not_readable', 'manual' => TRUE);
	}
	if (! is_writable($real_ini)) {
		return array('ok' => FALSE, 'error' => 'ini_not_writable', 'manual' => TRUE);
	}

	$content = file_get_contents($real_ini);
	if ($content === FALSE) {
		return array('ok' => FALSE, 'error' => 'ini_read_failed', 'manual' => TRUE);
	}

	$username_quoted = preg_quote($username, '/');
	$pattern = "/('" . $username_quoted . "'\\s*=>\\s*')([^']+)(')/";
	if (! preg_match($pattern, $content)) {
		return array('ok' => FALSE, 'error' => 'user_not_found_in_ini', 'manual' => TRUE);
	}

	$new_content = preg_replace_callback(
		$pattern,
		function ($matches) use ($new_hash) {
			return $matches[1] . $new_hash . $matches[3];
		},
		$content,
		1
	);
	if ($new_content === NULL || $new_content === $content) {
		return array('ok' => FALSE, 'error' => 'ini_update_failed', 'manual' => TRUE);
	}

	$tmp = dirname($real_ini) . DIRECTORY_SEPARATOR . '.pukiwiki.ini.php.' . getmypid() . '.tmp';
	if (file_put_contents($tmp, $new_content, LOCK_EX) === FALSE) {
		@unlink($tmp);
		return array('ok' => FALSE, 'error' => 'ini_write_failed', 'manual' => TRUE);
	}
	if (! @rename($tmp, $real_ini)) {
		@unlink($tmp);
		return array('ok' => FALSE, 'error' => 'ini_rename_failed', 'manual' => TRUE);
	}

	if (is_array($auth_users) && array_key_exists($username, $auth_users)) {
		$auth_users[$username] = $new_hash;
	}

	return array('ok' => TRUE);
}

/**
 * Update one $auth_users entry in pukiwiki.ini.php (hash value only).
 *
 * On writable failure, tries Unix permission fix once and retries save once.
 *
 * @param string $username
 * @param string $new_hash
 * @return array{ok:bool,error?:string,manual?:bool}
 */
function pkwk_ini_update_auth_user_hash($username, $new_hash)
{
	if (! preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
		return array('ok' => FALSE, 'error' => 'invalid_username');
	}
	if (! pkwk_ini_is_valid_auth_hash($new_hash)) {
		return array('ok' => FALSE, 'error' => 'invalid_hash');
	}
	if (! defined('INI_FILE')) {
		return array('ok' => FALSE, 'error' => 'ini_not_configured');
	}

	$ini_file = INI_FILE;
	$result = pkwk_ini_attempt_write_auth_user_hash($username, $new_hash, $ini_file);
	if (! empty($result['ok'])) {
		return $result;
	}
	if (! empty($result['manual'])) {
		require_once(LIB_DIR . 'perm.php');
		if (pkwk_perm_try_fix_ini_writable($ini_file)) {
			$result = pkwk_ini_attempt_write_auth_user_hash($username, $new_hash, $ini_file);
		}
	}

	return $result;
}
