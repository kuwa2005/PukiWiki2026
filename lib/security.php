<?php
// PukiWiki2026 - Security helpers
// License: GPL v2 or (at your option) any later version

/**
 * Verify passphrase against stored hash (supports legacy + password_hash).
 *
 * @param string $phrase
 * @param string $stored
 * @return bool
 */
function pkwk_hash_verify($phrase, $stored)
{
	if (! is_string($phrase) || ! is_string($stored)) {
		return FALSE;
	}

	// password_hash() output stored with scheme prefix
	if (preg_match('/^\{x-php-password\}(.+)$/', $stored, $m)) {
		return password_verify($phrase, $m[1]);
	}
	// password_hash() output stored directly
	if (preg_match('/^\$(2[ayb]|argon2)/', $stored)) {
		return password_verify($phrase, $stored);
	}

	return pkwk_hash_compute($phrase, $stored) === $stored;
}

/**
 * Hash passphrase for storage (prefers password_hash).
 *
 * @param string $phrase
 * @return string
 */
function pkwk_hash_store($phrase)
{
	return '{x-php-password}' . password_hash($phrase, PASSWORD_DEFAULT);
}

/**
 * Safe redirect URL — same-origin only (SEC-H04).
 *
 * @param string $url
 * @return string Empty string if unsafe
 */
function pkwk_safe_redirect_url($url)
{
	if ($url === '' || ! is_string($url)) {
		return '';
	}

	// Relative path on same site
	if ($url[0] === '/' && (strlen($url) < 2 || $url[1] !== '/')) {
		return $url;
	}
	if (preg_match('/^[?#]/', $url)) {
		return $url;
	}

	$parts = parse_url($url);
	if ($parts === FALSE) {
		return '';
	}

	if (isset($parts['host'])) {
		$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
		if ($host === '' || strcasecmp($parts['host'], $host) !== 0) {
			return '';
		}
	}

	if (isset($parts['scheme']) &&
		! in_array(strtolower($parts['scheme']), array('http', 'https'), TRUE)) {
		return '';
	}

	return $url;
}

/**
 * Whether remote address is a trusted reverse proxy (SEC-H03).
 *
 * @param string $remote_addr
 * @param array  $trusted_proxies
 * @return bool
 */
function pkwk_is_trusted_proxy($remote_addr, $trusted_proxies)
{
	if ($remote_addr === '' || ! is_array($trusted_proxies)) {
		return FALSE;
	}
	return in_array($remote_addr, $trusted_proxies, TRUE);
}

/**
 * Login brute-force rate limit check (SEC-H02 / SPAM-03).
 *
 * @return bool TRUE if attempt is allowed
 */
function pkwk_login_rate_limit_check()
{
	$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
	$key = hash('sha256', $ip);
	$file = CACHE_DIR . 'login_ratelimit/' . $key . '.json';
	$now = time();
	$data = array('failures' => 0, 'locked_until' => 0);

	if (is_file($file)) {
		$raw = @file_get_contents($file);
		if ($raw !== FALSE) {
			$decoded = json_decode($raw, TRUE);
			if (is_array($decoded)) {
				$data = $decoded;
			}
		}
	}

	if ($data['locked_until'] > $now) {
		return FALSE;
	}
	return TRUE;
}

/**
 * Record failed login attempt.
 */
function pkwk_login_rate_limit_fail()
{
	$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
	$key = hash('sha256', $ip);
	$dir = CACHE_DIR . 'login_ratelimit/';
	if (! is_dir($dir)) {
		@mkdir($dir, 0755, TRUE);
	}
	$file = $dir . $key . '.json';
	$now = time();
	$data = array('failures' => 0, 'locked_until' => 0);

	if (is_file($file)) {
		$raw = @file_get_contents($file);
		if ($raw !== FALSE) {
			$decoded = json_decode($raw, TRUE);
			if (is_array($decoded)) {
				$data = $decoded;
			}
		}
	}

	$data['failures'] = isset($data['failures']) ? (int)$data['failures'] + 1 : 1;
	$delay = min(300, (int)pow(2, min($data['failures'], 8)));
	$data['locked_until'] = $now + $delay;

	@file_put_contents($file, json_encode($data), LOCK_EX);
}

