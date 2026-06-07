<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// freeze.inc.php
// Copyright 2003-2017 PukiWiki Development Team
// License: GPL v2 or (at your option) any later version
//
// Freeze(Lock) plugin

// Reserve 'Do nothing'. '^#freeze' is for internal use only.
function plugin_freeze_convert() { return ''; }

function plugin_freeze_action()
{
	global $vars, $function_freeze;
	global $_title_isfreezed, $_title_freezed, $_title_freeze;
	global $_msg_invalidpass, $_msg_freezing, $_msg_freezing_confirm, $_btn_freeze;

	$script = get_base_uri();
	$page = isset($vars['page']) ? $vars['page'] : '';
	if (! $function_freeze || ! is_page($page))
		return array('msg' => '', 'body' => '');

	$pass = isset($vars['pass']) ? $vars['pass'] : NULL;
	$msg = $body = '';
	if (is_freeze($page)) {
		// Freezed already
		$msg  = & $_title_isfreezed;
		$body = str_replace('$1', htmlsc(strip_bracket($page)),
			$_title_isfreezed);

	} else if (isset($vars['ok']) && pkwk_admin_authorized($pass)) {
		// Freeze
		$postdata = get_source($page);
		array_unshift($postdata, "#freeze\n");
		file_write(DATA_DIR, $page, join('', $postdata), TRUE);

		// Update
		is_freeze($page, TRUE);
		$vars['cmd'] = 'read';
		$msg  = & $_title_freezed;
		$body = '';

	} else {
		// Show a freeze form
		$msg    = & $_title_freeze;
		$s_page = htmlsc($page);
		$body   = (isset($vars['ok']) && ! pkwk_admin_authorized($pass)) ?
			"<p><strong>$_msg_invalidpass</strong></p>\n" : '';
		$pass_input = pkwk_is_authenticated() ? '' :
			'<input type="password" name="pass" size="12" />';
		$action_msg = pkwk_is_authenticated()
			? "<p>$_msg_freezing_confirm</p>\n"
			: "<p>$_msg_freezing</p>\n";
		$body  .= <<<EOD
$action_msg
<form action="$script" method="post">
 <div>
  <input type="hidden"   name="cmd"  value="freeze" />
  <input type="hidden"   name="page" value="$s_page" />
  $pass_input
  <input type="submit"   name="ok"   value="$_btn_freeze" />
 </div>
</form>
EOD;
	}

	return array('msg'=>$msg, 'body'=>$body);
}
