<?php
// PukiWiki2026 - oEmbed consumer library
// oembed.php
// License: GPL v2 or (at your option) any later version
//
// oEmbed consumer with SSRF / XSS mitigation (no third-party proxy)

if (! defined('PKWK_OEMBED_HTTP_TIMEOUT')) {
	define('PKWK_OEMBED_HTTP_TIMEOUT', 10);
}

if (! defined('PKWK_OEMBED_DISCOVERY_MAX_BYTES')) {
	define('PKWK_OEMBED_DISCOVERY_MAX_BYTES', 65536);
}

if (! defined('PKWK_OEMBED_BLOCKED_NETS')) {
	define('PKWK_OEMBED_BLOCKED_NETS', serialize(array(
		'127.0.0.0/8',
		'10.0.0.0/8',
		'172.16.0.0/12',
		'192.168.0.0/16',
		'169.254.0.0/16',
		'0.0.0.0/8',
		'224.0.0.0/4',
	)));
}

if (! defined('PKWK_OEMBED_BLOCKED_HOSTS')) {
	define('PKWK_OEMBED_BLOCKED_HOSTS', serialize(array(
		'localhost',
		'localhost.localdomain',
	)));
}

if (! defined('PKWK_OEMBED_IFRAME_HOSTS')) {
	define('PKWK_OEMBED_IFRAME_HOSTS', serialize(array(
		'www.youtube.com',
		'youtube.com',
		'www.youtube-nocookie.com',
		'player.vimeo.com',
		'platform.twitter.com',
		'platform.x.com',
		'www.flickr.com',
		'flickr.com',
	)));
}

function pkwk_oembed_config()
{
	global $oembed_enabled, $oembed_standalone_url, $oembed_providers;
	global $oembed_maxwidth, $oembed_maxheight, $oembed_cache_hours;

	static $cfg = null;
	if ($cfg !== null) return $cfg;

	$cfg = array(
		'enabled'         => isset($oembed_enabled) ? (bool)$oembed_enabled : TRUE,
		'standalone_url'  => isset($oembed_standalone_url) ? (bool)$oembed_standalone_url : TRUE,
		'providers'       => isset($oembed_providers) && is_array($oembed_providers)
			? array_map('strtolower', $oembed_providers)
			: array('youtube', 'vimeo', 'twitter', 'flickr'),
		'maxwidth'        => isset($oembed_maxwidth) ? (int)$oembed_maxwidth : 640,
		'maxheight'       => isset($oembed_maxheight) ? (int)$oembed_maxheight : 480,
		'cache_hours'     => isset($oembed_cache_hours) ? (float)$oembed_cache_hours : 24,
	);
	return $cfg;
}

function pkwk_oembed_provider_registry()
{
	return array(
		'youtube' => array(
			'pattern'  => '#^https://(?:www\.)?(?:youtube\.com/watch\?(?:[^&]*&)*v=[\w-]+|youtu\.be/[\w-]+)(?:[&?][^\s]*)?$#i',
			'endpoint' => 'https://www.youtube.com/oembed',
		),
		'vimeo' => array(
			'pattern'  => '#^https://(?:www\.)?vimeo\.com/\d+(?:[/?][^\s]*)?$#i',
			'endpoint' => 'https://vimeo.com/api/oembed.json',
		),
		'twitter' => array(
			'pattern'  => '#^https://(?:(?:www\.)?(?:twitter|x)\.com)/[\w]+/status/\d+(?:[/?][^\s]*)?$#i',
			'endpoint' => 'https://publish.twitter.com/oembed',
		),
		'flickr' => array(
			'pattern'  => '#^https://(?:www\.)?flickr\.com/photos/[^\s/]+/\d+(?:[/?][^\s]*)?$#i',
			'endpoint' => 'https://www.flickr.com/services/oembed',
		),
	);
}

function pkwk_oembed_is_enabled()
{
	$cfg = pkwk_oembed_config();
	return $cfg['enabled'];
}

function pkwk_oembed_blocked_nets()
{
	return unserialize(PKWK_OEMBED_BLOCKED_NETS);
}

function pkwk_oembed_blocked_hosts()
{
	return unserialize(PKWK_OEMBED_BLOCKED_HOSTS);
}

