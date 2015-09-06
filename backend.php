<?php

try {
	$db = new PDO("sqlite:main.sqlite");
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
	echo "Database connection failed: " . $e->getMessage();
	exit;
}

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

function resetDatabase() {
	query("DROP TABLE IF EXISTS weight");
	query("CREATE TABLE weight (id INTEGER NOT NULL, time INTEGER, weight DECIMAL(15,1), PRIMARY KEY (id))");
}

function addWeight($weight) {
	$time = time();
	$weight = sqlite_escape_string($weight);
	query("INSERT INTO weight (time, weight) VALUES ($time, $weight)");
}

function removeMostRecentWeight() {
	query("DELETE FROM weight WHERE id = (SELECT MAX(id) FROM weight)");
}

function getWeights() {
	$result = query("SELECT * FROM weight ORDER BY id ASC");
	$weights = $result->fetchAll();
	return $weights;
}

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
