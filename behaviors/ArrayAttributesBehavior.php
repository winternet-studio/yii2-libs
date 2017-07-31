<?php
namespace winternet\yii2\behaviors;

use yii\db\ActiveRecord;
use yii\base\Behavior;

/**
 * For handling array attributes, being a comma-separated list of values in the database
 * Additional feature is handling of JSON strings, eg.: {"gender":"req","birthdate":"hide","addr":"req","zip":"req","city":"req","state":"opt"}
 */

class ArrayAttributesBehavior extends Behavior {

	public $attributes = [];
	public $separator = ',';
	public $jsonAttributes = [];

	public function events() {
		return [
			ActiveRecord::EVENT_AFTER_FIND => 'toArrays',
			ActiveRecord::EVENT_BEFORE_VALIDATE => 'toArrays',
			ActiveRecord::EVENT_BEFORE_INSERT => 'toStrings',
			ActiveRecord::EVENT_BEFORE_UPDATE => 'toStrings',
		];
	}

	public function toArrays($event) {
		foreach ($this->attributes as $attribute) {
			if (is_string($this->owner->$attribute)) {
				$this->owner->$attribute = explode($this->separator, $this->owner->$attribute);
			} else {
				$this->owner->$attribute = [];
			}
		}

		foreach ($this->jsonAttributes as $attribute) {
			if (is_string($this->owner->$attribute)) {
				$this->owner->$attribute = json_decode($this->owner->$attribute, true);
			}
		}
	}

	public function toStrings($event) {
		foreach ($this->attributes as $attribute) {
			if (is_array($this->owner->$attribute)) {
				$this->owner->$attribute = implode($this->separator, $this->owner->$attribute);
			}
		}

		foreach ($this->jsonAttributes as $attribute) {
			if (!is_string($this->owner->$attribute)) {
				$this->owner->$attribute = json_encode($this->owner->$attribute);
			}
		}
	}
}
