<?php
// PukiWiki2026 - Edit CAPTCHA library (SPAM-02)
// captcha.php
// License: GPL v2 or (at your option) any later version

if (! defined('PKWK_CAPTCHA_RECAPTCHA_VERIFY_URL')) {
	define('PKWK_CAPTCHA_RECAPTCHA_VERIFY_URL', 'https://www.google.com/recaptcha/api/siteverify');
}

if (! defined('PKWK_CAPTCHA_RECAPTCHA_V3_THRESHOLD')) {
	define('PKWK_CAPTCHA_RECAPTCHA_V3_THRESHOLD', 0.5);
}

/**
 * @return array{enabled:bool,provider:string,site_key:string,secret_key:string}
 */
function pkwk_captcha_config()
{
	global $captcha_enabled, $captcha_provider, $recaptcha_site_key, $recaptcha_secret_key;

	static $cfg = null;
	if ($cfg !== null) return $cfg;

	$provider = isset($captcha_provider) ? trim((string)$captcha_provider) : 'recaptcha_v2';
	if (! in_array($provider, array('recaptcha_v2', 'recaptcha_v3', 'honeypot'), TRUE)) {
		$provider = 'recaptcha_v2';
	}

	$cfg = array(
		'enabled'    => ! empty($captcha_enabled),
		'provider'   => $provider,
		'site_key'   => isset($recaptcha_site_key) ? trim((string)$recaptcha_site_key) : '',
		'secret_key' => isset($recaptcha_secret_key) ? trim((string)$recaptcha_secret_key) : '',
	);
	return $cfg;
}

function pkwk_captcha_is_enabled()
{
	$cfg = pkwk_captcha_config();
	if (! $cfg['enabled']) {
		return FALSE;
	}
	if ($cfg['provider'] === 'honeypot') {
		return TRUE;
	}
	return $cfg['site_key'] !== '' && $cfg['secret_key'] !== '';
}

/**
 * HTML fragment for edit_form (before submit buttons).
 *
 * @return string
 */
function pkwk_captcha_form_markup()
{
	if (! pkwk_captcha_is_enabled()) {
		return '';
	}

	$cfg = pkwk_captcha_config();
	switch ($cfg['provider']) {
	case 'honeypot':
		return '  <div class="pkwk-captcha-honeypot" aria-hidden="true" style="position:absolute;left:-9999px;">' . "\n" .
			'   <label for="pkwk_hp_url">URL</label>' . "\n" .
			'   <input type="text" name="pkwk_hp_url" id="pkwk_hp_url" value="" tabindex="-1" autocomplete="off" />' . "\n" .
			'  </div>' . "\n";

	case 'recaptcha_v3':
		$site_key = htmlsc($cfg['site_key']);
		return '  <input type="hidden" name="g-recaptcha-response" id="pkwk-recaptcha-v3-token" value="" />' . "\n" .
			'  <script src="https://www.google.com/recaptcha/api.js?render=' . $site_key . '"></script>' . "\n" .
			'  <script>' . "\n" .
			'  (function(){' . "\n" .
			'   var form=document.querySelector("form._plugin_edit_edit_form");' . "\n" .
			'   if(!form||typeof grecaptcha==="undefined")return;' . "\n" .
			'   form.addEventListener("submit",function(e){' . "\n" .
			'    var btn=e.submitter||document.activeElement;' . "\n" .
			'    if(!btn||btn.name!=="write")return;' . "\n" .
			'    e.preventDefault();' . "\n" .
			'    grecaptcha.ready(function(){' . "\n" .
			'     grecaptcha.execute("' . $site_key . '",{action:"edit"}).then(function(token){' . "\n" .
			'      document.getElementById("pkwk-recaptcha-v3-token").value=token;' . "\n" .
			'      form.submit();' . "\n" .
			'     });' . "\n" .
			'    });' . "\n" .
			'   });' . "\n" .
			'  })();' . "\n" .
			'  </script>' . "\n";

	case 'recaptcha_v2':
	default:
		$site_key = htmlsc($cfg['site_key']);
		return '  <div class="g-recaptcha" data-sitekey="' . $site_key . '"></div>' . "\n" .
			'  <script src="https://www.google.com/recaptcha/api.js" async defer></script>' . "\n";
	}
}

/**
 * Verify CAPTCHA on edit write POST (cmd=edit & write=).
 * No-op when disabled or content is empty (page delete).
 *
 * @param string $content Wiki text without author footer
 */
function pkwk_captcha_verify_edit_or_die($content)
{
	if (! pkwk_captcha_is_enabled()) {
		return;
	}
	if (trim($content) === '') {
		return;
	}

	$cfg = pkwk_captcha_config();
	switch ($cfg['provider']) {
	case 'honeypot':
		$hp = isset($_POST['pkwk_hp_url']) ? trim((string)$_POST['pkwk_hp_url']) : '';
		if ($hp !== '') {
			die_message('CAPTCHA の検証に失敗しました。保存できません。');
		}
		return;

	case 'recaptcha_v3':
		$response = isset($_POST['g-recaptcha-response']) ? trim((string)$_POST['g-recaptcha-response']) : '';
		if ($response === '') {
			die_message('CAPTCHA の検証に失敗しました。ページを再読み込みしてやり直してください。');
		}
		$result = pkwk_captcha_recaptcha_verify($response, $cfg['secret_key']);
		if (! $result['success']) {
			die_message('CAPTCHA の検証に失敗しました。ページを再読み込みしてやり直してください。');
		}
		if (isset($result['score']) && (float)$result['score'] < PKWK_CAPTCHA_RECAPTCHA_V3_THRESHOLD) {
			die_message('CAPTCHA のスコアが低いため、保存できません。しばらくしてから再試行してください。');
		}
		if (isset($result['action']) && $result['action'] !== '' && $result['action'] !== 'edit') {
			die_message('CAPTCHA の検証に失敗しました。ページを再読み込みしてやり直してください。');
		}
		return;

	case 'recaptcha_v2':
	default:
		$response = isset($_POST['g-recaptcha-response']) ? trim((string)$_POST['g-recaptcha-response']) : '';
		if ($response === '') {
			die_message('CAPTCHA にチェックを入れてから保存してください。');
		}
		$result = pkwk_captcha_recaptcha_verify($response, $cfg['secret_key']);
		if (! $result['success']) {
			die_message('CAPTCHA の検証に失敗しました。ページを再読み込みしてやり直してください。');
		}
		return;
	}
}

/**
 * @return array{success:bool,score:float|null,action:string}
 */
function pkwk_captcha_recaptcha_verify($response, $secret_key)
{
	$post = array(
		'secret'   => $secret_key,
		'response' => $response,
	);
	if (isset($_SERVER['REMOTE_ADDR'])) {
		$post['remoteip'] = $_SERVER['REMOTE_ADDR'];
	}

	$headers = "Content-Type: application/x-www-form-urlencoded\r\n";
	$raw = pkwk_http_request(PKWK_CAPTCHA_RECAPTCHA_VERIFY_URL, 'POST', $headers, $post);
	if (! is_array($raw) || (int)$raw['rc'] !== 200) {
		return array('success' => FALSE, 'score' => null, 'action' => '');
	}

	$data = json_decode($raw['data'], TRUE);
	if (! is_array($data)) {
		return array('success' => FALSE, 'score' => null, 'action' => '');
	}

	$score = isset($data['score']) ? (float)$data['score'] : null;
	$action = isset($data['action']) ? (string)$data['action'] : '';
	return array(
		'success' => ! empty($data['success']),
		'score'   => $score,
		'action'  => $action,
	);
}
