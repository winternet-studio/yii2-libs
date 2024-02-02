<?php
namespace winternet\yii2;

use Yii;
use yii\base\Component;
use yii\console\Controller;
use yii\helpers\Console;

class ConsoleHelper extends Component {
	public static $starttime;
	public static $lasttime;
	public static $secsWidth = 8;
	public static $memWidth = 6;
	public static $columns = [];

	/**
	 * @var string : `seconds` or `timestamp` depending whether you want to see seconds passed since script start or the current time
	 */
	private static $timeFormat = 'seconds';

	/**
	 * @var boolean : Show how long time passed since the last line was written?
	 */
	private static $lineTimingDefault = false;

	private static $lineTimingOnNext = false;

	public static function commandBegin($options = []) {
		$staticincl = Yii::getAlias('@app/web/static_includes.php');
		if (file_exists($staticincl)) {
			require_once($staticincl);
		}

		if (@$options['phpErrorReportingAll']) {
			ini_set('error_reporting', E_ALL & ~E_NOTICE);
		}
		if (@$options['setMemoryLimit']) {
			ini_set('memory_limit', $options['setMemoryLimit']);
		}

		self::$starttime = microtime(true);
		echo "\n". (new Controller(9999, 9999))->ansiFormat('=========================================================================', Console::FG_BLUE) ."\n";
	}

	public static function commandEnd() {
		self::linePrefix();

		$endtime = microtime(true);
		echo (new Controller(9999, 9999))->ansiFormat("\n\nCompleted at ". date('Y-m-d H:i:s') ."\n", Console::FG_GREEN);
		//WRITING ACC TIME IN EACH LINE INSTEAD NOW. echo (new Controller(9999, 9999))->ansiFormat("\n\nDone in ". round($endtime - self::$starttime, 1) ." secs at ". date('H:i:s') ." - Peak Memory: ". round(memory_get_peak_usage(true)/1024/1024) ."M\n", Console::FG_GREEN);
		echo (new Controller(9999, 9999))->ansiFormat('=========================================================================', Console::FG_BLUE) ."\n";
	}

	/**
	 * Echo a line of text
	 *
	 * Examples:
	 * `ConsoleHelper::l('Some information', ['color' => 'FG_YELLOW'])`
	 * `ConsoleHelper::l('Some information', ['color' => 'FG_RED.BOLD'])`
	 * `ConsoleHelper::l('Some information', ['color' => 'FG_WHITE.BOLD.BG_BLUE'])`
	 * All possible colors: https://www.yiiframework.com/doc/api/2.0/yii-helpers-console
	 */
	public static function l($line_str = null, $options = []) {
		self::linePrefix($options);

		if (@$options['color']) {
			$line_str = self::applyColor($line_str, $options['color']);
		}

		echo $line_str;
		self::$lasttime = microtime(true);
	}

	public static function linePrefix($options = []) {
		if (self::$lineTimingDefault || self::$lineTimingOnNext) {
			if (self::$lasttime) {
				echo (new Controller(9999, 9999))->ansiFormat('  '. number_format(microtime(true) - self::$lasttime, 1) .'s', Console::FG_PURPLE);
			}
			self::$lineTimingOnNext = false;
		}

		echo "\n";

		if (!@$options['raw']) {
			if (self::$timeFormat == 'seconds') {
				$accum_time = microtime(true) - self::$starttime;
				if ($accum_time >= 61) {
					$accum_time = $accum_time / 60;
					$unit = 'm';
				} else {
					$unit = 's';
				}
				$time = str_pad(number_format($accum_time, 3) . $unit, self::$secsWidth, ' ');
			} else {
				$time = date('Y-m-d H:i:s') .'   ';
			}
			echo (new Controller(9999, 9999))->ansiFormat($time . str_pad(round(memory_get_peak_usage(true)/1024/1024) .'M', self::$memWidth, ' '), Console::FG_CYAN);
		}
	}