function pkwk_oembed_iframe_hosts()
{
	return unserialize(PKWK_OEMBED_IFRAME_HOSTS);
}

function pkwk_oembed_normalize_url($url)
{
	$url = trim($url);
	if ($url === '') return FALSE;
	if (! is_url($url, TRUE)) return FALSE;
	return $url;
}

function pkwk_oembed_host_is_blocked($host)
{
	$host = strtolower(trim($host, '[]'));
	if ($host === '') return TRUE;

	foreach (pkwk_oembed_blocked_hosts() as $blocked) {
		if ($host === strtolower($blocked)) return TRUE;
	}

	if (preg_match('/\.(local|internal|localhost|lan|home|corp|localdomain)$/', $host)) {
		return TRUE;
	}

	if (in_the_net(pkwk_oembed_blocked_nets(), $host)) {
		return TRUE;
	}

	return FALSE;
}

function pkwk_oembed_url_is_safe($url)
{
	$url = pkwk_oembed_normalize_url($url);
	if ($url === FALSE) return FALSE;

	$parts = parse_url($url);
	if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) return FALSE;
	if (! in_array(strtolower($parts['scheme']), array('http', 'https'), TRUE)) return FALSE;

	$host = strtolower($parts['host']);
	if (pkwk_oembed_host_is_blocked($host)) return FALSE;

	$ip = gethostbyname($host);
	if ($ip === $host && ! preg_match('/^\d{1,3}(?:\.\d{1,3}){3}$/', $host)) {
		// DNS resolution failed for non-literal host
		return FALSE;
	}
	if (pkwk_oembed_host_is_blocked($ip)) return FALSE;

	return $url;
}

function pkwk_oembed_endpoint_is_safe($endpoint)
{
	$endpoint = pkwk_oembed_normalize_url($endpoint);
	if ($endpoint === FALSE) return FALSE;

	$parts = parse_url($endpoint);
	if (strtolower($parts['scheme']) !== 'https') return FALSE;

	return pkwk_oembed_url_is_safe($endpoint);
}

function pkwk_oembed_match_provider($url)
{
	$cfg = pkwk_oembed_config();
	$registry = pkwk_oembed_provider_registry();

	foreach ($cfg['providers'] as $name) {
		if (! isset($registry[$name])) continue;
		if (preg_match($registry[$name]['pattern'], $url)) {
			return array(
				'name'     => $name,
				'endpoint' => $registry[$name]['endpoint'],
			);
		}
	}
	return FALSE;
}

function pkwk_oembed_build_endpoint_url($endpoint, $page_url)
{
	$cfg = pkwk_oembed_config();
	$params = array(
		'url'    => $page_url,
		'format' => 'json',
	);
	if ($cfg['maxwidth'] > 0) $params['maxwidth'] = $cfg['maxwidth'];
	if ($cfg['maxheight'] > 0) $params['maxheight'] = $cfg['maxheight'];

	$sep = (strpos($endpoint, '?') === FALSE) ? '?' : '&';
	return $endpoint . $sep . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
}

function pkwk_oembed_fetch_json($url)
{
	if (! pkwk_oembed_url_is_safe($url)) return FALSE;

	$response = pkwk_http_request($url, 'GET', "Accept: application/json\r\n");
	if (! is_array($response) || (int)$response['rc'] !== 200) return FALSE;
	if ($response['data'] === '') return FALSE;

	$data = json_decode($response['data'], TRUE);
	if (! is_array($data)) return FALSE;
	return $data;
}

function pkwk_oembed_discover_endpoint($page_url)
{
	if (! pkwk_oembed_url_is_safe($page_url)) return FALSE;

	$response = pkwk_http_request($page_url, 'GET', "Accept: text/html\r\n");
	if (! is_array($response) || (int)$response['rc'] !== 200) return FALSE;

	$html = substr($response['data'], 0, PKWK_OEMBED_DISCOVERY_MAX_BYTES);
	if ($html === '') return FALSE;

	$matches = array();
	if (! preg_match_all('/<link\b[^>]*>/i', $html, $matches)) {
		return FALSE;
	}

	foreach ($matches[0] as $tag) {
		if (! preg_match('/\brel=["\']alternate["\']/i', $tag)) continue;
		if (! preg_match('/\btype=["\'](?:application\/json\+oembed|text\/json\+oembed)["\']/i', $tag)) {
			continue;
		}
		if (! preg_match('/\bhref=["\']([^"\']+)["\']/i', $tag, $href_match)) continue;
		$endpoint = html_entity_decode($href_match[1], ENT_QUOTES, 'UTF-8');
		if (strpos($endpoint, '//') === 0) {
			$endpoint = 'https:' . $endpoint;
		} else if (strpos($endpoint, '/') === 0) {
			$parts = parse_url($page_url);
			$endpoint = $parts['scheme'] . '://' . $parts['host'] . $endpoint;
		}
		if (pkwk_oembed_endpoint_is_safe($endpoint)) return $endpoint;
	}

	return FALSE;
}

