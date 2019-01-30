<?php
namespace winternet\yii2;

use yii\base\Component;

class JobQueue extends Component {
	private $db = 'db';
	private $dbName = null;
	private $queueID = null;
	private $sanitizeCallback = null;


	public function __construct($params = []) {
		if ($params['db']) {
			$this->db = $params['db'];
		}
		if ($params['dbName']) {
			$this->dbName = $params['dbName'];
		}
		if ($params['queueID']) {
			$this->queueID = $params['queueID'];
		}
	}

	public function startJob($jobID) {
		\Yii::$app->{$this->db}->createCommand("UPDATE ". ($this->dbName ? '`'. $this->dbName .'`.' : '') ."system_job_queue SET job_time_started = UTC_TIMESTAMP(), job_replaceID = NULL WHERE jobID = :id", ['id' => $jobID])->execute();
	}

	public function finishJob($jobID, $exit_status = 0) {
		\Yii::$app->{$this->db}->createCommand("UPDATE ". ($this->dbName ? '`'. $this->dbName .'`.' : '') ."system_job_queue SET job_time_completed = UTC_TIMESTAMP(), job_exit_status = :status WHERE jobID = :id", ['id' => $jobID, 'status' => $exit_status])->execute();
	}


	public function runJob($jobID) {
		$job = \Yii::$app->{$this->db}->createCommand("SELECT * FROM ". ($this->dbName ? '`'. $this->dbName .'`.' : '') ."system_job_queue WHERE jobiD = :id AND job_time_started IS NULL", ['id' => $jobID])->queryOne();

		if (empty($job)) return false;

		if ($job['job_queueID'] != $this->queueID) return false;  //skip jobs not in this queue

		if ($job['job_postpone_time'] && $job['job_postpone_time'] > (new \DateTime())->getTimestamp() ) return false;  //skip jobs that have been postponed for later

		$this->consoleLog('Executing job #'. $jobID .'...');

		$this->startJob($job['jobID']);

		$command = json_decode($job['job_command']);

		if ($command->route) {
			$cmdargs = [];
			foreach ($command->params as $key => $value) {
				$cmdargs[] = '--'. $key .'="'. $value .'"';
			}

			$cmd = 'php yii '. $command->route;
			if ($cmdargs) {
				$cmd .= ' '. implode(' ', $cmdargs);
			}
		} else {
			if (!$this->$sanitizeCallback || !is_callable($this->$sanitizeCallback)) {
				// DON'T ALLOW UNSANITIZED COMMANDS FOR SECURITY REASONS. If someone gained access to the database and script runs in sudo (though is most cases that shouldn't be necessary) they would be able to run any command!
				new \winternet\yii2\UserException('Missing function for sanitizing command from JobQueue.');
			} else {
				$cmd = $this->$sanitizeCallback($command);
			}
		}

		$output = []; $exitcode = 0;
		// $this->consoleLog('Command: '. $cmd);
		exec($cmd .' 2>&1', $output, $exitcode);

		$this->finishJob($job['jobID'], substr($exitcode . implode('', $output), 0, 500));

		return true;
	}

	public function runAll($params = []) {
		// Execute all pending jobs
		$jobs = \Yii::$app->{$this->db}->createCommand("SELECT * FROM ". ($this->dbName ? '`'. $this->dbName .'`.' : '') ."system_job_queue WHERE job_time_started IS NULL ORDER BY job_priority DESC")->queryAll();

		chdir(\Yii::getAlias('@app/'));
		$jobs_done = 0;
		$this->consoleLog('Started batch');

		foreach ($jobs as $job) {

			$was_run = $this->runJob($job['jobID']);

			if ($was_run) {
				$jobs_done++;
			}
		}

		$this->consoleLog('Ended batch ('. $jobs_done .' '. ($jobs_done == 1 ? 'job' : 'jobs') .' executed)');
	}

	public function listen($params = []) {
		// Continually run all jobs every X seconds
		$interval = 60;
		if ($params['interval']) {
			$interval = $params['interval'];
		}

		do {
			$this->run($params);
			$this->consoleLog('Sleeping '. $interval .' secs');

			\Yii::$app->{$this->db}->close();
			sleep($interval);
			\Yii::$app->{$this->db}->open();
		} while (true);
	}

	public function consoleLog($msg) {
		echo date('Y-m-d H:i:s') .'  '. $msg . PHP_EOL;
	}

	// -------------


