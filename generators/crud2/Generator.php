<?php
namespace winternet\yii2\generators\crud2;

use Yii;
use yii\db\Schema;

/**
 * @inheritdoc
 */
class Generator extends \yii\gii\generators\crud\Generator {

	public $enableI18N = true;

	/**
	 * @inheritdoc
	 */
	public $enablePjax = true;


	/**
	 * @inheritdoc
	 */
	public function getName() {
		return 'CRUD2 Generator';
	}

	/**
	 * @inheritdoc
	 */
	public function getDescription() {
		return 'This is a customized version of the default Yii CRUD generator. Additionally it also allows to save and restore generator settings!';
	}

	/**
	 * @inheritdoc
	 */
    public function generateActiveField($attribute) {
    	$beforeFieldType = "\n\t\t\t  ";

        $tableSchema = $this->getTableSchema();
        if ($tableSchema === false || !isset($tableSchema->columns[$attribute])) {
            if (preg_match('/^(password|pass|passwd|passcode)$/i', $attribute)) {
                return "\$form->field(\$model, '$attribute')". $beforeFieldType ."->passwordInput()";
            } else {
                return "\$form->field(\$model, '$attribute')";
            }
        }
        $column = $tableSchema->columns[$attribute];
        if ($column->phpType === 'boolean') {
            return "\$form->field(\$model, '$attribute')". $beforeFieldType ."->checkbox()";
        } elseif ($column->type === 'text') {
            return "\$form->field(\$model, '$attribute')". $beforeFieldType ."->textarea(['rows' => 6])";
        } else {
            if (preg_match('/^(password|pass|passwd|passcode)$/i', $column->name)) {
                $input = 'passwordInput';
            } else {
                $input = 'textInput';
            }
            if (is_array($column->enumValues) && count($column->enumValues) > 0) {
                $dropDownOptions = [];
                foreach ($column->enumValues as $enumValue) {
                    $dropDownOptions[$enumValue] = Inflector::humanize($enumValue);
                }
                return "\$form->field(\$model, '$attribute')". $beforeFieldType ."->dropDownList("
                    . preg_replace("/\n\s*/", ' ', VarDumper::export($dropDownOptions)).", ['prompt' => ''])";
            } elseif ($column->phpType !== 'string' || $column->size === null) {
                return "\$form->field(\$model, '$attribute')". $beforeFieldType ."->$input()";
            } else {
                return "\$form->field(\$model, '$attribute')". $beforeFieldType ."->$input(['maxlength' => true])";
            }
        }
    }

	/**
	 * @inheritdoc
	 */
	public function generateSearchConditions() {
		$columns = [];
		if (($table = $this->getTableSchema()) === false) {
			$class = $this->modelClass;
			/* @var $model \yii\base\Model */
			$model = new $class();
			foreach ($model->attributes() as $attribute) {
				$columns[$attribute] = 'unknown';
			}
		} else {
			foreach ($table->columns as $column) {
				$columns[$column->name] = $column->type;
			}
		}

		$likeConditions = [];
		$hashConditions = [];
		foreach ($columns as $column => $type) {
			switch ($type) {
				case Schema::TYPE_SMALLINT:
				case Schema::TYPE_INTEGER:
				case Schema::TYPE_BIGINT:
				case Schema::TYPE_BOOLEAN:
				case Schema::TYPE_FLOAT:
				case Schema::TYPE_DOUBLE:
				case Schema::TYPE_DECIMAL:
				case Schema::TYPE_MONEY:
				case Schema::TYPE_DATE:
				case Schema::TYPE_TIME:
				case Schema::TYPE_DATETIME:
				case Schema::TYPE_TIMESTAMP:
					$hashConditions[] = "'{$column}' => \$this->{$column},";
					break;
				default:
					$likeConditions[] = "->andFilterWhere(['like', '{$column}', \$this->{$column}])";
					break;
			}
		}

		$conditions = [];
		if (!empty($hashConditions)) {
			$conditions[] = "\$query->andFilterWhere([\n"
				. str_repeat("\t", 3) . implode("\n" . str_repeat("\t", 3), $hashConditions)
				. "\n" . str_repeat("\t", 2) . "]);\n";
		}
		if (!empty($likeConditions)) {
			$conditions[] = "\$query" . implode("\n" . str_repeat("\t", 3), $likeConditions) . ";\n";
		}

		return $conditions;
	}
}
