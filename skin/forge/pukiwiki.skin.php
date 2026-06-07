<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// pukiwiki.skin.php
// Copyright
//   2002-2021 PukiWiki Development Team
//   2001-2002 Originally written by yu-ji
// License: GPL v2 or (at your option) any later version
//
// forge skin — React-based modern UI (PukiWiki2026)

// ------------------------------------------------------------
// Settings

$_IMAGE['skin']['logo']     = 'pukiwiki.png';
$_IMAGE['skin']['favicon']  = '';

if (! defined('SKIN_DEFAULT_DISABLE_TOPICPATH'))
	define('SKIN_DEFAULT_DISABLE_TOPICPATH', 1);

if (! defined('PKWK_SKIN_SHOW_NAVBAR'))
	define('PKWK_SKIN_SHOW_NAVBAR', 1);

if (! defined('PKWK_SKIN_SHOW_TOOLBAR'))
	define('PKWK_SKIN_SHOW_TOOLBAR', 1);

// ------------------------------------------------------------
// Code start

if (! defined('UI_LANG')) die('UI_LANG is not set');
if (! isset($_LANG)) die('$_LANG is not set');
if (! defined('PKWK_READONLY')) die('PKWK_READONLY is not set');

$lang  = & $_LANG['skin'];
$link  = & $_LINK;
$image = & $_IMAGE['skin'];
$rw    = ! PKWK_READONLY;

$menu = arg_check('read') && exist_plugin_convert('menu') ? do_plugin_convert('menu') : FALSE;
$rightbar = FALSE;
if (arg_check('read') && exist_plugin_convert('rightbar')) {
	$rightbar = do_plugin_convert('rightbar');
}

// ------------------------------------------------------------
// Build forge initial data for React

function _forge_nav_item($key, $label = '', $href = '') {
	global $_LANG, $_LINK;
	if ($href === '') {
		if (! isset($_LINK[$key])) return null;
		$href = $_LINK[$key];
	}
	if ($label === '') {
		if (! isset($_LANG['skin'][$key])) return null;
		$label = $_LANG['skin'][$key];
	}
	return array('key' => $key, 'label' => $label, 'href' => $href);
}

function _forge_toolbar_item($key, $w = 20, $h = 20) {
	global $_LANG, $_LINK, $_IMAGE;
	if (! isset($_LINK[$key]) || ! isset($_LANG['skin'][$key])) return null;
	$icon = isset($_IMAGE['skin'][$key]) ? IMAGE_DIR . $_IMAGE['skin'][$key] : '';
	return array(
		'key'   => $key,
		'label' => $_LANG['skin'][$key],
		'href'  => $_LINK[$key],
		'icon'  => $icon,
		'width' => $w,
		'height'=> $h,
	);
}

$forge_nav_primary = array();
if (PKWK_SKIN_SHOW_NAVBAR) {
	$item = _forge_nav_item('top');
	if ($item) $forge_nav_primary[] = $item;
}

$forge_nav_page = array();
if (PKWK_SKIN_SHOW_NAVBAR && $is_page) {
	if ($rw) {
		$item = _forge_nav_item('edit');
		if ($item) $forge_nav_page[] = $item;
		if ($is_read && $function_freeze) {
			$item = _forge_nav_item($is_freeze ? 'unfreeze' : 'freeze');
			if ($item) $forge_nav_page[] = $item;
		}
	}
	$item = _forge_nav_item('diff');
	if ($item) $forge_nav_page[] = $item;
	if ($do_backup) {
		$item = _forge_nav_item('backup');
		if ($item) $forge_nav_page[] = $item;
	}
	if ($rw && (bool)ini_get('file_uploads')) {
		$item = _forge_nav_item('upload');
		if ($item) $forge_nav_page[] = $item;
	}
	$item = _forge_nav_item('reload');
	if ($item) $forge_nav_page[] = $item;
}