	/**
	 * Add a job to the job queue
	 *
	 * @param array $params : Associative array with these keys:
	 *   - `queueID` (opt.) : name of queue to add the job to (if using multiple queues)
	 *   - `command` (req.) : command to execute. Usually in the format of {"route":"image/gen-thumbnail", "id":8256, "dest":"/storage/"}
	 *     - non-strings are acceptable
	 *   - `replaceID` (opt.) : ID of the job which will cause a later job with the same ID to replace this job if it hasn't started yet
	 *     (therefore once a job is started this ID is removed)
	 *   - `priority` (opt.) : set a custom priority, from -128 to 127 (default is 0)
	 *   - `atCommand` (opt.) : the command for the Linux `at` command to execute to get this job done
	 *     - use placeholder {{jobID}} to have the ID of the created job inserted into the command
	 *     - single-quotes (') not allowed in the command and will be removed (no escaping is currently done)
	 *     - www-data must be allowed to use `at` command:
	 *       http://www.digitalwhores.net/linux/you-do-not-have-permission-to-use-at/
	 *         - remove www-data from /etc/at.deny
	 *       https://serverfault.com/questions/556332/why-is-www-data-ubuntu-apache-user-listed-in-the-etc-at-deny-file
	 *   - `niceness` (number) (opt.) : add a `nice` value (process priority) to the job executed by `at` (only applicable when atCommand is used)
	 *     - values can range from -20 to 19 (higher means lower priority (= nicer to the system resources))
	 *     - root privileges is required to use values below 0.
	 *     - default is 0
	 *   - `delaySecs` (opt.) : number of seconds to delay the job (otherwise executed at next loop of the service) (only applicable when atCommand is used)
	 *   - `touchFile` (opt.) : path to file to touch in order to trigger an incron job that has been setup (requires `incron` to have been installed, running and configured with the command to run)
	 *     - example incron job: `/var/app/current/runtime/WS-JobQueue-trigger  IN_ATTRIB  bash /.../scriptfile.sh`
	 *     - example script file (remember execute permissions on it for the executing user):
	 *         #!/bin/bash
	 *         rm -f /var/app/current/runtime/WS-JobQueue-incron-inprogress.tmp
	 *         nice -19 php /var/app/current/yii enrich/process-job-queue >/dev/null 2>&1
	 *   - `noConcurrentExecution` (opt.) : set to true to not set up multiple concurrent `at` jobs or touch a file if a job execution from a previous touch has not yet completed
	 *     - can be used to reduce the number of commands run, but then each command must execute all pending jobs in the queue
	 * @return void
	 */
	public function push($params = []) {
		if (!$params['command']) {
			new \winternet\yii2\UserException('Command for adding JobQueue is not specified.');
		}

		$bindings = [];

		if ($params['replaceID']) {
			$bindings['replace'] = $params['replaceID'];
		} else {
			$bindings['replace'] = null;
		}

		$bindings['command'] = json_encode($params['command']);

		if (is_numeric($params['priority'])) {
			$bindings['priority'] = $params['priority'];
		} else {
			$bindings['priority'] = 10;
		}

		if (is_numeric($params['delaySecs'])) {
			$bindings['delay'] = time() + $params['delaySecs'];
		} else {
			$bindings['delay'] = null;
		}

		$bindings['queue'] = $this->queueID;

		$rowsaffected = \Yii::$app->{$this->db}->createCommand("REPLACE INTO ". ($this->dbName ? '`'. $this->dbName .'`.' : '') ."system_job_queue SET job_queueID = :queue, job_replaceID = :replace, job_command = :command, job_priority = :priority, job_postpone_time = :delay, job_time_added = UTC_TIMESTAMP()", $bindings)->execute();
		$jobID = \Yii::$app->{$this->db}->getLastInsertID();

		if ($params['atCommand'] && stripos(PHP_OS, 'Linux') !== false) {
			$skip_at = false;

			$cmd = str_replace('{{jobID}}', $jobID, $params['atCommand']);

			if ($params['niceness']) {
				$cmd = 'nice -'. $params['niceness'] .' '. $cmd;
			}

			if ($params['noConcurrentExecution']) {
				$indic_file = \Yii::getAlias('@app/runtime/WS-JobQueue-at-inprogress.tmp');

				// Cancel setting at command if one is already pending
				if (file_exists($indic_file)) {
					$skip_at = true;
				} else {
					// Create the file
					touch($indic_file);

					// Have it removed just before the command runs
					$cmd = 'rm -f '. str_replace(' ', "\\ ", $indic_file) .' && '. $cmd;
				}
			}

			if (!$skip_at) {
				$whole_minutes = $remaining_seconds = null;
				if (is_numeric($params['delaySecs'])) {
					if ($params['delaySecs'] >= 60) {
						$whole_minutes = floor($params['delaySecs'] / 60) + 1;  //have to add 1 minute because if current time is 14:05:43 and we say "now + 1 min" it will execute at 14:06:00 (because the current "minute" we are in is included). Without adding one the job will be executed SOONER than one 1 minute and then job_postpone_time might still be in the future and won't be run when the job executes!
						$remaining_seconds = $params['delaySecs'] - ($whole_minutes * 60);
					} else {
						$remaining_seconds = $params['delaySecs'];
					}
					if ($remaining_seconds > 0) {
						$cmd = 'sleep '. $remaining_seconds .' && '. $cmd;
					}
				}
				$cmd = "echo '". str_replace("'", '', $cmd) ."' | at now". ($whole_minutes && $params['delaySecs'] >= 60 ? ' + '. $whole_minutes .' min' : '');

				$output = []; $exitcode = 0;
				exec($cmd, $output, $exitcode);

				if ($exitcode != 0) {
					new \winternet\yii2\UserException('Failed to execute at command in JobQueue.', ['Exit code' => $exitcode, 'Output' => implode('', $output) ]);
				}
			}
		}

		if ($params['touchFile']) {
			$folder = dirname($params['touchFile']);
			$filename = basename($params['touchFile']);

			if (!file_exists($folder)) {
				new \winternet\yii2\UserException('Folder to touch file in from within JobQueue does not exist.', ['Folder' => $folder]);
			}
			if (!preg_match("/^[a-z0-9_\\-\\.]+$/i", $filename)) {
				new \winternet\yii2\UserException('File name touch in JobQueue does not exist.', ['Filename' => $filename]);
			}

			$skip_touch = false;
			if ($params['noConcurrentExecution']) {
				$indic_file = \Yii::getAlias('@app/runtime/WS-JobQueue-incron-inprogress.tmp');

				// Cancel setting at command if one is already pending
				if (file_exists($indic_file)) {
					$skip_touch = true;
				} else {
					// Create the file
					touch($indic_file);
				}
			}

			if (!$skip_touch) {
				if (!touch($params['touchFile'])) {
					new \winternet\yii2\UserException('Failed to touch file in JobQueue.', ['File' => $params['touchFile'] ]);
				}
			}
		}
	}
}
