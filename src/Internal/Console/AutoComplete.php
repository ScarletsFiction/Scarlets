<?php
namespace Scarlets\Internal\Console;
use \Scarlets;
use \Scarlets\Console;
use \Scarlets\Config;

class AutoShell{
	// Find candidate from $nested
	public static function readline(&$currentText, &$index){ // $currentText-> after space this will be empty
		$info = readline_info('line_buffer'); // line_buffer-> all text, point-> total length
		$matches = ['aye', 'eya'];

		self::saveCursor();
		$desc = print_r([$currentText, $index, $info], true);
		echo self::style('<black lighter>'.str_replace("\n", "\n\033[K\r", $desc).'</black>');
		self::loadCursor();

		return $matches;
	}
}