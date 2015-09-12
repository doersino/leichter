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

function getWeights($start = 0) {
	$result = query("SELECT * FROM weight WHERE time >= $start ORDER BY id ASC");
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

	$startValue = 5 * floor($min / 5);
	$stepWidth = (5 * ceil($max / 5) - $startValue) / $steps;

	return array("steps" => $steps, "stepWidth" => $stepWidth, "startValue" => $startValue);
}

function gauss($x, $stdev) {
	return exp(-(pow($x, 2) / (2 * pow($stdev, 2))));
}

function smoothWeights($weights, $numSamples = 100) {
	// mean
	$mean = 0;
	foreach ($weights as $weight) {
		$mean += $weight["time"];
	}
	$mean /= sizeof($weights);

	// standard deviation
	$stdev = 0;
	foreach ($weights as $weight) {
		$stdev += pow($weight["time"] - $mean, 2);
	}
	$stdev /= sizeof($weights);
	$stdev = sqrt($stdev);
	$stdev /= 5; // TODO make this dependent on the available time range, maybe don't use stdev at all

	// sample points at which to evaluate the gauss function
	$samples = array();
	$timeRange = $weights[sizeof($weights)-1]["time"] - $weights[0]["time"];
	for ($i = 0; $i < $numSamples; $i++) {
		$offset = intval(($i / ($numSamples - 1)) * $timeRange);
		$samples[] = $weights[0]["time"] + $offset;
	}

	// compute smooth weights by weighting all weights at each sample point with the gauss function
	$smoothWeights = array();
	foreach ($samples as $sample) {
		$weightedWeight = 0;
		$normalizationFactor = 0;
		for ($j = 0; $j < sizeof($weights); $j++) {
			$timeDifference = $sample - $weights[$j]["time"];
			$weightedWeight += $weights[$j]["weight"] * gauss($timeDifference, $stdev);
			$normalizationFactor += gauss($timeDifference, $stdev);
		}
		$weightedWeight /= $normalizationFactor;
		$smoothWeights[] = array("time" => $sample, "weight" => $weightedWeight);
	}
	return $smoothWeights;
}
