<?php
namespace Scarlets\Library;
use \Scarlets;
use \Scarlets\Library\FileSystem\LocalFile;

class Schedule{
	private static $time = [];
	private static $modified = [];

	public static function init(){
		self::$time = LocalFile::load('{framework}/schedule.cache');
		if(self::$time === '') self::$time = [];
		else self::$time = json_decode(self::$time, true);

		\Scarlets::onShutdown(function(){
			if(empty(self::$modified) === false){
				$time = array_merge(self::$time, self::$modified);
				LocalFile::put('{framework}/schedule.cache', json_encode(self::$time));
			}
		});
	}

	public static function everyMinutes($min, $callback = false){
		if($min instanceof \Closure){
			$callback = $min;
			$min = 1;
		}

		if(isset(self::$time["m.$min"]) && self::$time["m.$min"] >= time())
			return;

		try{
			$callback();
		}catch(\Exception $e){\Scarlets\Error::checkUncaughtError();}

		if(isset(self::$modified["m.$min"]) === false)
			self::$modified["m.$min"] = mktime(date("H"), date("i")+$min, 0, date("n"), date("j"), date("Y"));
	}

	public static function hourly($hour, $callback = false){
		if($hour instanceof \Closure){
			$callback = $hour;
			$hour = 1;
		}

		if(isset(self::$time["h.$hour"]) && self::$time["h.$hour"] >= time())
			return;

		try{
			$callback();
		}catch(\Exception $e){\Scarlets\Error::checkUncaughtError();}

		if(isset(self::$modified["h.$hour"]) === false)
			self::$modified["h.$hour"] = mktime(date("H")+$hour, 0, 0, date("n"), date("j"), date("Y"));
	}

	public static function daily($day, $callback = false){
		if($day instanceof \Closure){
			$callback = $day;
			$day = 1;
		}

		if(isset(self::$time["d.$day"]) && self::$time["d.$day"] >= time())
			return;

		try{
			$callback();
		}catch(\Exception $e){\Scarlets\Error::checkUncaughtError();}

		if(isset(self::$modified["d.$day"]) === false)
			self::$modified["d.$day"] = mktime(date("H"), date("i"), 0, date("n"), date("j")+$day, date("Y"));
	}

	public static function weekly($week, $callback = false){
		if($week instanceof \Closure){
			$callback = $week;
			$week = 7;
		} else $week = $week*7;

		if(isset(self::$time["w.$week"]) && self::$time["w.$week"] >= time())
			return;

		try{
			$callback();
		}catch(\Exception $e){\Scarlets\Error::checkUncaughtError();}

		if(isset(self::$modified["w.$week"]) === false)
			self::$modified["w.$week"] = mktime(0, 0, 0, date("n"), date("j")+$week, date("Y"));
	}

	public static function monthly($month, $callback = false){
		if($month instanceof \Closure){
			$callback = $month;
			$month = 1;
		}

		if(isset(self::$time["mo.$month"]) && self::$time["mo.$month"] >= time())
			return;

		try{
			$callback();
		}catch(\Exception $e){\Scarlets\Error::checkUncaughtError();}

		if(isset(self::$modified["mo.$month"]) === false)
			self::$modified["mo.$month"] = mktime(0, 0, 0, date("n")+$month, 0, date("Y"));
	}

	public static function yearly($year, $callback = false){
		if($year instanceof \Closure){
			$callback = $year;
			$year = 1;
		}

		if(isset(self::$time["y.$year"]) && self::$time["y.$year"] >= time())
			return;

		try{
			$callback();
		}catch(\Exception $e){\Scarlets\Error::checkUncaughtError();}

		if(isset(self::$modified["y.$year"]) === false)
			self::$modified["y.$year"] = mktime(0, 0, 0, 0, 0, date("Y")+$year);
	}

	public static function cron($line, $callback = false){
		if(isset(self::$time[$line]) && self::$time[$line] >= time())
			return;

		try{
			$callback();
		}catch(\Exception $e){\Scarlets\Error::checkUncaughtError();}

		if(isset(self::$modified[$line]) === false)
			self::$modified[$line] = self::nextCronTime($line);
	}

	private static function nextCronTime($line){
		$time = time();
		list($minute, $hour, $day, $month, $year) = preg_split('/ +/', $line);
		list($minute_n, $hour_n, $day_n, $month_n, $year_n) = explode(" ", date("i H d n Y", $time));

		do{
			if($month !== '*'){
				if(self::checkCronSyntax($month, $month_n) === false){
					$month_n = $month_n + 1;
					$time = mktime(0, 0, 0, $month_n, 1, date("Y", $time));
					continue;
				}
			}

			if($day !== '*'){
				if(self::checkCronSyntax($day, $day_n) === false){
					$day_n = $day_n + 1;
					$time = mktime(0, 0, 0, $month_n, $day_n, date("Y", $time));
					continue;
				}
			}

			if($hour !== '*'){
				if(self::checkCronSyntax($hour, $hour_n) === false){
					$hour_n = $hour_n + 1;
					$time = mktime($hour_n, 0, 0, $month_n, $day_n, date("Y", $time));
					continue;
				}
			}

			if($minute !== '*'){
				if(self::checkCronSyntax($minute, $minute_n) === false){
					$minute_n = $minute_n + 1; 
					$time = mktime($hour_n, $minute_n, 0, $month_n, $day_n, date("Y", $time));
					continue;
				}
			}

			if($year !== '*'){
				if(self::checkCronSyntax($year, $year_n) === false){
					$day_n = $day_n + 1;
					$time = mktime(0, 0, 0, $month_n, $day_n, date("Y", $time));
					continue;
				}
			}

			break;
		} while(true);

		return $time;
	}

	private static function checkCronSyntax($str, $num){
		$num = intval($num);

		if(strpos($str, ',') !== false){
			$arr = explode(',', $str);
			foreach($arr as $element){
				if(self::checkCronSyntax($element, $num) === true)
					return true;
			}
			return false;
		}

		if(strpos($str, '-') !== false){
			list($low, $high) = explode('-', $str);
			if($num == (int)$low) return true;
			else return false;
		}

		if(strpos($str, '/') !== false){
			list($pre, $pos) = explode('/', $str);
			$left = $num % (int)$pos;

			if($pre === '*'){
				if($left === 0) return true;
			}
			elseif($left === (int)$pre) return true;
		}

		if((int)$str === $num) return true;	
		return false;
	}
}

Schedule::init();
// mktime(date("H"), date("i"), 0, date("n"), date("j"), date("Y"))
// 			hour 	  minute 		month 		day 	  year