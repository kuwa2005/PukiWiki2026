<?php
// PukiWiki2026 — startup directory permission check
// Copyright 2026 PukiWiki2026 contributors
// License: GPL v2 or (at your option) any later version
//
// Unix-like 環境向け。各書き込みディレクトリ自身の mode のみを起動時に確認し、
// 不適切な場合のみ chmod と配下の再帰的修正を行う。

/**
 * Unix パーミッション操作が意味を持つ環境か
 *
 * @return bool
 */
function pkwk_perm_is_supported()
{
	return (DIRECTORY_SEPARATOR === '/' && PHP_OS_FAMILY !== 'Windows');
}

/**
 * 起動時チェック対象のディレクトリ定数名一覧
 *
 * @return array<int, string>
 */
function pkwk_perm_default_dir_constants()
{
	return array(
		'DATA_DIR',
		'DIFF_DIR',
		'BACKUP_DIR',
		'CACHE_DIR',
		'UPLOAD_DIR',
		'COUNTER_DIR',
	);
}

/**
 * パスから Unix mode (0777) を取得
 *
 * @param string $path
 * @return int|false
 */
function pkwk_perm_get_mode($path)
{
	clearstatcache(true, $path);
	$perms = @fileperms($path);
	if ($perms === FALSE) return FALSE;
	return $perms & 0777;
}

/**
 * ディレクトリ mode が許容範囲か
 *
 * @param int $mode
 * @param array<int, int> $acceptable_modes
 * @return bool
 */
function pkwk_perm_is_dir_mode_acceptable($mode, $acceptable_modes)
{
	return in_array($mode, $acceptable_modes, TRUE);
}

/**
 * ディレクトリを作成（存在しなければ）
 *
 * @param string $path
 * @param int $dir_mode
 * @return bool
 */
function pkwk_perm_ensure_directory($path, $dir_mode)
{
	if (is_dir($path)) return TRUE;
	return @mkdir($path, $dir_mode, TRUE);
}

/**
 * ディレクトリ配下を再帰的に chmod（ディレクトリ自身は含まない）
 *
 * @param string $dir 末尾スラッシュ付きパス
 * @param int $dir_mode
 * @param int $file_mode
 * @return void
 */
function pkwk_perm_fix_tree($dir, $dir_mode, $file_mode)
{
	$items = @scandir($dir);
	if ($items === FALSE) return;

	foreach ($items as $item) {
		if ($item === '.' || $item === '..') continue;

		$path = $dir . $item;
		if (! file_exists($path)) continue;

		if (is_dir($path)) {
			@chmod($path, $dir_mode);
			pkwk_perm_fix_tree($path . '/', $dir_mode, $file_mode);
		} else {
			@chmod($path, $file_mode);
		}
	}
}

/**
 * 起動時パーミッションチェックを実行
 *
 * @return array{skipped: bool, fixed: array<int, string>, errors: array<int, string>}
 */
function pkwk_perm_check_on_boot()
{
	global $perm_check_on_boot, $perm_dir_mode, $perm_file_mode,
		$perm_acceptable_dir_modes, $perm_check_dirs_extra;

	$result = array(
		'skipped' => FALSE,
		'fixed'   => array(),
		'errors'  => array(),
	);

	if (! pkwk_perm_is_supported()) {
		$result['skipped'] = TRUE;
		return $result;
	}

	if (isset($perm_check_on_boot) && ! $perm_check_on_boot) {
		$result['skipped'] = TRUE;
		return $result;
	}

	$dir_mode = isset($perm_dir_mode) ? (int)$perm_dir_mode : 0777;
	$file_mode = isset($perm_file_mode) ? (int)$perm_file_mode : 0666;
	$acceptable_modes = isset($perm_acceptable_dir_modes)
		? $perm_acceptable_dir_modes
		: array(0777, 0775, 0770);

	$dir_constants = pkwk_perm_default_dir_constants();
	if (! empty($perm_check_dirs_extra) && is_array($perm_check_dirs_extra)) {
		$dir_constants = array_merge($dir_constants, $perm_check_dirs_extra);
	}
	$dir_constants = array_unique($dir_constants);

	foreach ($dir_constants as $dir_const) {
		if (! defined($dir_const)) continue;

		$path = constant($dir_const);
		if ($path === '' || $path === FALSE) continue;

		if (! is_dir($path)) {
			if (! pkwk_perm_ensure_directory($path, $dir_mode)) {
				$result['errors'][] =
					'Directory is not found and cannot be created (' . $dir_const . ')';
			}
			continue;
		}

		$mode = pkwk_perm_get_mode($path);
		if ($mode === FALSE) {
			$result['errors'][] =
				'Cannot read permission of directory (' . $dir_const . ')';
			continue;
		}

		if (pkwk_perm_is_dir_mode_acceptable($mode, $acceptable_modes)) {
			continue;
		}

		if (! @chmod($path, $dir_mode)) {
			$result['errors'][] =
				'Cannot fix permission of directory (' . $dir_const . ')';
			continue;
		}

		pkwk_perm_fix_tree($path, $dir_mode, $file_mode);
		$result['fixed'][] = $dir_const;
	}

	return $result;
}

