<?php
namespace winternet\yii2;

use yii\base\Component;

class DatabaseHelper extends Component {
	/**
	 * Convert search model attribute (from crud2) into ActiveQuery conditions
	 *
	 * @param string $attribute : name of the field, eg. `cust_name` or `customer.cust_name` where the first part is the model name
	 * 	- in case of $operator=soundslike the actual table name must be used instead of the model name
	 * @param mixed $value : value to put into the field (can be a string or array). If empty value (length = 0) field will be set to NULL unless the flag 'noNull' is set
	 * @param array $options : possible keys:
	 * 	- 'field' : set true to interpret the value as being another field, will be enclosed by `-characters
	 * 	- 'passthrough' : set true to pass value directly through, which basically means skip adding single quotes around the value
	 * 	                  WARNING: be careful with using this one! It might add potential security holes.
	 * 	- NOT YET IMPLEMENTED. 'noNull' : set true to not use NULL values (= don't convert empty string to NULL)
	 * @param string $operator (opt.) : one of the following values for using other than "equal to" (=):
	 * 	- 'equal' (default) : equal to
	 * 	- 'notequal'        : not equal to
	 * 	- 'contains'    : value contains...         (wildcards % (match multiple characters) and _ (match single character) can be used in the criteria, % is autoamtically prefixed and suffixed and all spaces are replacd with %)
	 * 	- 'containsnot' : value does not contain... (wildcards % (match multiple characters) and _ (match single character) can be used in the criteria, % is autoamtically prefixed and suffixed and all spaces are replacd with %)
	 * 	- 'soundslike' : value sounds like/is similar to...
	 * 	- 'empty'    : value must be empty (IS NULL)
	 * 	- 'notempty' : value must not be empty (IS NOT NULL)
	 * 	- 'in'     : value is one of these...     (format example: 'denmark, norway' (STRING) or array('denmark', 'norway') (ARRAY) )
	 * 	- 'not_in' : value is not one of these... (format example: 'denmark, norway' (STRING) or array('denmark', 'norway') (ARRAY) )
	 * 	- 'lt'   : less than
	 * 	- 'lteq' : less than or equal to
	 * 	- 'gt'   : greater than
	 * 	- 'gteq' : greater than or equal to
	 * 	- 'between'     : value must be between A and B     (format example: '2 and 10')
	 * 	- 'notbetween'  : value must not be between A and B (format example: '2 and 10')
	 * 	- 'regexp'        : value must match regular expression (see MySQL documentation)
	 * 	- 'notregexp'     : value must match regular expression (see MySQL documentation)
	 *
	 * @return array : according to the Hash format and Operator format documentation: https://www.yiiframework.com/doc/api/2.0/yii-db-queryinterface#where()-detail
	 */
	public static function modelToCondition($attribute, $value, $operator = 'equal', $options = []) {
		// DON'T THINK WE NEED THIS HERE...
		// if (($value === '' || $value === false || $value === null || $value === array()) && strpos($options, 'noNull') === false) {  //NOTE: see test_if_value_is_db_NULL.php
		// 	return [$attribute => null];
		// } else {
			if (@$options['field']) {
				$value = new \yii\db\Expression('`'. str_replace('`', '', $value) .'`');
			} elseif (@$options['passthrough']) {
				$value = new \yii\db\Expression($value);
			} elseif ($operator == 'contains' || $operator == 'containsnot') {
				$value = str_replace(' ', '%', (string) $value);  //add wildcards within the text
			}

			// Determine operator (and sometimes adjust the value)
			switch ($operator) {
			case 'equal':
				return [$attribute => $value];
				break;
			case 'notequal':
				return ['not', [$attribute => $value]];
				break;
			case 'contains':
				$operator = 'like';
				break;
			case 'containsnot':
				$operator = 'not like';
				break;
			case 'soundslike':
				$operator = '=';
				$attribute = new \yii\db\Expression("SOUNDEX(`". $attribute ."`)");
				if (@$options['field'] || @$options['passthrough']) {
					$value = new \yii\db\Expression("SOUNDEX(". $value .")");
				} else {
					$value = new \yii\db\Expression("SOUNDEX(". \Yii::$app->db->quoteValue($value) .")");
				}
				break;
			case 'empty':
				return [$attribute => null];
				break;
			case 'notempty':
				return ['not', [$attribute => null]];
				break;
			case 'in':
			case 'notin':
				$operator = ($operator == 'in' ? 'in' : 'not in');
				if (!is_array($value)) {
					$valueparts = explode(',', $value);
				} else {
					$valueparts = $value;
				}
				foreach ($valueparts as $key => $curr) {
					if (is_string($curr)) {
						$valueparts[$key] = trim($curr);
					}
				}
				$value = $valueparts;
				break;
			case 'lt':
				$operator = '<';
				break;
			case 'lteq':
				$operator = '<=';
				break;
			case 'gt':
				$operator = '>';
				break;
			case 'gteq':
				$operator = '>=';
				break;
			case 'between':
			case 'notbetween':
				$pattern = '/^(.+)\\s*AND\\s*?(.+)$/iU';
				if (preg_match($pattern, $value, $match)) {
					$operator = ($operator == 'between' ? 'between' : 'not between');
					return [$operator, $attribute, $match[1], $match[2]];
				} else {
					new \winternet\yii2\UserException('Search parameter for the field \''. $attribute .'\' is not written correctly. The lower and upper value must be separated by the keyword AND. Dates must be in the format yyyy-mm-dd.', ['Field' => $attribute, 'Value' => $value], ['register' => false]);
				}
				break;
			case 'regexp':
			case 'notregexp':
				$operator = ($operator == 'regexp' ? 'REGEXP' : 'NOT REGEXP');
				break;
			default:
				new \winternet\yii2\UserException('Invalid comparison sign for a criteria in the database query.', ['Operator' => $operator, 'Field' => $attribute, 'Value' => $value]);
			}

			return [$operator, $attribute, $value];
		// }
	}

