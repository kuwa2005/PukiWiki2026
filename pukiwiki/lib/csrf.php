<?php
// PukiWiki2026 - CSRF protection
// License: GPL v2 or (at your option) any later version

/**
 * Set secure session cookie parameters (SEC-H07).
 */
function pkwk_session_set_cookie_params()
{
	$secure = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
	if (PHP_VERSION_ID >= 70300) {
		session_set_cookie_params(array(
			'lifetime' => 0,
			'path'     => '/',
			'secure'   => $secure,
			'httponly' => TRUE,
			'samesite' => 'Strict',
		));
	} else {
		session_set_cookie_params(0, '/; samesite=Strict', '', $secure, TRUE);
	}
}

/**
 * Start session if not already active.
 */
function pkwk_ensure_session()
{
	if (session_status() === PHP_SESSION_ACTIVE) {
		return;
	}
	pkwk_session_set_cookie_params();
	session_start();
}

/**
 * Get or create CSRF token for current session.
 *
 * @return string
 */
function pkwk_csrf_token()
{
	pkwk_ensure_session();
	if (empty($_SESSION['pkwk_csrf_token'])) {
		$_SESSION['pkwk_csrf_token'] = bin2hex(random_bytes(32));
	}
	return $_SESSION['pkwk_csrf_token'];
}

/**
 * HTML hidden input for CSRF token.
 *
 * @return string
 */
function pkwk_csrf_hidden_field()
{
	return '<input type="hidden" name="pkwk_csrf_token" value="' .
		htmlsc(pkwk_csrf_token()) . '" />' . "\n";
}

/**
 * Inject CSRF hidden fields into POST forms that lack a token (SEC-C02).
 *
 * @param string $html
 * @return string
 */
function pkwk_csrf_inject_forms($html)
{
	if ($html === '' || stripos($html, 'pkwk_csrf_token') !== FALSE) {
		return $html;
	}
	return preg_replace_callback(
		'/<form\b[^>]*\bmethod=(["\'])post\1[^>]*>/i',
		function ($m) {
			return $m[0] . "\n" . pkwk_csrf_hidden_field();
		},
		$html
	);
}

/**
 * POST plugins exempt from CSRF (read-only search etc.).
 *
 * @return array
 */
function pkwk_csrf_exempt_requests($vars)
{
	if (isset($vars['cmd']) && $vars['cmd'] === 'search') {
		return TRUE;
	}
	if (isset($vars['plugin']) && $vars['plugin'] === 'search') {
		return TRUE;
	}
	return FALSE;
}

/**
 * Verify CSRF token on POST requests (SEC-C02).
 *
 * @param array $vars
 */
function pkwk_csrf_verify_or_die($vars)
{
	if (! isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
		return;
	}
	if (empty($_POST) && empty($_FILES)) {
		return;
	}
	if (pkwk_csrf_exempt_requests($vars)) {
		return;
	}

	pkwk_ensure_session();
	$submitted = isset($_POST['pkwk_csrf_token']) ? $_POST['pkwk_csrf_token'] : '';
	$expected  = isset($_SESSION['pkwk_csrf_token']) ? $_SESSION['pkwk_csrf_token'] : '';

	if ($submitted !== '' && $expected !== '' && hash_equals($expected, $submitted)) {
		return;
	}
	if (pkwk_csrf_verify_same_origin()) {
		return;
	}
	die_message('<p>CSRF トークンの検証に失敗しました。ページを再読み込みしてやり直してください。</p>');
	exit;
}

/**
 * Verify Origin/Referer for same-site POST (CSRF defense layer).
 *
 * @return bool
 */
function pkwk_csrf_verify_same_origin()
{
	$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
	if ($host === '') {
		return FALSE;
	}
	if (isset($_SERVER['HTTP_ORIGIN'])) {
		$origin_host = parse_url($_SERVER['HTTP_ORIGIN'], PHP_URL_HOST);
		return ($origin_host && strcasecmp($origin_host, $host) === 0);
	}
	if (isset($_SERVER['HTTP_REFERER'])) {
		$referer_host = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
		return ($referer_host && strcasecmp($referer_host, $host) === 0);
	}
	return FALSE;
}
