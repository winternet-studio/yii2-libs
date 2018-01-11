<?php
namespace winternet\yii2;

use Yii;
use yii\helpers\VarDumper;
use yii\helpers\Html;
use yii\web\Response;

class UserException extends \yii\base\UserException {
	public $errorCode;

	/**
	 * Raise an error, with option to continue or not, plus many other options
	 *
	 * @param string $msg
	 * @param array $arr_internal_info : Associative array with any extra information you want to log together with this error. This info is NOT shown to the end user.
	 * @param array $options : Associative array with any of these options:
	 *   - `silent` : Default: false
	 *   - `register` : Default: true
	 *   - `notify` : Default: false
	 *   - `terminate` : Default: true
	 *   - `severe` : Default: `ERROR`
	 *   - `expire` : Default: false
	 *   - `httpCode` : HTTP code for the response. Default: 500
	 *   - `senderEmail` : String `sample@email.com` or array `['sample@email.com' => 'John Doe']`. Default: none
	 *   - `adminEmail` : String `sample@email.com` or array `['sample@email.com' => 'John Doe']`. Default: none
	 *   - `developerEmail` : String `sample@email.com` or array `['sample@email.com' => 'John Doe']`. Default: none
	 */
	function __construct($msg, $arr_internal_info = [], $options = []) {
		// Handle options
		//  set default
		$silent = false;
		$register = true;
		$notify = false;
		$terminate = true;
		$severe = 'ERROR';
		$expire = false;
		$httpCode = 500;
		$senderEmail = null;
		$adminEmail = null;
		$developerEmail = null;
		//  get those that have been set at location of the error
		if (is_array($options)) {
			if (array_key_exists('severe', $options)) $options['severe'] = strtoupper( (string) $options['severe']);
			if (isset($options['notify']) && $options['notify'] === true) $options['notify'] = 'developer';  //this ONLY happens if one mistakenly set notify to true instead of one of the values! This is a safety against that.

			if (array_key_exists('silent', $options)) $silent = $options['silent'];
			if (array_key_exists('register', $options)) $register = $options['register'];
			if (array_key_exists('notify', $options) && ($options['notify'] == 'developer' || $options['notify'] == 'sysadmin' || $options['notify'] === false)) $notify = $options['notify'];
			if (array_key_exists('terminate', $options)) $terminate = $options['terminate'];
			if (isset($options['severe']) && $options['severe'] == 'WARNING' || $options['severe'] == 'ERROR' || $options['severe'] == 'CRITICAL ERROR') $severe = $options['severe'];
			if (array_key_exists('expire', $options)) $expire = $options['expire'];
			if (array_key_exists('httpCode', $options) && is_numeric($options['httpCode'])) $httpCode = $options['httpCode'];
			if (array_key_exists('senderEmail', $options) && $options['senderEmail']) $senderEmail = $options['senderEmail'];
			if (array_key_exists('developerEmail', $options) && $options['developerEmail']) $developerEmail = $options['developerEmail'];
			if (array_key_exists('adminEmail', $options) && $options['adminEmail']) $adminEmail = $options['adminEmail'];
		}


		$err_timestamp = time();
		$this->errorCode = $err_timestamp;
		$err_timestamp_read = gmdate('Y-m-d H:i:s') .' UTC';

		$errordata = '';

		$backtrace = debug_backtrace();
		if ((!isset($GLOBALS['phpunit_is_running']) || !$GLOBALS['phpunit_is_running']) && is_array($backtrace)) {
			$backtraces_count = count($backtrace);
			if ($backtraces_count > 0) {
				if ($backtraces_count == 1) {  //if only 1 entry the reference is always the same file as in URL
					//just write the line number
					$errordata .= 'Line: '. $backtrace[0]['line'] ."\r\n";
				} else {  //more than one entry
					//write the different files, lines, and functions and their arguments that were used
					foreach ($backtrace as $key => $b) {
						$errordata .= 'Level '. ($key+1) .': '. ltrim(str_replace(\Yii::$app->basePath, '', $b['file']), '\\') .'::'. $b['line'];
						if ($key != 0) {  //the first entry will always reference to this function (system_error) so skip that
							$errordata .= ' / '. $b['function'] .'(';
							if (count($b['args']) > 0) {
								$arr_args = array();
								foreach ($b['args'] as $xarg) {
									if (is_array($xarg) || is_object($xarg)) {
										try {
											$vartmp = var_export($xarg, true);
											$vartmp = str_replace('array (', ' array(', $vartmp);
										} catch (\Exception $e) {
											// use print_r instead when variable has circular references (which var_export does not handle)
											$vartmp = print_r($xarg, true);
										}
										// for very large variables (like objects) only dump the first and last part of the variable
										if (strlen($vartmp) > 2000) {
											$vartmp = rtrim(substr($vartmp, 0, 2000)) . PHP_EOL . PHP_EOL .'...[LONG DUMP TRIMMED]...'. PHP_EOL . PHP_EOL . rtrim(substr($vartmp, -2000)) . PHP_EOL .'---------------------------------------------------------------------------------'. PHP_EOL . PHP_EOL . PHP_EOL;
										}
										$arr_args[] = $vartmp;
									} else {
										$arr_args[] = var_export($xarg, true);
									}
								}
								$errordata .= implode(', ', $arr_args);
							}
							$errordata .= ')';
						}
						$errordata .= "\r\n";
					}
				}
			}
		}

		if ($msg === null) {
			$msg_string = $msg_stringHTML = 'NULL';
		} elseif (is_array($msg) || is_object($msg) || is_bool($msg)) {
			//dumpAsString is nicer and more flexible. $msg_string = json_encode($msg, JSON_PRETTY_PRINT);
			$msg_string = VarDumper::dumpAsString($msg, 10, false);
			$msg_stringHTML = '<pre>'. VarDumper::dumpAsString($msg, 10, true) .'</pre>';
		} else {
			$msg_string = $msg;
			$msg_stringHTML = $msg_string;
		}

		if (PHP_SAPI == 'cli') {
			$showmsg = "\n". $msg_string;
			if (YII_DEBUG && YII_ENV == 'dev') {
				if (!empty($arr_internal_info)) {
					$showmsg .= "\n\n". VarDumper::dumpAsString($arr_internal_info, 10, false) ."\n";
				}
			}
			// $showmsg .= $errordata;  //in CLI mode I think this is pretty much similar to what Yii already itself writes to the output...

			$extramsg = "\nError Code: ". $this->errorCode ."\n";
		} elseif (in_array(Yii::$app->response->format, [Response::FORMAT_JSON, Response::FORMAT_JSONP, Response::FORMAT_XML])) {
			$showmsg = $msg_stringHTML .' Error Code: '. $this->errorCode;
		} else {
			$showmsg = '<b class="error-msg">'. $msg_stringHTML .'</b>';
			if (YII_DEBUG && YII_ENV == 'dev') {
				if (!empty($arr_internal_info)) {
					$showmsg .= '<br><br><pre class="error-internal-info"><div>'. json_encode($arr_internal_info, JSON_PRETTY_PRINT) .'</div></pre>';
				} else {
					$showmsg .= '<br>';
				}
				$errordatashow = str_replace(' / ', '<br>', Html::encode($errordata));
				$showmsg .= '<br><pre class="error-stack"><div>'. str_replace("\r\n", '</div><div style="border-top: 1px solid #DEDEDE">', trim($errordatashow)) .'</div></pre>';
			} else {
				$showmsg .= '<br>';
			}

			$extramsg = '<br><div>If webmaster needs to be notified <a href="#" onclick="alert(\'Sorry, this has not been implemented yet! Please manually send us the error code.\');return false;">click here</a>.<br>Error Code: <b style="font-family: monospace; font-size: 105%">'. $this->errorCode .'</b></div><!--Error Code: '. $this->errorCode .'-->';  //HTML comment so that it's easy to search for the code in the log file (it matches text in CLI version)
		}

		// Store in file
		// (this has better error stack information that Yii's own app.log - more argument values are shown)
		if ($register) {
			$filemsg = "----------------------------------------------------------------------------- ". $err_timestamp_read ." -----------------------------------------------------------------------------\r\n\r\n";
			$filemsg .= $msg_string ."\r\n";
			$filemsg .= "\r\nError Code: ". $this->errorCode;
			$filemsg .= "\r\nURL: ". $_SERVER['REQUEST_METHOD'] ." ". $_SERVER['REQUEST_SCHEME'] ."://". $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			$filemsg .= "\r\nReferer: ". $_SERVER['HTTP_REFERER'];
			$filemsg .= "\r\nIP: ". $_SERVER['REMOTE_ADDR'] . ($_SERVER['REDIRECT_GEOIP_COUNTRY_NAME'] ? '   '. $_SERVER['REDIRECT_GEOIP_COUNTRY_NAME'] : '');
			if (!empty($_POST)) {
				$filemsg .= "\r\n\r\nPOST: ". json_encode($_POST, JSON_PRETTY_PRINT);
			}
			if (!empty($arr_internal_info)) {
				$filemsg .= "\r\n\r\nInternal Error Details: ". json_encode($arr_internal_info, JSON_PRETTY_PRINT);
			}
			if ($errordata) {
				$filemsg .= "\r\n\r\nStack Trace:\r\n". $errordata;
			}
			$filemsg .= "\r\n";
			file_put_contents(Yii::getAlias('@app/runtime/logs/') .'/exceptions.log', $filemsg, FILE_APPEND);
		}

		// Notify us
		if ($notify) {
			try {
				// Defaults
				if ($senderEmail) {
					$sender_address = $senderEmail;
				} elseif (Yii::$app->params && Yii::$app->params['defaultEmailSenderAddress']) {
					$sender_address = Yii::$app->params['defaultEmailSenderAddress'];
				} else {
					$sender_address = 'info@'. Yii::$app->request->getHostName();  //resort to just a guess as the last option!
					if (Yii::$app->getComponents()['mailer'] && Yii::$app->mailer->transport && Yii::$app->mailer->transport->getUsername()) {
						$smptUsername = Yii::$app->mailer->transport->getUsername();
						if ($smptUsername && filter_var($smptUsername, FILTER_VALIDATE_EMAIL)) {
							$sender_address = $smptUsername;
						}
					}
				}
				if ($notify == 'developer' && $developerEmail) {
					$recipient_address = $developerEmail;
				} else {
					if ($adminEmail) {
						$recipient_address = $adminEmail;
					} elseif (Yii::$app->params && Yii::$app->params['adminEmail']) {
						$recipient_address = Yii::$app->params['adminEmail'];
					} else {
						$recipient_address = $sender_address;
					}
				}

				Yii::$app->mailer->compose()
					->setFrom($sender_address)
					->setTo($recipient_address)
					->setSubject($severe .' in '. $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] .' (instant-notif)')
					->setTextBody($filemsg)
					->send();
			} catch (\Exception $e) {
				@file_put_contents(Yii::getAlias('@app/runtime/logs/') .'/failed_email.log', $e->getMessage() . PHP_EOL . PHP_EOL . $filemsg, FILE_APPEND);
			}
		}

		if ($terminate) {
			if (!$register) {
				Yii::$app->log->targets = [];  //don't log it anywhere
			}
			throw new \yii\web\HttpException($httpCode, $showmsg . $extramsg, $this->errorCode);
		} else {
			if (!$silent) {
				// TODO: show error
			}
			if ($register) {
				// TODO: log error if $register=true - well, currently we log it above in a file...! But that should probably be changed to a database and then ALWAYS disable Yii's own logging by setting targets to empty array!
			}
		}
	}
}