function pkwk_oembed_cache_dir()
{
	$dir = CACHE_DIR . 'oembed/';
	if (! is_dir($dir)) {
		@mkdir($dir, 0777, TRUE);
	}
	return $dir;
}

function pkwk_oembed_cache_path($url)
{
	return pkwk_oembed_cache_dir() . md5($url) . '.json';
}

function pkwk_oembed_cache_get($url)
{
	$cfg = pkwk_oembed_config();
	if ($cfg['cache_hours'] <= 0) return FALSE;

	$path = pkwk_oembed_cache_path($url);
	if (! is_readable($path)) return FALSE;

	$raw = @file_get_contents($path);
	if ($raw === FALSE || $raw === '') return FALSE;

	$data = json_decode($raw, TRUE);
	if (! is_array($data) || ! isset($data['time'], $data['html'])) return FALSE;

	if (UTIME - (int)$data['time'] > (int)($cfg['cache_hours'] * 3600)) {
		@unlink($path);
		return FALSE;
	}

	return $data['html'];
}

function pkwk_oembed_cache_set($url, $html)
{
	$cfg = pkwk_oembed_config();
	if ($cfg['cache_hours'] <= 0) return;

	$path = pkwk_oembed_cache_path($url);
	$payload = json_encode(array(
		'time' => UTIME,
		'html' => $html,
	));
	if ($payload === FALSE) return;
	@file_put_contents($path, $payload, LOCK_EX);
}

function pkwk_oembed_sanitize_html($html)
{
	if ($html === '') return '';

	$allowed = array(
		'iframe'     => array('src', 'width', 'height', 'frameborder', 'allow', 'allowfullscreen', 'title', 'loading', 'referrerpolicy'),
		'blockquote' => array('class', 'cite'),
		'a'          => array('href', 'title', 'rel', 'target'),
		'p'          => array('class'),
		'div'        => array('class'),
		'span'       => array('class'),
		'img'        => array('src', 'alt', 'width', 'height', 'title'),
	);

	if (class_exists('DOMDocument')) {
		$doc = new DOMDocument();
		$prev = libxml_use_internal_errors(TRUE);
		$wrapped = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body><div id="oembed-root">' .
			$html . '</div></body></html>';
		if (! $doc->loadHTML($wrapped, LIBXML_HTML_NODEFDTD | LIBXML_NOERROR)) {
			libxml_clear_errors();
			libxml_use_internal_errors($prev);
			return '';
		}
		libxml_clear_errors();
		libxml_use_internal_errors($prev);

		$root = $doc->getElementById('oembed-root');
		if (! $root) return '';

		pkwk_oembed_sanitize_node($root, $allowed);
		$out = '';
		foreach ($root->childNodes as $child) {
			$out .= $doc->saveHTML($child);
		}
		return $out;
	}

	// Fallback: strip tags except iframe/blockquote and sanitize iframe src
	$html = preg_replace('/<(script|object|embed|form|link|meta|style)\b[^>]*>.*?<\/\1>/is', '', $html);
	$html = preg_replace('/<(script|object|embed|form|link|meta|style)\b[^>]*\/?>/is', '', $html);
	$html = preg_replace('/\s(on\w+|style)\s*=\s*(["\']).*?\2/i', '', $html);

	if (preg_match_all('/<iframe\b[^>]*>/i', $html, $iframes)) {
		foreach ($iframes[0] as $tag) {
			if (! preg_match('/\bsrc=["\']([^"\']+)["\']/i', $tag, $src_match)) {
				$html = str_replace($tag, '', $html);
				continue;
			}
			if (! pkwk_oembed_iframe_src_is_allowed($src_match[1])) {
				$html = str_replace($tag, '', $html);
			}
		}
	}

	return $html;
}

