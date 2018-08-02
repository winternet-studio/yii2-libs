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

		if ($options['phpErrorReportingAll']) {
			ini_set('error_reporting', E_ALL & ~E_NOTICE);
		}
		if ($options['setMemoryLimit']) {
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
	 */
	public static function l($line_str = null, $options = []) {
		self::linePrefix($options);

		if ($options['color']) {
			if (strpos($options['color'], '.') !== false) {
				$arr = [];
				$styling = explode('.', $options['color']);
				foreach ($styling as $style) {
					$arr[] = constant('\yii\helpers\Console::'. $style);
				}
				array_unshift($arr, $line_str);
				$line_str = call_user_func_array([ \Yii::$app->controller, 'ansiFormat' ], $arr);  // calling \Yii::$app->controller->ansiFormat()
			} else {
				$line_str = \Yii::$app->controller->ansiFormat($line_str, constant('\yii\helpers\Console::'. $options['color']));
			}
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

		if (!$options['raw']) {
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

	public static function stdErr($str = null) {
		file_put_contents('php://stderr', $str . "\n");
	}
}
