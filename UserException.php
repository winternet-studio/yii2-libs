<?php
namespace winternet\yii2;

use Yii;
use yii\helpers\VarDumper;
use yii\helpers\Html;
use yii\web\Response;

class UserException extends \yii\base\UserException {
	public $errorCode;

	private $internalInfo = [];

	/**
	 * Raise an error, with option to continue or not, plus many other options
	 *
	 * @param string $msg
	 * @param array $arrayInternalInfo : Associative array with any extra information you want to log together with this error. This info is NOT shown to the end user.
	 * @param array $options : Associative array with any of these options:
	 *   - `register` : Boolean. Default: true
	 *   - `notify` : Possible values: `developer` or `sysadmin`. Default: false
	 *   - `terminate` : Boolean. Default: true
	 *   - `silent` : Should error be silent if `terminate` is `false`? If set to false a Bootstrap alert will be echoed. Default: true
	 *   - `severe` : Suggested values: `ERROR`, `CRITICAL ERROR`, `WARNING`, `INFO`. Any value will actually be accepted. Default: `ERROR`
	 *   - `expire` : Number of days after which the error expires and record deleted. Default: false
	 *   - `httpCode` : HTTP code for the response. Default: 500
	 *   - `senderEmail` : String `sample@email.com` or array `['sample@email.com' => 'John Doe']`. Default: none
	 *   - `adminEmail` : String `sample@email.com` or array `['sample@email.com' => 'John Doe']`. Default: none
	 *   - `developerEmail` : String `sample@email.com` or array `['sample@email.com' => 'John Doe']`. Default: none
	 *   - `databaseTable` : String with name of database table to log errors in (only if register=true). Default: none
	 *                       Table must already exist and have the following columns:
	 * ```
	 * 	CREATE TABLE `system_errors_detailed` (
	 * 		`errorID` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	 * 		`err_timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	 * 		`err_msg` TEXT NULL,
	 * 		`err_details` MEDIUMBLOB NULL COMMENT 'Information, usually misc variables in JSON format, to help with debugging',
	 * 		`err_url` TEXT NULL,
	 * 		`err_user_name` VARCHAR(255) NULL DEFAULT NULL,
	 * 		`err_stack` MEDIUMBLOB NULL,
	 * 		`err_request` MEDIUMTEXT NULL COMMENT 'Other information about the request',
	 * 		`err_code` VARCHAR(50) NULL DEFAULT NULL COMMENT 'User is shown this error code and can provide it to us for exact identification of the error in this table',
	 * 		`err_userID` INT(10) UNSIGNED NULL DEFAULT NULL,
	 * 		`err_expire_days` SMALLINT(5) UNSIGNED NULL DEFAULT NULL COMMENT 'Number of days after this error expires and can be purged',
	 * 		PRIMARY KEY (`errorID`)
	 * 	);
	 * ```
	 */
	function __construct($msg, $arrayInternalInfo = [], $options = []) {
		$this->message = $msg;
		$this->internalInfo = $arrayInternalInfo;

		// Handle options
		//  set default
		$register = true;
		$notify = false;
		$terminate = true;
		$silent = true;
		$severe = 'ERROR';
		$expire = false;
		$httpCode = 500;
		$senderEmail = null;
		$adminEmail = null;
		$developerEmail = null;
		$databaseTable = null;
		//  get those that have been set at location of the error
		if (is_array($options)) {
			if (array_key_exists('severe', $options)) $options['severe'] = strtoupper( (string) $options['severe']);
			if (isset($options['notify']) && $options['notify'] === true) $options['notify'] = 'developer';  //this ONLY happens if one mistakenly set notify to true instead of one of the values! This is a safety against that.

			if (array_key_exists('register', $options)) $register = $options['register'];
			if (array_key_exists('notify', $options) && ($options['notify'] == 'developer' || $options['notify'] == 'sysadmin' || $options['notify'] === false)) $notify = $options['notify'];
			if (array_key_exists('terminate', $options)) $terminate = $options['terminate'];
			if (array_key_exists('silent', $options)) $silent = $options['silent'];
			if (isset($options['severe'])) $severe = $options['severe'];
			if (array_key_exists('expire', $options)) $expire = $options['expire'];
			if (array_key_exists('httpCode', $options) && is_numeric($options['httpCode'])) $httpCode = $options['httpCode'];
			if (array_key_exists('senderEmail', $options) && $options['senderEmail']) $senderEmail = $options['senderEmail'];
			if (array_key_exists('developerEmail', $options) && $options['developerEmail']) $developerEmail = $options['developerEmail'];
			if (array_key_exists('adminEmail', $options) && $options['adminEmail']) $adminEmail = $options['adminEmail'];
			if (array_key_exists('databaseTable', $options) && $options['databaseTable']) $databaseTable = $options['databaseTable'];
		}


		$errorTimestamp = time();
		$errorTimestampRead = gmdate('Y-m-d H:i:s', $errorTimestamp) .' UTC';
		$this->errorCode = $errorTimestamp;

		if (Yii::$app->request->isConsoleRequest) {
			$ipAddress = 'CLI '. Yii::$app->request->getScriptFile();
			if (Yii::$app->request instanceof \yii\console\Request) {
				$ipAddress .= ' '. json_encode(Yii::$app->request->getParams(), JSON_UNESCAPED_SLASHES);  //when running tests I experience it using yii\web\Request instead of yii\console\Request and in that case getParams() doesn't exist!
			}
		} else {
			$ipAddress = Yii::$app->request->getUserIP();
		}

		$errorData = '';

		$backtrace = debug_backtrace();
		if ((!isset($GLOBALS['phpunit_is_running']) || !$GLOBALS['phpunit_is_running']) && is_array($backtrace)) {
			$backtracesCount = count($backtrace);
			if ($backtracesCount > 0) {
				if ($backtracesCount == 1) {  //if only 1 entry the reference is always the same file as in URL
					//just write the line number
					$errorData .= 'Line: '. $backtrace[0]['line'] ."\r\n";
				} else {  //more than one entry
					//write the different files, lines, and functions and their arguments that were used
					foreach ($backtrace as $key => $b) {
						if ($key > 0) {
							$errorData .= '* * * * * * *'. PHP_EOL;
						}
						$errorData .= 'Level '. ($key+1) .': '. ltrim(str_replace(\Yii::$app->basePath, '', $b['file']), '\\') .'::'. $b['line'];
						if ($key != 0) {  //the first entry will always reference to this function (system_error) so skip that
							$errorData .= ' / '. $b['function'] .'(';
							if (count($b['args']) > 0) {
								$arrArgs = array();
								foreach ($b['args'] as $xarg) {
									if (is_array($xarg) || is_object($xarg)) {
										if (YII_ENV_TEST) {  // During tests use of var_export() and print_r() resulted in memory exhausted
											$varTemp = 'N/A';
										} else {
											try {
												$varTemp = @var_export($xarg, true);
												$varTemp = str_replace('array (', ' array(', $varTemp);
											} catch (\Exception $e) {
												// use print_r instead when variable has circular references (which var_export does not handle)
												$varTemp = print_r($xarg, true);
											}
											// for very large variables (like objects) only dump the first and last part of the variable
											if (strlen($varTemp) > 2000) {
												$varTemp = rtrim(substr($varTemp, 0, 2000)) . PHP_EOL . PHP_EOL .'...[LONG DUMP TRIMMED]...'. PHP_EOL . PHP_EOL . rtrim(substr($varTemp, -2000)) . PHP_EOL;
											}
										}
										$arrArgs[] = $varTemp;
									} else {
										$arrArgs[] = var_export($xarg, true);
									}
								}
								$errorData .= implode(', ', $arrArgs);
							}
							$errorData .= ')';
						}
						$errorData .= "\r\n";
					}
				}
			}
		}

		if ($msg === null) {
			$messageString = $messageStringHtml = 'NULL';
		} elseif (is_array($msg) || is_object($msg) || is_bool($msg)) {
			//dumpAsString is nicer and more flexible. $messageString = json_encode($msg, JSON_PRETTY_PRINT);
			$messageString = VarDumper::dumpAsString($msg, 10, false);
			$messageStringHtml = '<pre>'. VarDumper::dumpAsString($msg, 10, true) .'</pre>';
		} else {
			$messageString = $msg;
			$messageStringHtml = $messageString;
		}

		if (Yii::$app->request->isConsoleRequest) {
			$showMessage = "\n". $messageString;
			if (YII_DEBUG && YII_ENV == 'dev') {
				if (!empty($this->internalInfo)) {
					if (is_string($this->internalInfo)) {
						$showMessage .= "\n\n". $this->internalInfo ."\n";
					} else {
						$showMessage .= "\n\n". VarDumper::dumpAsString($this->internalInfo, 10, false) ."\n";
					}
				}
			}
			// $showMessage .= $errorData;  //in CLI mode I think this is pretty much similar to what Yii already itself writes to the output...

			if ($register) {  //code is useless if we don't register the error
				$extraMessage = "\nError Code: ". $this->errorCode ."\n";
			}
		} elseif (in_array(Yii::$app->response->format, [Response::FORMAT_JSON, Response::FORMAT_JSONP, Response::FORMAT_XML])) {
			$showMessage = $messageStringHtml;
			if ($register) {  //code is useless if we don't register the error
				$showMessage .= ' Error Code: '. $this->errorCode;
			}
		} else {
			$showMessage = '<b class="error-msg">'. $messageStringHtml .'</b>';
			if (YII_DEBUG && YII_ENV == 'dev') {
				if (!empty($this->internalInfo)) {
					if (is_string($this->internalInfo)) {
						$showMessage .= '<br><br><pre class="error-internal-info"><div>'. $this->internalInfo .'</div></pre>';
					} else {
						$showMessage .= '<br><br><pre class="error-internal-info"><div>'. json_encode($this->internalInfo, JSON_PRETTY_PRINT) .'</div></pre>';
					}
				} else {
					$showMessage .= '<br>';
				}
				$errorDataShow = str_replace(' / ', '<br>', Html::encode($errorData));
				$showMessage .= '<br><pre class="error-stack"><div>'. str_replace("\r\n", '</div><div style="border-top: 1px solid #DEDEDE">', trim($errorDataShow)) .'</div></pre>';
			} else {
				$showMessage .= '<br>';
			}

			$extraMessage = '<br><div>If webmaster needs to be notified <a href="#" onclick="alert(\'Sorry, this has not been implemented yet! Please manually send us the error code.\');return false;">click here</a>.'. ($this->errorCode ? '<br>Error Code: <b style="font-family: monospace; font-size: 105%">'. $this->errorCode .'</b>' : '') .'</div><!--Error Code: '. ($register ? $this->errorCode : 'none') .'-->';  //HTML comment so that it's easy to search for the code in the log file (it matches text in CLI version)
		}

		// Store in file
		// (this has better error stack information that Yii's own app.log - more argument values are shown)
		if ($register) {
			if (Yii::$app->request->isConsoleRequest) {
				$host = ($_SERVER['USER'] ? $_SERVER['USER'] : $_SERVER['HOME']);  //CLI and arguments is recorded in $ipAddress
			} else {
				$host = Yii::$app->request->getHostInfo();
			}

			$url = $_SERVER['REQUEST_URI'];

			if (Yii::$app->getComponents()['user']) {
				if (!Yii::$app->user->isGuest) {
					$userID = Yii::$app->user->getId();

					// Automatically detect the name of the user!
					$userUnfo = Yii::$app->user->identity->toArray();
					$userName = '';
					foreach ($userUnfo as $fieldName => $fieldValue) {
						if (stripos($fieldName, 'name') !== false) {
							$userName .= $fieldValue .' ';
						}
					}
					$userName = trim($userName);
				}
			}
			if (!$userID) {
				$userID = null;
			}
			if (!$userName) {
				$userName = null;
			}

			$requestInfo = [
				'IP' => $ipAddress . ($_SERVER['REDIRECT_GEOIP_COUNTRY_NAME'] ? '   '. $_SERVER['REDIRECT_GEOIP_COUNTRY_NAME'] : ''),
				'Host' => $host,
				$_SERVER['REQUEST_METHOD'] => (!empty($_POST) ? $_POST : file_get_contents('php://input')),
				'Referer' => $_SERVER['HTTP_REFERER'],
				'User Agent' => $_SERVER['HTTP_USER_AGENT'],
			];
			if (!$terminate) {
				$requestInfo['Execution terminated'] = $terminate;
				$requestInfo['Silent'] = $silent;
			}
			if ($notify) {
				$requestInfo['Notified'] = $notify;
			}
			if ($severe !== 'ERROR') {
				$requestInfo['Severity'] = $severe;
			}
			if ($httpCode != 500) {
				$requestInfo['HTTP exit code'] = $httpCode;
			}

			if ($databaseTable) {
				$dbConn = \Yii::$app->db;
				if (\Yii::$app->db->getTransaction()) {  //if within a transaction create a new database connection to send query to - otherwise it's included in the transaction and won't actually be stored since the transaction at this point normally will roll back
					$dbConn = clone \Yii::$app->db;
				}

				$dbConn->createCommand("INSERT INTO `". $databaseTable ."` SET err_code = :code, err_msg = :msg, err_details = :details, err_timestamp = :timestamp, err_url = :url, err_request = :request, err_stack = :stack, err_userID = :userID, err_user_name = :user_name, err_expire_days = :expire_days",
					[
						'code' => $this->errorCode,
						'msg' => $messageString,
						'details' => (!empty($this->internalInfo) ?  (is_string($this->internalInfo) ? $this->internalInfo : $this->jsonEncodeCleaned($this->internalInfo))  : null),
						'timestamp' => gmdate('Y-m-d H:i:s', $errorTimestamp),
						'url' => $url,
						'request' => $this->jsonEncodeCleaned($requestInfo),
						'stack' => ($errorData ? $errorData : null),
						'userID' => $userID,
						'user_name' => $userName,
						'expire_days' => ($expire ? $expire : null),
					])->execute();

				$errorID = $dbConn->getLastInsertID();
				$fileLog = 'All details registered in database with error code '. $this->errorCode .' (errorID '. $errorID .').';

				// Delete expired errors once per session
				$session = Yii::$app->session;
				if ($session && !Yii::$app->request->isConsoleRequest) {  //will get message if run in CLI: "session_set_cookie_params(): Cannot change session cookie parameters when headers already sent"
					try {
						if (!$session->get('wsErrorsCleared')) {
							$dbConn->createCommand("DELETE FROM `". $databaseTable ."` WHERE err_expire_days IS NOT NULL AND TO_DAYS(err_timestamp) + err_expire_days < TO_DAYS(NOW())")->execute();
							$session->set('wsErrorsCleared', 1);
						}
					} catch (\Exception $e) {
						if (preg_match("/session_start.*headers already sent/", $e->getMessage())) {  //in case of CLI programs we might end up with this error because output was already echoed way before we got to here where Yii2 tries to open the session, so ignore that error
							// ignore exception
						} else {
							throw $e;
						}
					}
				}
			} else {
				$fileLog = "----------------------------------------------------------------------------- ". $errorTimestampRead ." -----------------------------------------------------------------------------\r\n\r\n";
				$fileLog .= $messageString ."\r\n";
				$fileLog .= "\r\nError Code: ". $this->errorCode;
				$fileLog .= "\r\nURL: ". $_SERVER['REQUEST_METHOD'] ." ". $host . $url;
				$fileLog .= "\r\nReferer: ". $_SERVER['HTTP_REFERER'];
				$fileLog .= "\r\nIP: ". $ipAddress . ($_SERVER['REDIRECT_GEOIP_COUNTRY_NAME'] ? '   '. $_SERVER['REDIRECT_GEOIP_COUNTRY_NAME'] : '');
				if (!empty($_POST)) {
					$fileLog .= "\r\n\r\nPOST: ". json_encode($_POST, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
				}
				if (!empty($this->internalInfo)) {
					$cleanedJson = $this->jsonEncodeCleaned($this->internalInfo);
					if (is_string($cleanedJson)) {
						$fileLog .= "\r\n\r\nInternal Error Details: ". $cleanedJson;
					} else {
						$fileLog .= "\r\n\r\nInternal Error Details: ". json_encode($this->internalInfo, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
					}
				}
				if ($errorData) {
					$fileLog .= "\r\n\r\nStack Trace:\r\n". $errorData;
				}
				$fileLog .= "\r\n";
				$fileLog .= "\r\nUserID: ". $userID;
				$fileLog .= "\r\nUser name: ". $userName;
				$logPath = Yii::getAlias('@app/runtime/logs/');
				\yii\helpers\FileHelper::createDirectory($logPath);
				file_put_contents($logPath .'exceptions.log', $fileLog, FILE_APPEND);
			}
		}

		// Notify us
		if ($notify) {
			try {
				// Defaults
				if ($senderEmail) {
					$senderAddress = $senderEmail;
				} elseif (Yii::$app->params && Yii::$app->params['defaultEmailSenderAddress']) {
					$senderAddress = Yii::$app->params['defaultEmailSenderAddress'];
				} else {
					$senderAddress = 'info@'. Yii::$app->request->getHostName();  //resort to just a guess as the last option!
					if (Yii::$app->getComponents()['mailer'] && Yii::$app->mailer->transport && Yii::$app->mailer->transport->getUsername()) {
						$smptUsername = Yii::$app->mailer->transport->getUsername();
						if ($smptUsername && filter_var($smptUsername, FILTER_VALIDATE_EMAIL)) {
							$senderAddress = $smptUsername;
						}
					}
				}
				if ($notify == 'developer' && $developerEmail) {
					$recipientAddress = $developerEmail;
				} else {
					if ($adminEmail) {
						$recipientAddress = $adminEmail;
					} elseif (Yii::$app->params && Yii::$app->params['adminEmail']) {
						$recipientAddress = Yii::$app->params['adminEmail'];
					} else {
						$recipientAddress = $senderAddress;
					}
				}

				Yii::$app->mailer->compose()
					->setFrom($senderAddress)
					->setTo($recipientAddress)
					->setSubject($severe .' in '. Yii::$app->id .' (instant-notif)')
					->setTextBody($fileLog . PHP_EOL . PHP_EOL . Common::getScriptReference())
					->send();
			} catch (\Exception $e) {
				@file_put_contents(Yii::getAlias('@app/runtime/logs/') .'/failed_email.log', $e->getMessage() . PHP_EOL . PHP_EOL . $fileLog, FILE_APPEND);
			}
		}

		if ($terminate) {
			if (!$register) {
				Yii::$app->log->targets = [];  //don't log it anywhere
			}
			if (Yii::$app->request->isConsoleRequest) {
				// In CLI mode we don't want to convert the exception to an HttpException, so just throw this
				// In SystemError.php we don't throw the exception, we only create it. So it's important to throw it here - otherwise the script will continue to run.
				throw $this;
			} else {
				throw new \yii\web\HttpException($httpCode, $showMessage . $extraMessage . ($databaseTable ? ' <!--WS-->' : ''), $this->errorCode, $this);
			}
		} else {
			if (!$silent) {
				echo '<div class="alert alert-danger ws-yii2-user-exception"></div>';
			}
		}
	}

	public function getInternalInfo() {
		return $this->internalInfo;
	}

	public function jsonEncodeCleaned($variable) {
		$json = json_encode($variable, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
		if ($json === false) {
			// failed to encode (possible reason from json_last_error_msg(): "Malformed UTF-8 characters, possibly incorrectly encoded")
			return print_r($variable, true);
		} else {
			return trim(str_replace("\n    ", "\n", $json), "{}\r\n");
		}
	}
}
