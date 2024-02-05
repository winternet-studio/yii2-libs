<?php
namespace winternet\yii2;

use \yii\log\DbTarget;
use yii\helpers\VarDumper;

class CustomDbTarget extends DbTarget {

    /**
     * Set this to false to bypass this entire customization
     */
    public $customizationEnabled = true;

    /**
     * @inheritdoc
     *
     * Same as original `\yii\log\Target->collect()` but with these changes:
     *
     * - add context message to existing message instead of making it a separate message. Then we log each message with
     *   winternet\yii2\UserException before passing the data on to the orignal collect() method which logs the data like normal.
     *
     * It requires and works together with `\winternet\yii2\UserException`
     *
     * Test changes with these scenarios:
     *
     * ```
     * throw new \yii\base\UserException('STOP1');
     * throw new \NonexistingExceptionClass('STOP2');
     * throw new \Exception('STOP3');
     * throw new \Exception('STOP-PARSE-ERROR';
     * file_put_contents();  //PHP error
     * throw new \yii\web\HttpException(532, 'Failed HTTP', 82155654);
     * new \winternet\yii2\UserException('STOP4', ['internalInfo' => 43, 'moreInfo' => true]);
     * new \winternet\yii2\UserException('STOP5', ['internalInfo' => 43, 'moreInfo' => true], ['databaseTable' => 'system_errors_detailed']);
     * \Yii::$app->system->error('STOP6', ['internalInfo' => 43]);
     * ```
     *
     * In case we find out this is a bad solution an alternative solution could be to override the export() method,
     * and maybe create and record a unique request ID with the following code before saving to database:
     *
     * ```
     * if (!Yii::$app->params['_request_guid']) {
     *     Yii::$app->params['_request_guid'] = uniqid() .'-'. rand(1000, 9999);
     * }
     * ```
     */
    public function collect($messages, $final) {
        if (!$this->customizationEnabled) {
            // Behave completely like normal
            parent::collect($messages, $final);
            return;
        }

        $databaseTable = \Yii::$app->system->databaseTable;
        if ($databaseTable) {  //only do anything here if we know which database table to store the message(s) in

            $this->messages = array_merge($this->messages, static::filterMessages($messages, $this->getLevels(), $this->categories, $this->except));
            $count = count($this->messages);
            $sep = '=======================================================';
            if ($count > 0 && ($final || $this->exportInterval > 0 && $count >= $this->exportInterval)) {

                // Log each message with winternet\yii2\UserException
                foreach ($this->messages as $message) {
                    // Get the message details like in the export() method
                    list($text, $level, $category, $timestamp) = $message;

                    // Skip levels higher than 2 (don't recall why I added this so I disabled it again...)
                    // if ($level > 2) continue;

                    if (!is_string($text)) {
                        // exceptions may not be serializable if in the call stack somewhere is a Closure
                        if ($text instanceof \Throwable || $text instanceof \Exception) {
                            $text = (string) $text;
                        } else {
                            $text = VarDumper::export($text);
                        }
                    }

                    // Skip messages that were already created directly by winternet\yii2\UserException
                    if (strpos($text, '<!--WS-->') !== false) continue;

                    // Append the context message (which is what holds dump of $_GET, $_POST etc)
                    if (($context = $this->getContextMessage()) !== '') {
                        $text .= "\n\n$sep\n\n". $context;
                    }

                    if (substr($text, 0, 7) === 'Error: ') {  //cut away unnecessary text
                        $text = substr($text, 7);
                    }

                    // Log it
                    new \winternet\yii2\UserException(trim(strtok($text, "\n")), "$text\n\n$sep\n\nLevel: $level\nCategory: $category\nUnix Timestamp: ". round($timestamp, 4) /*round it to match what is stored in Yii's table*/, ['terminate' => false, 'silent' => true, 'databaseTable' => $databaseTable]);
                }
            }
        }

        parent::collect([], $final);  //don't pass $messages because we already merged them onto $this->messages above!
    }
}
