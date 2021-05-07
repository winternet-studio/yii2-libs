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
	public $attributeMapping = [
		'userID' => 'log_userID',
		'model' => 'log_model',
		'modelID' => 'log_modelID',
		'action' => 'log_action',
		'data' => 'log_data',
		'expire' => 'log_expire',
	];

	/**
	 * @var array : Attributes that should be excluded from the "changes" log
	 */
	public $excludeChanges = [];

	/**
	 * @var array : Attributes in the "changes" log that should be masked with stars instead of showing the actual value
	 */
	public $maskValues = [];

	/**
	 * @var string : Period after which the log entry can be deleted (currently needs a separate process to do that). Any expression that the DateTime constructor accepts can be used. See https://www.php.net/manual/en/datetime.formats.relative.php
	 */
	public $expiresAfter = null;

	public function events() {
		return [
			ActiveRecord::EVENT_AFTER_INSERT => 'logChanges',
			ActiveRecord::EVENT_AFTER_UPDATE => 'logChanges',
			ActiveRecord::EVENT_AFTER_DELETE => 'logChanges',
		];
	}

	public function logChanges($event) {
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
			$to = [];
			foreach ($from as $currFromKey => $currFromValue) {
				$to[$currFromKey] = $event->sender->getAttribute($currFromKey);
			}

		} elseif ($event->name === ActiveRecord::EVENT_AFTER_DELETE) {
			$action = 'delete';
			$from = $to = null;
		}

		$modelNameClean = $this->cleanModelName($event->sender);

		$logAttributes = [
			$this->attributeMapping['userID'] => (\Yii::$app->user->isGuest ? null : \Yii::$app->user->identity->id),
			$this->attributeMapping['model'] => $modelNameClean,
			$this->attributeMapping['modelID'] => $id,
			$this->attributeMapping['action'] => $action,
		];
		if ($from || $to) {
			if ($from) {
				$logAttributes[ $this->attributeMapping['data'] ][$modelNameClean]['from'] = $this->maskModelValues($from);
			}
			if ($to) {
				$logAttributes[ $this->attributeMapping['data'] ][$modelNameClean]['to'] = $this->maskModelValues($to);
			}
		}
		if ($this->expiresAfter) {
			$logAttributes[ $this->attributeMapping['expire'] ] = (new \DateTime($this->expiresAfter))->format('Y-m-d');
		}

		$logModel = $this->logModel;
		$log = new $logModel();

		$log->setAttributes($logAttributes);

		if (!$log->save()) {
			new \winternet\yii2\UserException('Failed to log the operation.', ['Errors' => $log->getErrors(), 'Model' => $log->toArray() ]);
		}
	}

	protected function removeExcluded($keyValueArray) {
		return array_diff_key($keyValueArray, array_flip($this->excludeChanges));
	}

	protected function maskModelValues($values) {
		foreach ($this->maskValues as $attribute) {
			if (array_key_exists($attribute, $values)) {
				$values[$attribute] = '****';
			}
		}
		return $values;
	}

	protected function cleanModelName($model) {
		return str_replace('app\models\\', '', get_class($model));
	}

}
