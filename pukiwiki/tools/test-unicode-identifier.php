#!/usr/bin/env php
<?php
/**
 * SEC-U01 — Unicode identifier validation smoke test.
 *
 * Usage: php pukiwiki/tools/test-unicode-identifier.php
 */
define('DATA_HOME', dirname(__DIR__) . '/');
define('LIB_DIR', DATA_HOME . 'lib/');
define('PKWK_UTF8_ENABLE', 1);
define('SOURCE_ENCODING', 'UTF-8');

require LIB_DIR . 'security.php';

$InterWikiName = '(\[\[)?((?:(?!\s|:|\]\]).)+):(.+)(?(1)\]\])';
$BracketName = '(?!\s):?[^\r\n\t\f\[\]<>#&":]+:?(?<!\s)';
require LIB_DIR . 'func.php';

$failures = 0;
$tests = array(
	array('FrontPage', true, 'plain ASCII page name'),
	array("FrontPag\u{202E}gnipaeP", false, 'RTL override (U+202E) spoof'),
	array("Front\u{200B}Page", false, 'zero-width space (U+200B)'),
	array("Front\u{FEFF}Page", false, 'BOM (U+FEFF)'),
	array("test\u{2066}name", false, 'LRI (U+2066)'),
	array('Sandbox', true, 'normal wiki page'),
);

foreach ($tests as $t) {
	list($input, $expect_safe, $label) = $t;
	$safe = pkwk_is_safe_identifier($input);
	$pagename = is_pagename($input);
	$ok = ($safe === $expect_safe) && ($pagename === $expect_safe);
	if (! $ok) {
		fwrite(STDERR, "FAIL: $label\n");
		fwrite(STDERR, "  input=" . json_encode($input, JSON_UNESCAPED_UNICODE) . "\n");
		fwrite(STDERR, "  expected safe=" . ($expect_safe ? 'true' : 'false') .
			" got safe=" . ($safe ? 'true' : 'false') .
			" is_pagename=" . ($pagename ? 'true' : 'false') . "\n");
		++$failures;
	} else {
		echo "OK: $label\n";
	}
}

if ($failures > 0) {
	fwrite(STDERR, "$failures test(s) failed.\n");
	exit(1);
}

echo "All " . count($tests) . " tests passed.\n";
exit(0);