/**
 * Reset login rate limit after success.
 */
function pkwk_login_rate_limit_reset()
{
	$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
	$key = hash('sha256', $ip);
	$file = CACHE_DIR . 'login_ratelimit/' . $key . '.json';
	if (is_file($file)) {
		@unlink($file);
	}
}

/**
 * Safe PKWK_UPDATE_EXEC runner — whitelist only (SEC-C01).
 */
function pkwk_run_update_exec()
{
	if (! defined('PKWK_UPDATE_EXEC') || PKWK_UPDATE_EXEC === '') {
		return;
	}

	$cmd = PKWK_UPDATE_EXEC;
	$allowed = defined('PKWK_UPDATE_EXEC_ALLOWED')
		? PKWK_UPDATE_EXEC_ALLOWED
		: array();

	if (! empty($allowed) && ! in_array($cmd, $allowed, TRUE)) {
		return;
	}

	if (! is_file($cmd) || ! is_executable($cmd)) {
		return;
	}

	$null = (DIRECTORY_SEPARATOR === '\\') ? 'NUL' : '/dev/null';
	$descriptors = array(
		0 => array('pipe', 'r'),
		1 => array('file', $null, 'w'),
		2 => array('file', $null, 'w'),
	);
	$proc = @proc_open(array($cmd), $descriptors, $pipes, NULL, NULL);
	if (is_resource($proc)) {
		@proc_close($proc);
	}
}

/**
 * PHP 8 compatible set_file_buffer replacement (SEC-M08).
 *
 * @param resource $fp
 * @param int      $size
 * @return bool|int
 */
function pkwk_set_file_buffer($fp, $size)
{
	if (function_exists('stream_set_write_buffer')) {
		return stream_set_write_buffer($fp, $size);
	}
	if (function_exists('set_file_buffer')) {
		return set_file_buffer($fp, $size);
	}
	return FALSE;
}

/**
 * Attach password hash for storage (SEC-M02).
 *
 * @param string $pass
 * @return string
 */
function pkwk_attach_hash_password($pass)
{
	return password_hash($pass, PASSWORD_DEFAULT);
}

/**
 * Verify attach password (legacy MD5 + password_hash).
 *
 * @param string $pass
 * @param string $stored
 * @return bool
 */
function pkwk_attach_verify_password($pass, $stored)
{
	if ($stored === TRUE || $stored === NULL || $stored === '') {
		return ($pass === TRUE || $pass === NULL || $pass === '');
	}
	if (preg_match('/^\$(2[ayb]|argon2)/', $stored)) {
		return password_verify($pass, $stored);
	}
	return md5($pass) === $stored;
}

/**
 * Content-Disposition for attach download (SEC-H06).
 *
 * @param string $type
 * @param string $filename
 * @return string inline|attachment
 */
function pkwk_attach_content_disposition($type, $filename)
{
	$type = strtolower($type);
	$dangerous_types = array(
		'text/html', 'application/xhtml+xml', 'image/svg+xml',
		'application/javascript', 'text/javascript', 'text/xml',
		'application/xml',
	);
	if (in_array($type, $dangerous_types, TRUE)) {
		return 'attachment';
	}
	if (preg_match('/\.(html?|htm|svg|xml|js|xhtml)$/i', $filename)) {
		return 'attachment';
	}
	return 'inline';
}

/**
 * Validate external URL for ref plugin SSRF guard (SEC-M07).
 *
 * @param string $url
 * @return bool
 */
function pkwk_is_safe_external_url($url)
{
	$parts = @parse_url($url);
	if ($parts === FALSE || empty($parts['scheme']) || empty($parts['host'])) {
		return FALSE;
	}
	if (! in_array(strtolower($parts['scheme']), array('http', 'https'), TRUE)) {
		return FALSE;
	}
	$host = strtolower($parts['host']);
	if ($host === 'localhost' || $host === '127.0.0.1' || $host === '::1') {
		return FALSE;
	}
	if (preg_match('/^(10\.|172\.(1[6-9]|2[0-9]|3[01])\.|192\.168\.|169\.254\.)/', $host)) {
		return FALSE;
	}
	return TRUE;
}
