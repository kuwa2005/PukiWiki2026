<?php
// PukiWiki2026 - Guest comment plugin helpers
// comment.php
// License: GPL v2 or (at your option) any later version

/**
 * @return array{auth_required:bool,captcha_enabled:bool,rate_limit_max:int,rate_limit_window:int}
 */
function pkwk_comment_config()
{
	global $comment_auth, $comment_captcha_enabled, $comment_rate_limit_max, $comment_rate_limit_window;

	static $cfg = null;
	if ($cfg !== null) return $cfg;

	$cfg = array(
		'auth_required'      => ! empty($comment_auth),
		'captcha_enabled'    => ! isset($comment_captcha_enabled) || ! empty($comment_captcha_enabled),
		'rate_limit_max'     => isset($comment_rate_limit_max) ? (int)$comment_rate_limit_max : 10,
		'rate_limit_window'  => isset($comment_rate_limit_window) ? (int)$comment_rate_limit_window : 3600,
	);
	return $cfg;
}

function pkwk_comment_auth_required()
{
	return pkwk_comment_config()['auth_required'];
}

function pkwk_is_comment_plugin($plugin)
{
	return in_array($plugin, array('comment', 'pcomment', 'article'), TRUE);
}

/**
 * Whether anonymous comment POST should bypass $edit_auth gates.
 *
 * @param array $vars
 * @return bool
 */
function pkwk_guest_comment_allowed($vars)
{
	if (pkwk_comment_auth_required() || pkwk_is_authenticated()) {
		return FALSE;
	}
	if (! isset($vars['plugin']) || ! pkwk_is_comment_plugin($vars['plugin'])) {
		return FALSE;
	}
	if (! isset($vars['msg']) || trim((string)$vars['msg']) === '') {
		return FALSE;
	}
	return TRUE;
}

/**
 * Check page allows guest BBS-style insertion (comment / article; freeze OK).
 *
 * @param string $page
 * @param bool $auth_enabled
 * @param bool $exit_on_fail
 * @return bool
 */
function check_commentable($page, $auth_enabled = TRUE, $exit_on_fail = TRUE)
{
	global $_title_cannotedit;

	if (! is_pagename($page)) {
		if ($exit_on_fail === FALSE) {
			return FALSE;
		}
		die_message(str_replace('$1', htmlsc($page), $_title_cannotedit));
	}

	if (pkwk_comment_auth_required()) {
		return edit_auth($page, $auth_enabled, $exit_on_fail);
	}

	if (is_page($page)) {
		return check_readable($page, $auth_enabled, $exit_on_fail);
	}

	return TRUE;
}

/** @var bool */
$_pkwk_comment_write_in_progress = FALSE;

function pkwk_comment_write_begin()
{
	global $_pkwk_comment_write_in_progress;
	$_pkwk_comment_write_in_progress = TRUE;
}

function pkwk_comment_write_end()
{
	global $_pkwk_comment_write_in_progress;
	$_pkwk_comment_write_in_progress = FALSE;
}

function pkwk_comment_write_in_progress()
{
	global $_pkwk_comment_write_in_progress;
	return $_pkwk_comment_write_in_progress;
}

/**
 * Anti-spam checks for guest comment POST (CAPTCHA + rate limit).
 *
 * @param string $page
 * @param string $content Wiki text without author footer
 */
function pkwk_comment_antispam_verify_or_die($page, $content)
{
	if (pkwk_is_authenticated() || pkwk_comment_auth_required()) {
		return;
	}
	if (trim($content) === '') {
		return;
	}

	pkwk_comment_rate_limit_check_or_die();
	pkwk_captcha_verify_comment_or_die($content);
}

/**
 * Record a successful guest comment for rate limiting.
 */
function pkwk_comment_rate_limit_record()
{
	if (pkwk_is_authenticated() || pkwk_comment_auth_required()) {
		return;
	}

	$cfg = pkwk_comment_config();
	if ($cfg['rate_limit_max'] <= 0 || $cfg['rate_limit_window'] <= 0) {
		return;
	}

	$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
	$key = hash('sha256', $ip);
	$dir = CACHE_DIR . 'comment_ratelimit/';
	if (! is_dir($dir)) {
		@mkdir($dir, 0755, TRUE);
	}
	$file = $dir . $key . '.json';
	$now = time();
	$timestamps = array();

	if (is_file($file)) {
		$raw = @file_get_contents($file);
		if ($raw !== FALSE) {
			$decoded = json_decode($raw, TRUE);
			if (is_array($decoded) && isset($decoded['timestamps']) && is_array($decoded['timestamps'])) {
				$timestamps = $decoded['timestamps'];
			}
		}
	}

	$cutoff = $now - $cfg['rate_limit_window'];
	$timestamps = array_values(array_filter($timestamps, function ($ts) use ($cutoff) {
		return (int)$ts > $cutoff;
	}));
	$timestamps[] = $now;

	@file_put_contents($file, json_encode(array('timestamps' => $timestamps)), LOCK_EX);
}

function pkwk_comment_rate_limit_check_or_die()
{
	$cfg = pkwk_comment_config();
	if ($cfg['rate_limit_max'] <= 0 || $cfg['rate_limit_window'] <= 0) {
		return;
	}

	$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
	$key = hash('sha256', $ip);
	$file = CACHE_DIR . 'comment_ratelimit/' . $key . '.json';
	$now = time();
	$timestamps = array();

	if (is_file($file)) {
		$raw = @file_get_contents($file);
		if ($raw !== FALSE) {
			$decoded = json_decode($raw, TRUE);
			if (is_array($decoded) && isset($decoded['timestamps']) && is_array($decoded['timestamps'])) {
				$timestamps = $decoded['timestamps'];
			}
		}
	}

	$cutoff = $now - $cfg['rate_limit_window'];
	$recent = array_filter($timestamps, function ($ts) use ($cutoff) {
		return (int)$ts > $cutoff;
	});

	if (count($recent) >= $cfg['rate_limit_max']) {
		die_message('投稿回数が多すぎます。しばらく時間をおいてから再試行してください。');
	}
}
