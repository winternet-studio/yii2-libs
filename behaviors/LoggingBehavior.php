<?php
namespace winternet\yii2\behaviors;

use yii\db\ActiveRecord;
use yii\base\Behavior;

/**
 * For logging of all changes to the model
 */

class LoggingBehavior extends Behavior {

	/**
	 * @var string : Model that handles the recording of the log
	 */
	public $logModel = 'app\models\Log';

	/**
	 * @var array : Array that maps the different values to attributes in the model specified in $logModel
	 */
	public $attributeMapping = [  //also defined further down
		'userID' => 'log_userID',
		'model' => 'log_model',
		'modelID' => 'log_modelID',
		'action' => 'log_action',
		'data' => 'log_data',
		'expire' => 'log_expire',
	];

	/**
	 * @var mixed : Value to use as userID during a console application. Be aware of relations/MySQL JOIN queries though - might want to use `null` for that reason.
	 */
	public $consoleUserID = 0;  //default to 0 since we assume a normal user will never have ID 0 (at least never should)

	/**
	 * @var array : Exclude logging these events. Currently these are available: `insert`, `update`, `delete`
	 */
	public $excludeEvents = [];

	/**
	 * @var array : Attributes that should be excluded from the "changes" log
	 */
	public $excludeAttributes = [];

	/**
	 * @var array : Attributes in the "changes" log that should be masked with stars instead of showing the actual value
	 */
	public $maskValues = [];

	/**
	 * @var boolean : Set true to always create log entry on an update, even if the array with effective changes to log is empty
	 */
	public $logUpdateIfNoChanges = false;

	/**
	 * @var boolean : Set false to not remove changes where only the type has changed (eg. string "100" changed to integer 100)
	 */
	public $nonStrictChangesOnly = true;

	/**
	 * @var boolean : Set true to pretty-print JSON with the changes
	 */
	public $prettyPrintJson = false;

	/**
	 * @var string : Period after which the log entry can be deleted (needs a separate process to do that). Any expression that the DateTime constructor accepts can be used. See https://www.php.net/manual/en/datetime.formats.relative.php
	 */
	public $expiresAfter = null;

	/**
	 * @var array : Array of base classes (fully qualified names) we want to use in logging if the actual model is a subclass
	 */
	public $baseClasses = [];

	public function events() {
		$events = [];
		if (!in_array('insert', $this->excludeEvents)) {
			$events[ActiveRecord::EVENT_AFTER_INSERT] = 'logChanges';
		}
		if (!in_array('update', $this->excludeEvents)) {
			$events[ActiveRecord::EVENT_AFTER_UPDATE] = 'logChanges';
		}
		if (!in_array('delete', $this->excludeEvents)) {
			$events[ActiveRecord::EVENT_AFTER_DELETE] = 'logChanges';
		}
		return $events;
	}

