<?php

require_once "config.php";

try {
	$db = new PDO("sqlite:" . DB_PATH);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
	echo "Database connection failed: " . $e->getMessage();
	exit;
}

/////////////
// HELPERS //
/////////////

function query($query) {
	global $db;

	try {
		$result = $db->query($query);
	} catch (PDOException $e) {
		echo "Database query failed: " . $e->getMessage();
		exit;
	}
	return $result;
}

function escape($value) {
	global $db;

	return $db->quote($value);
}

//////////
// AUTH //
//////////

function generateSessionUID() {

    // throw some entropy in there
    return md5(PASSWORD . microtime() . $_SERVER["HTTP_USER_AGENT"]);
}

function passwordValid($pass) {
    return $pass == PASSWORD;
}

function startSession() {
    $uid = generateSessionUID();
    $expires = time() + (86400 * SESSION_LENGTH);
    $useragent = escape($_SERVER["HTTP_USER_AGENT"]);  // helps discern sessions when going through them manually, if need be

    query("INSERT INTO session (uid, expires, useragent) VALUES (" . escape($uid) . ", $expires, $useragent)");
    setcookie("sessionuid", $uid, $expires, "/");
}

function killSession() {
    if (isset($_COOKIE["sessionuid"])) {
    	$uid = escape($_COOKIE["sessionuid"]);
        query("DELETE FROM session WHERE uid = $uid");
    }
}

function sessionValid() {
    if (isset($_COOKIE["sessionuid"])) {
    	$uid = escape($_COOKIE["sessionuid"]);
        $query = query("SELECT expires FROM session WHERE uid = $uid");
        $result = $query->fetch();
        $expires = $result["expires"];
        return !empty($expires) && $expires >= time();
    }
    return false;
}

function vacuumExpiredSessions() {
    query("DELETE FROM session WHERE expires < " . time());
}

/////////
// ADD //
/////////

function addWeight($weight) {

	// allow new weight entry without a dot if within 10 kg of most recent weight
	$result = query("SELECT * FROM weight WHERE id = (SELECT MAX(id) FROM weight)");
	$weights = $result->fetch();
	if (abs($weight - 10 * $weights["weight"]) < 100) {
		$weight = $weight / 10;
	}

	$time = time();
	$escapedWeight = escape($weight);
	query("INSERT INTO weight (time, weight) VALUES ($time, $escapedWeight)");

	return $weight;
}

function removeMostRecentWeight() {
	query("DELETE FROM weight WHERE id = (SELECT MAX(id) FROM weight)");
}

//////////
// MISC //
//////////

function getMostRecentWeight() {
	$result = query("SELECT * FROM weight WHERE id = (SELECT MAX(id) FROM weight)");
	$weights = $result->fetch();
	return $weights["weight"];
}

function getWeights($start = 0) {
	$result = query("SELECT * FROM weight WHERE time >= $start ORDER BY id ASC");
	$weights = $result->fetchAll();
	return $weights;
}

///////////
// CHART //
///////////

function formatWeights($weights) {
	$formatted = "[";
	foreach ($weights as $weight) {
		$date = date("Y-m-d\TH:i:s", $weight["time"]);
		$weight = $weight["weight"];

		$formatted .= "{x: new Date('$date'), y: $weight},";
	}
	$formatted .= "]";
	return $formatted;
}

function getChartRange($weights, $steps = 25) {
	$min = $weights[0]["weight"];
	$max = $weights[0]["weight"];
	foreach ($weights as $weight) {
		if ($weight["weight"] < $min) {
			$min = $weight["weight"];
		} else if ($weight["weight"] > $max) {
			$max = $weight["weight"];
		}
	}

	$startValue = 2.5 * floor($min / 2.5);
	$stepWidth = (2.5 * ceil($max / 2.5) - $startValue) / $steps;

	return array("steps" => $steps, "stepWidth" => $stepWidth, "startValue" => $startValue);
}

/////////////////
// DANGER ZONE //
/////////////////

function resetDatabase() {
	query("DROP TABLE IF EXISTS weight");
	query("CREATE TABLE weight (id INTEGER NOT NULL, time INTEGER, weight DECIMAL(15,1), PRIMARY KEY (id))");
	query("CREATE TABLE session (uid varchar NOT NULL, expires INTEGER, useragent TEXT, PRIMARY KEY (uid))");
}
