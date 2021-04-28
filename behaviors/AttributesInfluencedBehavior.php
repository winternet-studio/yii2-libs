<?php
namespace winternet\yii2\behaviors;

use yii\db\ActiveRecord;
use yii\base\Behavior;

/**
 * Execute callback when an attribute has been influenced
 *
 * Can be used to trigger a callback function whenever one or more attributes have been influenced in the database,
 * either by inserting, updating or deleting a record. BUT PLEASE BE AWARE OF THE FOLLOWING:
 *
 * Think through carefully which attributes should trigger the callback. Even though the scenario you target
 * is only directly affected by a single attribute, a change in some of the other attributes might also
 * indirectly affect your scenario.
 *
 * Also, keep in mind that this isn't triggered if you execute your own INSERT/UPDATE/DELETE SQL statement,
 * either directly in the database or through a manual yii\db\Query.
 */
class AttributesInfluencedBehavior extends Behavior {

	/**
	 * @var array : Array of attributes
	 */
	public $attributes = [];

	/**
	 * @var callable : Callback that is trigger when an attribute is altered
	 *
	 * The callback will receive one argument, $event which is a yii\base\Event.
	 * `$event->name` will be either `afterInsert`, `afterUpdate`, `afterDelete`.
	 */
	public $callback = null;

	public function events() {
		return [
			ActiveRecord::EVENT_AFTER_INSERT => 'isAffected',
			ActiveRecord::EVENT_AFTER_UPDATE => 'isAffected',
			ActiveRecord::EVENT_AFTER_DELETE => 'isAffected',
		];
	}

	public function isAffected($event) {
		if ($event->name === ActiveRecord::EVENT_AFTER_INSERT || $event->name === ActiveRecord::EVENT_AFTER_DELETE) {
			call_user_func($this->callback, $event);
		} else {
			foreach ($event->changedAttributes as $changedAttribute => $oldValue) {
				if (in_array($changedAttribute, $this->attributes)) {
					call_user_func($this->callback, $event);
					break;
				}
			}
		}
	}

}