	/**
	 * A list of possible compare operators for searching
	 *
	 * @return array
	 */
	public static function compareOperators() {
		return [
			'equal' => '=',
			'notequal' => 'not =',
			'contains' => 'contains',
			'containsnot' => 'contains not',
			'soundslike' => 'is similar to',
			'empty' => 'is empty',
			'notempty' => 'is not empty',
			'in' => 'is one of',
			'notin' => 'is not one of',
			'lt' => '<',
			'lteq' => '<=',
			'gt' => '>',
			'gteq' => '>=',
			'between' => 'is between',
			'notbetween' => 'is not between',
			'regexp' => 'regular expr.',
			'notregexp' => 'not regular expr.',
		];
	}

	/**
	 * Help for the end-user to know how to use the different search operators
	 *
	 * @return array : Formatted for direct use in options for Html::dropDownList()
	 */
	public static function operatorHints() {
		return [
			'equal' => ['title' => ''],
			'notequal' => ['title' => ''],
			'contains' => ['title' => 'Field must contain this criteria. Also, wildcards can be used. Percentage (<code>%</code>) will match none, one or more characters. Underscore (<code>_</code>) will match exactly one character. Example: <code>w%jones</code>'],
			'containsnot' => ['title' => 'Field may not contain this criteria. Also, wildcards can be used. Percentage (<code>%</code>) will match none, one or more characters. Underscore (<code>_</code>) will match exactly one character. Example: <code>w%jones</code>'],
			'soundslike' => ['title' => 'Field must be similar to, or sound like the criteria, eg. useful if you are not sure about the spelling.'],
			'empty' => ['title' => ''],
			'notempty' => ['title' => ''],
			'in' => ['title' => 'Field must be one of these criterias. Separate criterias with comma. Example: <code>chair, table, sofa</code>'],
			'notin' => ['title' => 'Field may not be one of these criterias. Separate criterias with comma. Example: <code>chair, table, sofa</code>'],
			'lt' => ['title' => ''],
			'lteq' => ['title' => ''],
			'gt' => ['title' => ''],
			'gteq' => ['title' => ''],
			'between' => ['title' => 'Field must be between A and B (both inclusive). Works only correctly with number fields. Example: <code>45 and 100</code>'],
			'notbetween' => ['title' => 'Field may not be between A and B (both inclusive). Works only correctly with number fields. Example: <code>45 and 100</code>'],
			'regexp' => ['title' => 'Field must match the <em>regular expression</em> entered as criteria. This is a method for writing complex criterias to match certain patterns of values. <a href="https://dev.mysql.com/doc/refman/8.0/en/regexp.html" target="_blank" rel="noopener noreferrer">Click here</a> for documentation on <em>regular expressions</em>. Example: <code>[a-z]{2}[0-9]{4}</code> (would match a value beginning with 2 letters followed by 4 digits)'],
			'notregexp' => ['title' => 'Field may not match the <em>regular expression</em> entered as criteria. This is a method for writing complex criterias to match certain patterns of values. <a href="https://dev.mysql.com/doc/refman/8.0/en/regexp.html" target="_blank" rel="noopener noreferrer">Click here</a> for documentation on <em>regular expressions</em>. Example: <code>[a-z]{2}[0-9]{4}</code> (would match a value beginning with 2 letters followed by 4 digits)'],
		];
	}
}