$forge_nav_global = array();
if (PKWK_SKIN_SHOW_NAVBAR) {
	if ($rw) {
		$item = _forge_nav_item('new');
		if ($item) $forge_nav_global[] = $item;
	}
	$item = _forge_nav_item('list');
	if ($item) $forge_nav_global[] = $item;
	if (arg_check('list')) {
		$item = _forge_nav_item('filelist');
		if ($item) $forge_nav_global[] = $item;
	}
	foreach (array('search', 'recent', 'help') as $_nav_key) {
		$item = _forge_nav_item($_nav_key);
		if ($item) $forge_nav_global[] = $item;
	}
	if ($enable_login) {
		$item = _forge_nav_item('login');
		if ($item) $forge_nav_global[] = $item;
	}
	if ($enable_logout) {
		$item = _forge_nav_item('logout');
		if ($item) $forge_nav_global[] = $item;
	}
}

$forge_toolbar = array();
if (PKWK_SKIN_SHOW_TOOLBAR) {
	$_IMAGE['skin']['reload']   = 'reload.png';
	$_IMAGE['skin']['new']      = 'new.png';
	$_IMAGE['skin']['edit']     = 'edit.png';
	$_IMAGE['skin']['freeze']   = 'freeze.png';
	$_IMAGE['skin']['unfreeze'] = 'unfreeze.png';
	$_IMAGE['skin']['diff']     = 'diff.png';
	$_IMAGE['skin']['upload']   = 'file.png';
	$_IMAGE['skin']['copy']     = 'copy.png';
	$_IMAGE['skin']['rename']   = 'rename.png';
	$_IMAGE['skin']['top']      = 'top.png';
	$_IMAGE['skin']['list']     = 'list.png';
	$_IMAGE['skin']['search']   = 'search.png';
	$_IMAGE['skin']['recent']   = 'recentchanges.png';
	$_IMAGE['skin']['backup']   = 'backup.png';
	$_IMAGE['skin']['help']     = 'help.png';
	$_IMAGE['skin']['rss']      = 'rss.png';
	$_IMAGE['skin']['rss10']    = & $_IMAGE['skin']['rss'];
	$_IMAGE['skin']['rss20']    = 'rss20.png';

	$item = _forge_toolbar_item('top');
	if ($item) $forge_toolbar[] = $item;
	if ($is_page) {
		if ($rw) {
			$item = _forge_toolbar_item('edit');
			if ($item) $forge_toolbar[] = $item;
			if ($is_read && $function_freeze) {
				$item = _forge_toolbar_item($is_freeze ? 'unfreeze' : 'freeze');
				if ($item) $forge_toolbar[] = $item;
			}
		}
		$item = _forge_toolbar_item('diff');
		if ($item) $forge_toolbar[] = $item;
		if ($do_backup) {
			$item = _forge_toolbar_item('backup');
			if ($item) $forge_toolbar[] = $item;
		}
		if ($rw) {
			if ((bool)ini_get('file_uploads')) {
				$item = _forge_toolbar_item('upload');
				if ($item) $forge_toolbar[] = $item;
			}
			foreach (array('copy', 'rename') as $_tb_key) {
				$item = _forge_toolbar_item($_tb_key);
				if ($item) $forge_toolbar[] = $item;
			}
		}
		$item = _forge_toolbar_item('reload');
		if ($item) $forge_toolbar[] = $item;
	}
	if ($rw) {
		$item = _forge_toolbar_item('new');
		if ($item) $forge_toolbar[] = $item;
	}
	foreach (array('list', 'search', 'recent', 'help') as $_tb_key) {
		$item = _forge_toolbar_item($_tb_key);
		if ($item) $forge_toolbar[] = $item;
	}
	$item = _forge_toolbar_item('rss10', 36, 14);
	if ($item) $forge_toolbar[] = $item;
}

$topicpath = '';
if ($is_page && ! SKIN_DEFAULT_DISABLE_TOPICPATH) {
	require_once(PLUGIN_DIR . 'topicpath.inc.php');
	$topicpath = plugin_topicpath_inline();
}