/**
 * Prepare pukiwiki.ini.php and its parent directory for a single write.
 *
 * Chmods only when is_writable() is FALSE (mode 0644 でも実行ユーザーが書けなければ
 * 0666 へ上げる). Records original modes so pkwk_perm_ini_write_restore() can revert
 * only what this call changed.
 *
 * @param string $ini_path Path to pukiwiki.ini.php (realpath optional)
 * @return array{
 *   ini_path: string,
 *   dir_path: string,
 *   did_chmod_ini: bool,
 *   ini_mode_before: int|null,
 *   did_chmod_dir: bool,
 *   dir_mode_before: int|null
 * }
 */
function pkwk_perm_ini_write_prepare($ini_path)
{
	$ctx = array(
		'ini_path'        => '',
		'dir_path'        => '',
		'did_chmod_ini'   => FALSE,
		'ini_mode_before' => NULL,
		'did_chmod_dir'   => FALSE,
		'dir_mode_before' => NULL,
	);

	if (! pkwk_perm_is_supported() || $ini_path === '') {
		return $ctx;
	}

	global $perm_dir_mode, $perm_file_mode;
	$dir_mode = isset($perm_dir_mode) ? (int)$perm_dir_mode : 0777;
	$file_mode = isset($perm_file_mode) ? (int)$perm_file_mode : 0666;

	$resolved = realpath($ini_path);
	$path = ($resolved !== FALSE) ? $resolved : $ini_path;
	$dir = dirname($path);
	$ctx['ini_path'] = $path;
	$ctx['dir_path'] = $dir;

	if (is_file($path) && ! is_writable($path)) {
		$mode_before = pkwk_perm_get_mode($path);
		if ($mode_before !== FALSE && @chmod($path, $file_mode)) {
			$ctx['did_chmod_ini'] = TRUE;
			$ctx['ini_mode_before'] = $mode_before;
		}
	} elseif ($path !== $ini_path && is_file($ini_path) && ! is_writable($ini_path)) {
		$mode_before = pkwk_perm_get_mode($ini_path);
		if ($mode_before !== FALSE && @chmod($ini_path, $file_mode)) {
			$ctx['did_chmod_ini'] = TRUE;
			$ctx['ini_mode_before'] = $mode_before;
			$ctx['ini_path'] = $ini_path;
		}
	}

	if (is_dir($dir) && ! is_writable($dir)) {
		$mode_before = pkwk_perm_get_mode($dir);
		if ($mode_before !== FALSE && @chmod($dir, $dir_mode)) {
			$ctx['did_chmod_dir'] = TRUE;
			$ctx['dir_mode_before'] = $mode_before;
		}
	}

	return $ctx;
}

/**
 * Restore modes chmod'd by pkwk_perm_ini_write_prepare() in the same request.
 *
 * @param array $ctx Return value of pkwk_perm_ini_write_prepare()
 * @return void
 */
function pkwk_perm_ini_write_restore(array $ctx)
{
	if (! pkwk_perm_is_supported()) {
		return;
	}

	if (! empty($ctx['did_chmod_ini'])
		&& $ctx['ini_mode_before'] !== NULL
		&& $ctx['ini_path'] !== ''
		&& is_file($ctx['ini_path'])) {
		@chmod($ctx['ini_path'], $ctx['ini_mode_before']);
	}
	if (! empty($ctx['did_chmod_dir'])
		&& $ctx['dir_mode_before'] !== NULL
		&& $ctx['dir_path'] !== ''
		&& is_dir($ctx['dir_path'])) {
		@chmod($ctx['dir_path'], $ctx['dir_mode_before']);
	}
}

/**
 * Administrator hint for ini write permission failures (Unix-like only).
 *
 * @param string $ini_path
 * @return string
 */
function pkwk_perm_ini_write_debug_hint($ini_path)
{
	if (! pkwk_perm_is_supported() || $ini_path === '') {
		return '';
	}

	$real = realpath($ini_path);
	if ($real === FALSE || ! is_file($real)) {
		return '';
	}

	$dir = dirname($real);
	$parts = array();

	$ini_mode = pkwk_perm_get_mode($real);
	if ($ini_mode !== FALSE) {
		$parts[] = 'ini mode=' . sprintf('%04o', $ini_mode);
	}
	$parts[] = 'ini is_writable=' . (is_writable($real) ? 'yes' : 'no');

	if (is_dir($dir)) {
		$dir_mode = pkwk_perm_get_mode($dir);
		if ($dir_mode !== FALSE) {
			$parts[] = 'dir mode=' . sprintf('%04o', $dir_mode);
		}
		$parts[] = 'dir is_writable=' . (is_writable($dir) ? 'yes' : 'no');
	}

	if (function_exists('posix_geteuid') && function_exists('fileowner')) {
		$owner = @fileowner($real);
		if ($owner !== FALSE) {
			$parts[] = 'ini owner uid=' . $owner;
			$parts[] = 'process euid=' . posix_geteuid();
		}
	}

	return implode(', ', $parts);
}