function pkwk_oembed_sanitize_node(DOMNode $node, array $allowed)
{
	if (! $node->hasChildNodes()) return;

	$remove = array();
	foreach ($node->childNodes as $child) {
		if ($child->nodeType === XML_TEXT_NODE) continue;
		if ($child->nodeType !== XML_ELEMENT_NODE) {
			$remove[] = $child;
			continue;
		}

		$tag = strtolower($child->nodeName);
		if (! isset($allowed[$tag])) {
			while ($child->firstChild) {
				$node->insertBefore($child->firstChild, $child);
			}
			$remove[] = $child;
			continue;
		}

		if ($child->hasAttributes()) {
			$drop = array();
			foreach ($child->attributes as $attr) {
				$name = strtolower($attr->name);
				if (! in_array($name, $allowed[$tag], TRUE)) {
					$drop[] = $name;
					continue;
				}
				if ($name === 'src' && $tag === 'iframe' &&
				    ! pkwk_oembed_iframe_src_is_allowed($attr->value)) {
					$drop[] = $name;
				}
				if (($name === 'href' || $name === 'src') &&
				    preg_match('/^\s*javascript:/i', $attr->value)) {
					$drop[] = $name;
				}
			}
			foreach ($drop as $name) {
				$child->removeAttribute($name);
			}
		}

		pkwk_oembed_sanitize_node($child, $allowed);
	}

	foreach ($remove as $child) {
		$node->removeChild($child);
	}
}

function pkwk_oembed_iframe_src_is_allowed($src)
{
	$src = trim($src);
	if ($src === '' || preg_match('/^\s*javascript:/i', $src)) return FALSE;

	$parts = parse_url($src);
	if (! is_array($parts) || ! isset($parts['host'])) return FALSE;
	if (! isset($parts['scheme']) || strtolower($parts['scheme']) !== 'https') return FALSE;

	$host = strtolower($parts['host']);
	foreach (pkwk_oembed_iframe_hosts() as $allowed) {
		if ($host === $allowed || substr($host, -strlen('.' . $allowed)) === '.' . $allowed) {
			return TRUE;
		}
	}
	return FALSE;
}

function pkwk_oembed_fetch_response($url)
{
	$url = pkwk_oembed_normalize_url($url);
	if ($url === FALSE) return FALSE;
	if (! pkwk_oembed_url_is_safe($url)) return FALSE;

	$cached = pkwk_oembed_cache_get($url);
	if ($cached !== FALSE) return $cached;

	$provider = pkwk_oembed_match_provider($url);
	$endpoint = ($provider !== FALSE) ? $provider['endpoint'] : pkwk_oembed_discover_endpoint($url);
	if ($endpoint === FALSE) return FALSE;
	if (! pkwk_oembed_endpoint_is_safe($endpoint)) return FALSE;

	$request_url = pkwk_oembed_build_endpoint_url($endpoint, $url);
	$data = pkwk_oembed_fetch_json($request_url);
	if ($data === FALSE || empty($data['html'])) return FALSE;

	$html = pkwk_oembed_sanitize_html($data['html']);
	if ($html === '') return FALSE;

	pkwk_oembed_cache_set($url, $html);
	return $html;
}

function pkwk_oembed_render($url)
{
	if (! pkwk_oembed_is_enabled()) return FALSE;

	$html = pkwk_oembed_fetch_response($url);
	if ($html === FALSE) return FALSE;

	return '<div class="oembed">' . $html . '</div>';
}

function pkwk_oembed_render_standalone_line($text)
{
	$cfg = pkwk_oembed_config();
	if (! $cfg['enabled'] || ! $cfg['standalone_url']) return FALSE;

	$line = trim($text);
	if ($line === '' || strpos($line, "\n") !== FALSE) return FALSE;
	if (! preg_match('#^https?://[^\s<>"\'\]]+$#i', $line)) return FALSE;

	return pkwk_oembed_render($line);
}

function pkwk_oembed_fallback_link($url)
{
	$url = htmlsc($url);
	return '<a href="' . $url . '" rel="nofollow">' . $url . '</a>';
}
