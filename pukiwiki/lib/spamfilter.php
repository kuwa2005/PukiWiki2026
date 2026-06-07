<?php
// PukiWiki2026 - Write-side spam filter (SPAM-04)
// spamfilter.php
// License: GPL v2 or (at your option) any later version

/**
 * @return array{mode:int,allowlist:array<int,string>}
 */
function pkwk_spamfilter_external_links_config()
{
	global $spam_block_external_links, $spam_external_link_allowlist;

	static $cfg = null;
	if ($cfg !== null) return $cfg;

	$mode = isset($spam_block_external_links) ? (int)$spam_block_external_links : 0;
	$allowlist = array();
	if (isset($spam_external_link_allowlist) && is_array($spam_external_link_allowlist)) {
		foreach ($spam_external_link_allowlist as $domain) {
			$domain = strtolower(trim((string)$domain));
			if ($domain !== '') {
				$allowlist[] = $domain;
			}
		}
	}

	$cfg = array(
		'mode'      => $mode,
		'allowlist' => $allowlist,
	);
	return $cfg;
}

function pkwk_spamfilter_external_links_enabled()
{
	$cfg = pkwk_spamfilter_external_links_config();
	return $cfg['mode'] > 0;
}

/**
 * Wiki host from get_base_uri() (lowercase, without leading www.).
 *
 * @return string
 */
function pkwk_spamfilter_wiki_host()
{
	static $host = null;
	if ($host !== null) return $host;

	$base = get_base_uri(PKWK_URI_ABSOLUTE);
	$parsed = parse_url($base, PHP_URL_HOST);
	$host = $parsed ? strtolower((string)$parsed) : '';
	if (strpos($host, 'www.') === 0) {
		$host = substr($host, 4);
	}
	return $host;
}

/**
 * Normalize host for comparison (lowercase, strip www.).
 *
 * @param string $host
 * @return string
 */
function pkwk_spamfilter_normalize_host($host)
{
	$host = strtolower(trim($host));
	if (strpos($host, 'www.') === 0) {
		$host = substr($host, 4);
	}
	return $host;
}

/**
 * @param string $host
 * @param array<int,string> $allowlist
 * @param string $wiki_host
 * @return bool
 */
function pkwk_spamfilter_is_allowed_host($host, $allowlist, $wiki_host)
{
	$host = pkwk_spamfilter_normalize_host($host);
	if ($host === '' || $host === $wiki_host) {
		return TRUE;
	}
	foreach ($allowlist as $allowed) {
		$allowed = pkwk_spamfilter_normalize_host($allowed);
		if ($allowed === '') continue;
		if ($host === $allowed || substr($host, -strlen('.' . $allowed)) === '.' . $allowed) {
			return TRUE;
		}
	}
	return FALSE;
}

/**
 * Extract http/https URLs from wiki source text.
 *
 * @param string $content
 * @return array<int,string>
 */
function pkwk_spamfilter_extract_urls($content)
{
	$urls = array();
	if (preg_match_all('#https?://[^\s\]<>"\'\)\]]+#i', $content, $matches)) {
		foreach ($matches[0] as $url) {
			$url = rtrim($url, '.,;:!?)');
			$urls[] = $url;
		}
	}
	return array_values(array_unique($urls));
}

/**
 * Whether the current write is allowed to include external links (mode 2).
 *
 * @return bool
 */
function pkwk_spamfilter_is_admin_write()
{
	global $auth_user_groups;

	if (in_array('admin', $auth_user_groups, TRUE)) {
		return TRUE;
	}
	if (pkwk_is_authenticated()) {
		return TRUE;
	}
	if (isset($_POST['pass']) && pkwk_login($_POST['pass'])) {
		return TRUE;
	}
	if (isset($_POST['adminpass']) && pkwk_login($_POST['adminpass'])) {
		return TRUE;
	}
	return FALSE;
}

/**
 * Block page write when external links are disallowed (SPAM-04).
 * Called from page_write() after auth gate, before persist.
 *
 * @param string $content Wiki text without author footer
 */
function pkwk_spamfilter_verify_external_links_or_die($content)
{
	if (trim($content) === '') {
		return;
	}

	$cfg = pkwk_spamfilter_external_links_config();
	if ($cfg['mode'] <= 0) {
		return;
	}
	if ($cfg['mode'] === 2 && pkwk_spamfilter_is_admin_write()) {
		return;
	}

	$wiki_host = pkwk_spamfilter_wiki_host();
	$blocked = array();

	foreach (pkwk_spamfilter_extract_urls($content) as $url) {
		$host = parse_url($url, PHP_URL_HOST);
		if ($host === NULL || $host === FALSE || $host === '') {
			continue;
		}
		if (! pkwk_spamfilter_is_allowed_host($host, $cfg['allowlist'], $wiki_host)) {
			$blocked[] = $host;
		}
	}

	if (count($blocked) === 0) {
		return;
	}

	$blocked = array_values(array_unique($blocked));
	$hosts = htmlsc(join(', ', $blocked));
	if ($cfg['mode'] === 2) {
		die_message('外部サイトへのリンク（' . $hosts . '）は管理者のみ投稿できます。管理者パスワードを入力するか、管理者アカウントで保存してください。');
	}
	die_message('外部サイトへのリンク（' . $hosts . '）は投稿できません。自サイト内のリンクのみ使用するか、管理者に許可ドメインの追加を依頼してください。');
}
