<?php
namespace winternet\yii2\behaviors;

use yii\db\ActiveRecord;
use yii\base\Behavior;

/**
 * [BETA version!] Checks for referential constraints before deleting a model
 *
 * Maybe this is not a foolproof solution as there could maybe be deeper levels of restrictions (in other tables) - we only check one level deep...
 */

class CheckReferentialConstraintsBehavior extends Behavior {

	/**
	 * @var array : Array of relations names, eg. `client`, `posts`, `userRoles` which matches the model's methods `getClient()`, `getPosts()`, `getUserRoles()`
	 *
	 * It is not possible to automatically iterate over all relations (see https://github.com/yiisoft/yii2/issues/7710), therefore we do it like this -
	 * unless we want to use Reflection like here: https://github.com/yiisoft/yii2/issues/1282
	 */
	public $relations = [];

	public function events() {
		return [
			ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
		];
	}

	public function beforeDelete($event) {
		if (preg_match("/dbname=([^;]+)/", $this->owner->getDb()->dsn, $match)) {  //example DSN: `mysql:host=localhost;dbname=myprojectname`
			$databaseName = $match[1];
		} else {
			throw new \winternet\yii2\UserException('Failed to determine database name when checking referential constraints before deleting a model.');
		}

		// Not needed for now at least
		/*
		$schema = $this->owner->getTableSchema();
		if (!empty($schema->foreignKeys)) {
			foreach ($schema->foreignKeys as $keyName => $details) {
				$table = $details[0];
			}
		}
		*/

		if (true) {
			// Method 1

			foreach ($this->relations as $relationName) {
				$relationMethod = 'get'. $relationName;
				if (method_exists($this->owner, $relationMethod)) {
					$relation = $this->owner->$relationMethod();
					$primaryTable = $this->owner::tableName();
					$relatedTableName = $relation->modelClass::tableName();
					// $primaryKey = current($relation->link);  //eg. userID
					// $foreignKey = array_keys($relation->link)[0];  //eg. role_userID

					// Find other tables that has this table as a foreign key and which prohibits automatic deletion of their records when we our record
					$constraints = $this->owner->getDb()->createCommand("SELECT * FROM `information_schema`.`REFERENTIAL_CONSTRAINTS` WHERE CONSTRAINT_SCHEMA = :databaseName AND REFERENCED_TABLE_NAME = :primaryTable AND TABLE_NAME = :foreignKeyTable", ['databaseName' => $databaseName, 'primaryTable' => $primaryTable, 'foreignKeyTable' => $relatedTableName])->queryAll();
					if (empty($constraints)) {
						continue;
					} elseif (count($constraints) === 1) {
						if ($constraints[0]['DELETE_RULE'] === 'RESTRICT') {
							// Check if that other table has any records referring to our record
							$referencingModels = $this->owner->$relationName;
							if (!empty($referencingModels)) {
								$this->owner->addError($this->determineErrorAttribute(), \Yii::t('app', 'Cannot delete record as it is referenced by {relationName}.', ['relationName' => $relationName]));
							}
						}
					} else {
						throw new \winternet\yii2\UserException('Checking referential constraint currently only works when exactly one constraint is found.', ['Model' => get_class($this->owner), 'relationName' => $relationName, 'databaseName' => $databaseName, 'primaryTable' => $primaryTable, 'foreignKeyTable' => $relatedTableName]);
					}
				} else {
					throw new \winternet\yii2\UserException('Expected method name does not exist based on the given relation name when checking referential constraints before deleting a model.', ['Model' => get_class($this->owner), 'relationName' => $relationName, 'Method name' => $relationMethod]);
				}
			}

		} else {
			// Method 2

			throw new \winternet\yii2\UserException('This alternative method has not yet been implemented when checking referential constraints before deleting a model.');

			// This method would use a join so you get the column names as well, not just the table names (but we have them in yii\db\TableSchema->foreignKeys though!)
			// But this is very slow - but is okay if it is really needed.

			// Source: https://stackoverflow.com/questions/12734331/constraint-detail-from-information-schema-on-update-cascade-on-delete-restrict
			$sql = "SELECT tb1.CONSTRAINT_NAME, tb1.TABLE_NAME, tb1.COLUMN_NAME,
			tb1.REFERENCED_TABLE_NAME, tb1.REFERENCED_COLUMN_NAME,
			tb2.UPDATE_RULE, tb2.DELETE_RULE

			FROM information_schema.`KEY_COLUMN_USAGE` AS tb1
			INNER JOIN information_schema.REFERENTIAL_CONSTRAINTS AS tb2 ON
			tb1.CONSTRAINT_NAME = tb2.CONSTRAINT_NAME AND tb1.TABLE_NAME = tb2.TABLE_NAME
			WHERE table_schema = 'forskoleutvecklingnu' AND tb1.TABLE_NAME = 'main_organizations' AND referenced_column_name IS NOT NULL";
		}

		if ($this->owner->hasErrors()) {
			$event->isValid = false;
		}
	}

	public function determineErrorAttribute() {
		// Just use the first column we find
		$attributeName = $this->owner->activeAttributes()[0];

		if (empty($attributeName)) {
			throw new \winternet\yii2\UserException('Failed to determine an attribute for assigning error message to when checking referential constraints before deleting a model.', ['Model' => get_class($this->owner)]);
		}

		return (string) $attributeName;
	}
}