	/**
	 * Examples:
	 * `ConsoleHelper::row('First column', 'Second column', 'Third column')`
	 */
	public static function row($options = []) {
		$arguments = func_get_args();
		$separator = (new Controller(9999, 9999))->ansiFormat('|', Console::FG_CYAN);
		echo "\n";
		foreach ($arguments as $index => $argument) {
			if ($index > 0) {
				echo ' ';
			}
			echo $separator .' ';

			$fullWidthString = self::mb_str_pad($argument, self::$columns[$index]['width'], ' ', (self::$columns[$index]['align'] == 'right' ? STR_PAD_LEFT : STR_PAD_RIGHT));
			if (!empty(self::$columns[$index]['color'])) {
				echo self::applyColor($fullWidthString, self::$columns[$index]['color']);
			} else {
				echo $fullWidthString;
			}
		}
		echo ' '. $separator;
	}

	public static function rowSeparator() {
		echo "\n";
		foreach (self::$columns as $index => $column) {
			echo (new Controller(9999, 9999))->ansiFormat('+', Console::FG_CYAN);
			echo (new Controller(9999, 9999))->ansiFormat(str_repeat('-', $column['width'] + 2), Console::FG_CYAN);  // +2 is padding
		}
		echo (new Controller(9999, 9999))->ansiFormat('+', Console::FG_CYAN);
	}

	/**
	 * Configure columns
	 *
	 * Example:
	 * ```
	 * ConsoleHelper::setColumns([
	 * 	['width' => 7],
	 * 	['width' => 10, 'color' => 'FG_YELLOW', 'align' => 'right'],
	 * 	['width' => 10, 'color' => 'FG_YELLOW', 'align' => 'right'],
	 * 	['width' => 10, 'color' => 'FG_YELLOW', 'align' => 'right'],
	 * 	['width' => 50, 'color' => 'FG_YELLOW'],
	 * 	['width' => 20, 'color' => 'FG_YELLOW'],
	 * 	['width' => 10, 'color' => 'FG_YELLOW'],
	 * ]);
	 * ```
	 * @param array $columns : Example: `[['width' => 20], ['width' => 20, 'align' => 'right'], ['width' => 40]]`
	 */
	public static function setColumns($columns) {
		self::$columns = $columns;
	}

	/**
	 * @param string $color : Eg. `FG_YELLOW`, `FG_RED.BOLD`, `FG_WHITE.BOLD.BG_BLUE`
	 */
	public static function applyColor($string, $color) {
		if (strpos($color, '.') !== false) {
			$arr = [];
			$styling = explode('.', $color);
			foreach ($styling as $style) {
				$arr[] = constant('\yii\helpers\Console::'. $style);
			}
			array_unshift($arr, $string);
			$string = call_user_func_array([ \Yii::$app->controller, 'ansiFormat' ], $arr);  // calling \Yii::$app->controller->ansiFormat()
		} else {
			$string = \Yii::$app->controller->ansiFormat($string, constant('\yii\helpers\Console::'. $color));
		}
		return $string;
	}

	public static function setTimeFormat($format) {
		self::$timeFormat = $format;
	}

	public static function enableLineTiming() {
		self::$lineTimingDefault = true;
	}

	public static function disableLineTiming() {
		self::$lineTimingDefault = false;
		self::$lineTimingOnNext = true;
	}

	/**
	 * @see https://stackoverflow.com/a/27194169/2404541
	 */
	public static function mb_str_pad($str, $pad_len, $pad_str = ' ', $dir = STR_PAD_RIGHT, $encoding = NULL) {
	    $encoding = $encoding === NULL ? mb_internal_encoding() : $encoding;
	    $padBefore = $dir === STR_PAD_BOTH || $dir === STR_PAD_LEFT;
	    $padAfter = $dir === STR_PAD_BOTH || $dir === STR_PAD_RIGHT;
	    $pad_len -= mb_strlen($str, $encoding);
	    $targetLen = $padBefore && $padAfter ? $pad_len / 2 : $pad_len;
	    $strToRepeatLen = mb_strlen($pad_str, $encoding);
	    $repeatTimes = ceil($targetLen / $strToRepeatLen);
	    $repeatedString = str_repeat($pad_str, max(0, $repeatTimes)); // safe if used with valid unicode sequences (any charset)
	    $before = $padBefore ? mb_substr($repeatedString, 0, (int)floor($targetLen), $encoding) : '';
	    $after = $padAfter ? mb_substr($repeatedString, 0, (int)ceil($targetLen), $encoding) : '';
	    return $before . $str . $after;
	}

	public static function stdErr($str = null) {
		file_put_contents('php://stderr', $str . "\n");
	}
}
