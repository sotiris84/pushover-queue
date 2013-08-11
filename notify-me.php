<?php
	error_reporting(E_ALL);
	set_time_limit(0);
	ini_set('display_errors', 1);
	ini_set('memory_limit', -1);
	
	$GLOBALS['mysql_username'] = '';
	$GLOBALS['mysql_password'] = '';
	$GLOBALS['mysql_database'] = '';
	$GLOBALS['mysql_hostname'] = 'localhost';
	
	$GLOBALS['http_username'] = '';
	$GLOBALS['http_password'] = ''; // This should be a SHA256 hash.
	
	$GLOBALS['pushover_app_token'] = '';
	$GLOBALS['pushover_user_key'] = '';

	if (isset($_GET['action'])) {
		if ($_GET['action'] == 'add') {
			if (isset($_GET['title'], $_GET['message'], $_GET['priority'])) {
				writeQuery('INSERT INTO `notifications` VALUES (NULL, "'.mres($_GET['title']).'", "'.mres($_GET['message']).'", "'.mres($_GET['priority']).'", "'.time().'", 0, NULL, NULL, NULL, NULL)');
			}
		}
		else if ($_GET['action'] == 'add-site247') {
			if (isset($_GET['STATUS'], $_GET['MONITORNAME'])) {
				writeQuery('INSERT INTO `notifications` VALUES (NULL, "'.mres($_GET['MONITORNAME']).'", "'.mres($_GET['STATUS']).'", "-1", "'.time().'", 0, NULL, NULL, NULL, NULL)');
			}
		}
		else if ($_GET['action'] == 'add-sendgrid') {
			if (isset($_POST['subject'], $_POST['text'])) {
				writeQuery('INSERT INTO `notifications` VALUES (NULL, "'.mres($_POST['subject']).'", "'.mres($_POST['text']).'", "-1", "'.time().'", 0, NULL, NULL, NULL, NULL)');
			}
		}
		else if ($_GET['action'] == 'send') {
			if ($_SERVER['PHP_AUTH_USER'] == $GLOBALS['http_username'] && hash('sha256', $_SERVER['PHP_AUTH_PW']) == $GLOBALS['http_password']) {
				$notifications = fetchQuery('SELECT * FROM `notifications` WHERE `sent` = 0 AND (`sentHTTPStatus` NOT LIKE "4%" OR `sentHTTPStatus` IS NULL)');
				
				if (is_array($notifications)) {
					foreach ($notifications as $notification) {
						$ch = curl_init();
						curl_setopt_array($ch, array(
							CURLOPT_URL => 'https://api.pushover.net/1/messages.json',
							CURLOPT_RETURNTRANSFER => true,
							CURLOPT_POSTFIELDS => array(
								'token' => $GLOBALS['pushover_app_token'],
								'user' => $GLOBALS['pushover_user_key'],
								'title' => $notification['title'],
								'message' => $notification['message'],
								'priority' => $notification['priority'],
								'timestamp' => $notification['timestamp'],
							)
						));
						$response = curl_exec($ch);
						$httpStatus = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
					
						// Success!
						if ($httpStatus < 400) {
							$response = json_decode($response, true);
							
							writeQuery('UPDATE `notifications` SET `sent` = "1", `sentTimestamp` = "'.mres(time()).'", `sentStatus` = "'.mres($response['status']).'", `sentHTTPStatus` = "'.mres($httpStatus).'", `sentRequestID` = "'.mres($response['request']).'" WHERE `id` = "'.mres($notification['id']).'"');
						}
						// Problem with payload do not try again.
						else if ($httpStatus >= 400 && $httpStatus < 500) {
							writeQuery('UPDATE `notifications` SET `sent` = "0", `sentTimestamp` = "'.mres(time()).'", `sentHTTPStatus` = "'.mres($httpStatus).'" WHERE `id` = "'.mres($notification['id']).'"');
						}
						// Internal Error - Try again later.
						else if ($httpStatus >= 500) {
							writeQuery('UPDATE `notifications` SET `sent` = "0", `sentTimestamp` = "'.mres(time()).'", `sentHTTPStatus` = "'.mres($httpStatus).'" WHERE `id` = "'.mres($notification['id']).'"');
						}
						
						curl_close($ch);
					}
				}
			}
			else {
				header('WWW-Authenticate: Basic realm="Password Required"');
				header('HTTP/1.0 401 Unauthorized');
				exit();
			}
		}
	}
	
	function initDatabaseConnection() {
		$GLOBALS['db_link'] = new mysqli($GLOBALS['mysql_hostname'], $GLOBALS['mysql_username'], $GLOBALS['mysql_password'], $GLOBALS['mysql_database']);
	}
	
	function writeQuery($query) {
		if (!isset($GLOBALS['db_link'])) { initDatabaseConnection(); }
		if (preg_match('/INSERT/', $query)) {
			if ($GLOBALS['db_link']->query($query)) {
				return $GLOBALS['db_link']->insert_id;
			}
		}
		else {
			return $GLOBALS['db_link']->query($query);
		}
	}
	
	function fetchQuery($query) {	
		if (!isset($GLOBALS['db_link'])) { initDatabaseConnection(); }
		$results = $GLOBALS['db_link']->query($query);
		$table=false;
	
		if ($results !== false) {
			if ($query == 'SHOW TABLES') {
				while ($row = $results->fetch_row()) { $table[] = $row[0]; }
			}
			else {
				while ($row = $results->fetch_assoc()) { $table[] = $row; }
				if (is_array($table)) { if (preg_match('/LIMIT 1$/', $query)) { $table = $table[0]; } }
			}
		}
		
		$results->free();
		
		return $table;
	}
	
	function mres($val) {
		if (!isset($GLOBALS['db_link'])) { initDatabaseConnection(); }
		return $GLOBALS['db_link']->escape_string($val);
	}
?>
