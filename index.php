<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "config.php";
require_once "backend.php";

// authentication
if (PASSWORD != "") {

    // handle login form
    if (isset($_POST["login"]) && !empty($_POST["pass"])) {
        if (passwordValid($_POST["pass"])) {
            startSession();

            // for good measure
            vacuumExpiredSessions();
        } else {

            // a sleep a day keeps the bad guys at bay
            sleep(2);
        }
        header("Location: index.php");
        exit;
    }

    // show login form if session invalid
    // TODO make this prettier (but that goes for the entire app)
    if (!sessionValid()) {
        ?>
        <form action="index.php" method="post">
            <input type="password" name="pass" placeholder="Password" autofocus>
            <input type="submit" class="submit" name="login">
        </form>
        <?php
        exit;
    }

    // handle logout
    if (isset($_GET["logout"])) {
        killSession();

        header("Location: index.php");
        exit;
    }
}

// danger zone
if (isset($_GET["reset"])) {
	if (!empty($_POST["really"]) && $_POST["really"] == "yes") {
		resetDatabase();
		echo "done";
	} else {
		?>
		<form action="index.php?reset" method="POST">
			<input type="text" name="really" autofocus placeholder="yes/no">
			<input type="submit">
		</form>
		<?php
	}
	exit;
}

// add/remove
if (isset($_POST["add_weight"]) && !empty($_POST["weight"])) {
	$weight = $_POST["weight"];
	$weight = str_replace(",", ".", $weight);
	$weight = floatval($weight);
	addWeight($weight);

	$period = $_POST["period"];
	header("Location: index.php?period=" . $period);
	exit;
} else if (isset($_POST["remove_most_recent"])) {
	removeMostRecentWeight();

	$period = $_POST["period"];
	header("Location: index.php?period=" . $period);
	exit;
}

// period
if (empty($_GET["period"])) {
	header("Location: index.php?period=month");
	exit;
}
$period = $_GET["period"];
if ($period == "week") {
	$start = strtotime("-1 week");
} else if ($period == "month") {
	$start = strtotime("-1 month");
} else if ($period == "year") {
	$start = strtotime("-1 year");
} else if ($period == "all") {
	$start = 0;
} else {
	header("Location: index.php?period=month");
	exit;
}

$weights = getWeights($start);

?>

<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="shortcut icon" href="favicon.png">
		<link rel="apple-touch-icon" href="favicon.png">
		<title><?php echo getMostRecentWeight() . " kg" ?> - Leichter</title>
		<style>
			* {
				font-weight: normal;
				margin: 0;
				padding: 0;
				list-style-type: none;
				box-sizing: border-box;
			}
			body {
				font-size: 16px;
				overflow: hidden;
				font-family: Helvetica, sans-serif;
			}
			form {
				position: absolute;
				top: 2em;
				left: 5em;
				background-color: rgba(0,0,0,0.1);
				padding: 1em;
				text-align: center;
			}
			select,
			input {
				border: 1px solid gray;
				font: inherit;
				outline: none;
				padding: .5em;
				border-radius: 0;
			}
			select {
				width: 6.5em;
				margin-bottom: .33em;
				background-color: white;
			}
			input[type="number"] {
				width: 3.33em;
				-moz-appearance: textfield;
			}
			form ::-webkit-outer-spin-button,
			input[type="number"]::-webkit-inner-spin-button {
				-webkit-appearance: none;
			}
			input[name="add_weight"] {
				background-color: lightgray;
			}
			input[name="remove_most_recent"] {
				border: none;
				background: none;
				padding: 0;
				color: gray;
				font-size: .7em;
				text-decoration: underline;
				cursor: pointer;
			}
			input[name="remove_most_recent"]:hover {
				text-decoration: none;
			}
			p {
				position: absolute;
				width: 100%;
				top: 45%;
				color: gray;
				text-align: center;
			}
			div {
				margin: 1em;
			}
		</style>
	</head>
	<body>
		<form action="index.php" method="post">
			<select name="period" id="period">
				<option value="week" <?php if ($period == "week") echo "selected"; ?>>Week</option>
				<option value="month" <?php if ($period == "month") echo "selected"; ?>>Month</option>
				<option value="year" <?php if ($period == "year") echo "selected"; ?>>Year</option>
				<option value="all" <?php if ($period == "all") echo "selected"; ?>>All</option>
			</select><br>
			<input type="number" name="weight" step="0.1" autofocus placeholder="kg">
			<input type="submit" name="add_weight" value="Add"><br>
			<input type="submit" name="remove_most_recent" value="remove most recent">
		</form>
		<?php if (sizeof($weights) == 0) { ?>
			<p>No data available.</p>
		<?php } else if (sizeof($weights) == 1) { ?>
			<p>
				Found only one data point <?php if ($period != "all") echo "for this $period"; ?>:<br>
				<?php echo $weights[0]["weight"]; ?> kg on <?php echo date("Y-m-d \a\\t H:i:s", $weights[0]["time"]); ?>.
			</p>
		<?php } else { ?>
			<div>
				<canvas id="myChart"></canvas>
			</div>
			<script src="lib/jquery.min.js"></script>
			<script src="lib/Chart.Core.min.js"></script>
			<script src="lib/Chart.Scatter.min.js"></script>
			<script>
				// set chart dimensions
				var w = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);
				var h = Math.max(document.documentElement.clientHeight, window.innerHeight || 0);
				document.getElementById("myChart").width = w / 5;
				document.getElementById("myChart").height = h / 5;

				// draw chart
				Chart.defaults.global.responsive = true;
				Chart.defaults.global.animation = false;
				$(function () {
					var data = [
						{
							label: 'smoothed weight',
							strokeColor: '#A7A7D9',
							pointColor: 'transparent',
							pointStrokeColor: 'transparent',
							data: <?php echo formatWeights(smoothWeights($weights)); ?>
						},
						{
							label: 'weight',
							strokeColor: '#A31515',
							data: <?php echo formatWeights($weights); ?>
						}];

					var ctx = document.getElementById("myChart").getContext("2d");
					var myChart = new Chart(ctx).Scatter(data, {
						bezierCurve: false,
						showTooltips: true,
						scaleShowHorizontalLines: true,
						scaleShowLabels: true,
						scaleType: "date",
						scaleLabel: "<%=value%> kg",
						scaleOverride: true,
						<?php $chartRange = getChartRange($weights); ?>
						scaleSteps: <?php echo $chartRange["steps"]; ?>,
						scaleStepWidth: <?php echo $chartRange["stepWidth"]; ?>,
						scaleStartValue: <?php echo $chartRange["startValue"]; ?>
					});
				});
			</script>
		<?php } ?>
		<script>
			// update time period for chart
			period.onchange = function() {
				window.location.href = "index.php?period=" + this.value;
			}
		</script>
	</body>
</html>
