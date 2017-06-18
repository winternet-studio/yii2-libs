<?php
namespace winternet\yii2\behaviors;

use yii\db\ActiveRecord;
use yii\base\Behavior;

/**
 * For cleaning up user entered values, like trimming and setting blank string to null
 */

class CleanupBehavior extends Behavior {

	public $trimString = true;
	public $emptyStringToNull = true;

	public function events() {
		return [
			ActiveRecord::EVENT_BEFORE_VALIDATE => 'beforeValidate',
		];
	}

	public function beforeValidate($event) {
		foreach ($this->owner->attributes as $key => $value) {
			if (is_string($this->owner->$key)) {
				if ($this->trimString) {
					$this->owner->$key = trim($this->owner->$key);
				}
				if ($this->emptyStringToNull && $this->owner->$key === '') {
					$this->owner->$key = null;
				}
			}
		}
	}
}