$forge_initial = array(
	'title'          => $title,
	'page'           => $page,
	'pageTitle'      => $page_title,
	'body'           => $body,
	'menu'           => $menu ? $menu : '',
	'rightbar'       => $rightbar ? $rightbar : '',
	'notes'          => $notes,
	'attaches'       => $attaches,
	'lastmodified'   => $lastmodified,
	'related'        => $related,
	'canonicalUrl'   => $link['canonical_url'],
	'topicpath'      => $topicpath,
	'showTopicpath'  => ! SKIN_DEFAULT_DISABLE_TOPICPATH,
	'showNavbar'     => (bool)PKWK_SKIN_SHOW_NAVBAR,
	'showToolbar'    => (bool)PKWK_SKIN_SHOW_TOOLBAR,
	'isPage'         => (bool)$is_page,
	'isRead'         => (bool)$is_read,
	'readonly'       => ! $rw,
	'isFreeze'       => (bool)$is_freeze,
	'functionFreeze' => (bool)$function_freeze,
	'doBackup'       => (bool)$do_backup,
	'fileUploads'    => (bool)ini_get('file_uploads'),
	'logo'           => IMAGE_DIR . $image['logo'],
	'logoAlt'        => '[PukiWiki]',
	'topHref'        => $link['top'],
	'rssHref'        => $link['rss'],
	'navPrimary'     => $forge_nav_primary,
	'navPage'        => $forge_nav_page,
	'navGlobal'      => $forge_nav_global,
	'toolbar'        => $forge_toolbar,
	'footer'         => array(
		'modifier'     => $modifier,
		'modifierLink' => $modifierlink,
		'copyright'    => S_COPYRIGHT,
		'phpVersion'   => PHP_VERSION,
		'convertTime'  => elapsedtime(),
	),
);

$forge_json = json_encode($forge_initial, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
if ($forge_json === false) {
	$forge_json = '{}';
}

// Resolve built assets (hashed filenames from Vite manifest)
$forge_manifest_path = dirname(__FILE__) . '/dist/.vite/manifest.json';
$forge_css = SKIN_ASSETS_DIR . 'dist/assets/index.css';
$forge_js  = SKIN_ASSETS_DIR . 'dist/assets/index.js';
if (file_exists($forge_manifest_path)) {
	$_forge_manifest = json_decode(file_get_contents($forge_manifest_path), true);
	if (is_array($_forge_manifest) && isset($_forge_manifest['index.html'])) {
		$_forge_entry = $_forge_manifest['index.html'];
		if (isset($_forge_entry['file'])) {
			$forge_js = SKIN_ASSETS_DIR . 'dist/' . $_forge_entry['file'];
		}
		if (isset($_forge_entry['css'][0])) {
			$forge_css = SKIN_ASSETS_DIR . 'dist/' . $_forge_entry['css'][0];
		}
	}
}

// ------------------------------------------------------------
// Output

pkwk_common_headers();
header('Cache-control: no-cache');
header('Pragma: no-cache');
header('Content-Type: text/html; charset=' . CONTENT_CHARSET);

?>
<!DOCTYPE html>
<html lang="<?php echo LANG ?>">
<head>
 <meta charset="<?php echo CONTENT_CHARSET ?>" />
 <meta name="viewport" content="width=device-width, initial-scale=1.0" />
<?php if ($nofollow || ! $is_read)  { ?> <meta name="robots" content="NOINDEX,NOFOLLOW" /><?php } ?>
<?php if ($html_meta_referrer_policy) { ?> <meta name="referrer" content="<?php echo htmlsc(html_meta_referrer_policy) ?>" /><?php } ?>

 <title><?php echo $title ?> - <?php echo $page_title ?></title>

<?php if ($image['favicon'] !== '') { ?>
 <link rel="icon" href="<?php echo htmlsc($image['favicon']) ?>" />
<?php } ?>
 <link rel="stylesheet" href="<?php echo $forge_css ?>" />
 <link rel="alternate" type="application/rss+xml" title="RSS" href="<?php echo $link['rss'] ?>" />
 <script type="text/javascript" src="<?php echo SKIN_ASSETS_DIR ?>main.js" defer></script>

<?php echo $head_tag ?>
</head>
<body>
<?php echo $html_scripting_data ?>
<div id="pukiwiki-forge-root"></div>
<script type="application/json" id="pukiwiki-forge-initial"><?php echo $forge_json ?></script>
<script type="module" src="<?php echo $forge_js ?>"></script>
</body>
</html>