	public function logChanges($event) {
		$attrMap = array_merge([
			'userID' => 'log_userID',
			'model' => 'log_model',
			'modelID' => 'log_modelID',
			'action' => 'log_action',
			'data' => 'log_data',
			'expire' => 'log_expire',
		], $this->attributeMapping);

		$id = null;
		if (is_numeric($event->sender->primaryKey)) {
			$id = $event->sender->primaryKey;
		}

		if ($event->name === ActiveRecord::EVENT_AFTER_INSERT) {
			$action = 'insert';
			$from = null;
			$to   = $this->removeExcluded($event->sender->attributes);

		} elseif ($event->name === ActiveRecord::EVENT_AFTER_UPDATE) {
			$action = 'update';
			$from = $this->removeExcluded($event->changedAttributes);  //hopefully this is correct - see https://stackoverflow.com/questions/51645487/yii-2-getoldattribute-method-not-working-in-aftersave
			$this->convertArrayAttributes($event->sender, $from);
			$to = [];
			foreach ($from as $currFromKey => $currFromValue) {
				$to[$currFromKey] = $event->sender->getAttribute($currFromKey);
			}

		} elseif ($event->name === ActiveRecord::EVENT_AFTER_DELETE) {
			$action = 'delete';
			$from = $this->removeExcluded($event->sender->attributes);
			$to   = null;
		}

		$modelClass = null;
		if (!empty($this->baseClasses)) {
			foreach ($this->baseClasses as $fqClassName) {
				if ($event->sender instanceOf $fqClassName) {
					$modelClass = $fqClassName;
				}
			}
		}
		if (!$modelClass) {
			$modelClass = get_class($event->sender);
		}

		$logAttributes = [];

		if ($from || $to) {
			if ($event->name === ActiveRecord::EVENT_AFTER_UPDATE) {
				foreach ($from as $currAttr => $currValue) {
					if (is_array($from[$currAttr]) && is_array($to[$currAttr])) {  //compare array attributes correctly
						if (serialize($from[$currAttr]) === serialize($to[$currAttr])) {  //see https://stackoverflow.com/questions/804045/preferred-method-to-store-php-arrays-json-encode-vs-serialize
							unset($from[$currAttr], $to[$currAttr]);
						}
					}
				}
				if ($this->nonStrictChangesOnly) {
					foreach ($from as $currAttr => $currValue) {
						if (is_array($from[$currAttr]) && is_array($to[$currAttr])) {  //ensure array attributes are not converted to just "Array" and removed by the code below (they are handled above instead)
							// do nothing, they are handled above
						} else {
							if ((string) $from[$currAttr] === (string) $to[$currAttr]) {
								unset($from[$currAttr], $to[$currAttr]);
							}
						}
					}
				}
			}

			if (!empty($from)) {
				$logAttributes[ $attrMap['data'] ][$modelClass]['from'] = $this->maskModelValues($from);
			}
			if (!empty($to)) {
				$logAttributes[ $attrMap['data'] ][$modelClass]['to'] = $this->maskModelValues($to);
			}
		}

		if ($event->name !== ActiveRecord::EVENT_AFTER_UPDATE || $this->logUpdateIfNoChanges || !empty($logAttributes[ $attrMap['data'] ])) {
			if (\Yii::$app->request->isConsoleRequest) {  //in a console app the user component normally will not exist
				if (!\Yii::$app->has('user') || \Yii::$app->user->isGuest) {
					$logAttributes[ $attrMap['userID'] ] = $this->consoleUserID;
				} else {
					$logAttributes[ $attrMap['userID'] ] = \Yii::$app->user->identity->id;
				}
			} else {
				$logAttributes[ $attrMap['userID'] ] = (\Yii::$app->user->isGuest ? null : \Yii::$app->user->identity->id);
			}
			$logAttributes[ $attrMap['model'] ] = $modelClass;
			$logAttributes[ $attrMap['modelID'] ] = $id;
			$logAttributes[ $attrMap['action'] ] = $action;

			if ($this->expiresAfter) {
				$logAttributes[ $attrMap['expire'] ] = (new \DateTime($this->expiresAfter))->format('Y-m-d');
			}

			$logModel = $this->logModel;
			$log = new $logModel();

			if (is_array($logAttributes[ $attrMap['data'] ])) {
				if ($this->prettyPrintJson) {
					$logAttributes[ $attrMap['data'] ] = json_encode($logAttributes[ $attrMap['data'] ], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
				} else {
					$logAttributes[ $attrMap['data'] ] = json_encode($logAttributes[ $attrMap['data'] ], JSON_UNESCAPED_SLASHES);
				}
			}

			$log->setAttributes($logAttributes);

			if (!$log->save()) {
				new \winternet\yii2\UserException('Failed to log the operation.', ['Errors' => $log->getErrors(), 'Model' => $log->toArray() ]);
			}
		}
	}

	/**
	 * If any attributes has been set as array attributes using  winternet\yii2\behaviors\ArrayAttributesBehavior, ensure they are not strings but arrays when being logged
	 */
	public function convertArrayAttributes($model, &$changes) {
		if (!is_array($changes) || empty($changes)) {
			return;
		}
		foreach ($model->getBehaviors() as $behavior) {
			if ($behavior instanceOf ArrayAttributesBehavior) {
				$tempModel = clone $behavior->owner;
				$tempModel->setAttributes($changes, false);
				$tempModel->trigger(\yii\db\ActiveRecord::EVENT_AFTER_FIND);
				$changes = $tempModel->getAttributes(array_keys($changes));
				break;
			}
		}
	}

	protected function removeExcluded($keyValueArray) {
		return array_diff_key($keyValueArray, array_flip($this->excludeAttributes));
	}

	protected function maskModelValues($values) {
		foreach ($this->maskValues as $attribute) {
			if (array_key_exists($attribute, $values)) {
				$values[$attribute] = '****';
			}
		}
		return $values;
	}

	/**
	 * @param string $model : Model class name, usually by `get_class($modelInstance)`
	 */
	protected function cleanModelName($model) {
		return str_replace('app\models\\', '', $model);
	}

}
