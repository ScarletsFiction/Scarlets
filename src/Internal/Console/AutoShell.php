<?php
namespace Scarlets\Internal\Console;
use \Scarlets;
use \Scarlets\Console;
use \Scarlets\Config;
use \Scarlets\Internal\Console\AutoComplete;

AutoShell::$leftText = Config::get('app', 'console_user')."> ";
AutoShell::$minIndex = strlen(AutoShell::$leftText);

class AutoShell{
	private static $writtenToShell = '';
	private static $writtenLength = [];
	private static $cursorIndex = 0;
	private static $maxIndex = 0;
	private static $lastCharCode = '';
	private static $lastSpecialOrd = '';
	private static $debug = '';
	private static $ban = 0;

	public static $leftText; // "You> "
	public static $minIndex; // 4

	public static function redraw(){
		echo "\033[s\e[?25l\033[K\r";
		echo self::$leftText.self::$writtenToShell;
		echo "\e[?25h\033[u";
	}

	public static function write(&$char){
		$current = &self::$writtenToShell;

		$ord = ord($char);
		$charCode = mb_ord($char, "utf8");
		self::$lastCharCode = $charCode;

		if(self::$ban === $ord){
			self::$ban = 0;
			return;
		}

		if($ord === 224){ // special
			if(isset($char[1])){
				self::$lastSpecialOrd = ord($char[1]);
				self::moveCursor(self::$lastSpecialOrd);

				// Fix bug by ban repeating character
				self::$ban = self::$lastSpecialOrd;
			}
		}
		elseif($ord === 32 || $charCode === 12288){ // space
			$current = substr_replace($current, ' ', self::countCharCode(0, self::$cursorIndex), 0);
			echo "\033[1C";
			self::redraw();

			array_splice(self::$writtenLength, self::$cursorIndex, 0, 1);
			self::$cursorIndex++;
			self::$maxIndex++;
		}
		elseif($ord === 8){ // backspace
			if(self::$cursorIndex === 0)
				return;

			self::$cursorIndex--;
			self::$maxIndex--;

			$len = array_splice(self::$writtenLength, self::$cursorIndex, 1)[0];
			$current = substr_replace($current, '', self::countCharCode(0, self::$cursorIndex), $len);

			echo "\033[{$len}D";
			self::redraw();
		}
		elseif($ord === 13){ // enter
			echo "\n$current";
			self::$cursorIndex = self::$maxIndex = 0;
		}
		else{
			$current = substr_replace($current, $char, self::countCharCode(0, self::$cursorIndex), 0);
			self::redraw();

			$len = strlen($char);

			array_splice(self::$writtenLength, self::$cursorIndex, 0, $len);
			self::$cursorIndex++;
			self::$maxIndex++;

			echo "\033[{$len}C";
		}

		self::debug();
	}

	private static function countCharCode($start, $end){
		return array_sum(array_slice(self::$writtenLength, $start, $end));
	}

	private static function moveCursor($ord2){
		if($ord2 === 75 || $ord2 === 115)
			if(self::$cursorIndex === 0)
				return; // Reached first cursor index
		elseif($ord2 === 77 || $ord2 === 116)
			if(self::$cursorIndex === self::$maxIndex)
				return; // Reached last cursor index

		if($ord2 === 75){ // left
			if(self::$cursorIndex === 0)
				return;

			echo "\033[".self::$writtenLength[self::$cursorIndex-1]."D";
			self::$cursorIndex--;
		}
		elseif($ord2 === 77){ // right
			if(self::$maxIndex === self::$cursorIndex)
				return;

			echo "\033[".self::$writtenLength[self::$cursorIndex]."C";
			self::$cursorIndex++;
		}
		elseif($ord2 === 115){ // ctrl+left
			if(self::$cursorIndex === 0) return;

			$space = mb_strrpos(self::$writtenToShell, ' ', -(self::$maxIndex-self::$cursorIndex)-1);
			self::$debug = [$space, -self::$cursorIndex-1];
			if($space === false)
				$pos = self::$cursorIndex;
			else
				$pos = self::$cursorIndex - $space;

			if($pos === 0) return;

			self::$cursorIndex -= $pos;
			echo "\033[".$pos."D";
		}
		elseif($ord2 === 116){ // ctrl+right
			if(self::$cursorIndex === self::$maxIndex) return;

			$space = mb_strpos(self::$writtenToShell, ' ', self::$cursorIndex+1);
			if($space === false)
				$pos = self::$maxIndex - self::$cursorIndex;
			else
				$pos = $space - self::$cursorIndex;

			if($pos === 0) return;

			self::$cursorIndex += $pos;
			echo "\033[".$pos."C";
		}
		//echo "\033[1A"; // up
		//echo "\033[1B"; // down
	}

	private static function debug($text = null){
		if($text !== null)
			return Console::writeShadow("\n".json_encode($text));

		Console::writeShadow("
writtenToShell: ".self::$writtenToShell."
writtenLength: ".implode(' ', self::$writtenLength)."
cursorIndex: ".self::$cursorIndex."
maxIndex: ".self::$maxIndex."
minIndex: ".self::$minIndex."
lastCharCode: ".self::$lastCharCode."
lastSpecialOrd: ".self::$lastSpecialOrd."
debug: ".json_encode(self::$debug)."
");
	}
}