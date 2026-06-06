<?php
// PukiWiki2026 - Akismet spam check library
// akismet.php
// License: GPL v2 or (at your option) any later version
//
// Akismet REST API comment-check (no Composer dependency)

if (! defined('PKWK_AKISMET_HTTP_TIMEOUT')) {
	define('PKWK_AKISMET_HTTP_TIMEOUT', 10);
}

if (! defined('PKWK_AKISMET_API_HOST')) {
	define('PKWK_AKISMET_API_HOST', 'rest.akismet.com');
}

/**
 * @return array{enabled:bool,api_key:string,blog_url:string,strict:bool}
 */
function pkwk_akismet_config()
{
	global $akismet_enabled, $akismet_api_key, $akismet_blog_url, $akismet_strict;

	static $cfg = null;
	if ($cfg !== null) return $cfg;

	$cfg = array(
		'enabled'  => ! empty($akismet_enabled),
		'api_key'  => isset($akismet_api_key) ? trim((string)$akismet_api_key) : '',
		'blog_url' => isset($akismet_blog_url) ? trim((string)$akismet_blog_url) : '',
		'strict'   => ! empty($akismet_strict),
	);
	return $cfg;
}

function pkwk_akismet_is_enabled()
{
	$cfg = pkwk_akismet_config();
	return $cfg['enabled'] && $cfg['api_key'] !== '';
}

function pkwk_akismet_blog_url()
{
	$cfg = pkwk_akismet_config();
	if ($cfg['blog_url'] !== '') {
		return $cfg['blog_url'];
	}
	return get_base_uri(PKWK_URI_ABSOLUTE);
}

function pkwk_akismet_client_ip()
{
	return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
}

function pkwk_akismet_user_agent()
{
	return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
}

function pkwk_akismet_referrer()
{
	return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
}

function pkwk_akismet_comment_author()
{
	global $auth_user, $auth_user_fullname;

	if ($auth_user !== '') {
		return $auth_user_fullname !== '' ? $auth_user_fullname : $auth_user;
	}
	return 'anonymous';
}

/**
 * Call Akismet comment-check.
 *
 * @return string 'ham' | 'spam' | 'error'
 */
function pkwk_akismet_check($page, $content)
{
	if (! pkwk_akismet_is_enabled()) {
		return 'ham';
	}

	$cfg = pkwk_akismet_config();
	$url = 'https://' . $cfg['api_key'] . '.' . PKWK_AKISMET_API_HOST . '/1.1/comment-check';

	$post = array(
		'blog'             => pkwk_akismet_blog_url(),
		'user_ip'          => pkwk_akismet_client_ip(),
		'user_agent'       => pkwk_akismet_user_agent(),
		'referrer'         => pkwk_akismet_referrer(),
		'permalink'        => get_page_uri($page, PKWK_URI_ABSOLUTE),
		'comment_type'     => 'wiki',
		'comment_author'   => pkwk_akismet_comment_author(),
		'comment_content'  => $content,
	);

	$response = pkwk_akismet_http_post($url, $post);
	if ($response === FALSE) {
		return 'error';
	}

	$body = strtolower(trim($response));
	if ($body === 'true') {
		return 'spam';
	}
	if ($body === 'false') {
		return 'ham';
	}
	return 'error';
}

/**
 * @param array<string,string> $post
 * @return string|false Response body on success, FALSE on transport/HTTP failure
 */
function pkwk_akismet_http_post($url, $post)
{
	$headers = "Content-Type: application/x-www-form-urlencoded\r\n";

	$response = pkwk_http_request($url, 'POST', $headers, $post);
	if (! is_array($response)) {
		return FALSE;
	}
	if ((int)$response['rc'] !== 200) {
		return FALSE;
	}
	return $response['data'];
}

/**
 * Block page write when Akismet flags spam (or strict mode API error).
 * Called from page_write() before persisting changed non-empty content.
 *
 * @param string $page
 * @param string $content Wiki text without author footer
 */
function pkwk_akismet_verify_write_or_die($page, $content)
{
	if (! pkwk_akismet_is_enabled()) {
		return;
	}
	if (trim($content) === '') {
		return;
	}

	$result = pkwk_akismet_check($page, $content);

	if ($result === 'spam') {
		die_message('投稿内容がスパムと判定されたため、保存できません。内容を見直すか、管理者にお問い合わせください。');
	}
	if ($result === 'error') {
		$cfg = pkwk_akismet_config();
		if ($cfg['strict']) {
			die_message('スパム判定サービス（Akismet）に接続できないため、保存を中断しました。しばらくしてから再試行するか、管理者に連絡してください。');
		}
	}
}
