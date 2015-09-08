<?php

require_once "backend.php";

if (isset($_POST["weight"]) && !empty($_POST["weight"])) {
	$weight = $_POST["weight"];
	$weight = str_replace(",", ".", $weight);
	$weight = floatval($weight);
	addWeight($weight);

	header("Location: index.php");
} else if (isset($_GET["action"]) && $_GET["action"] == "removeMostRecentWeight") {
	removeMostRecentWeight();

	header("Location: index.php");
}

$weights = getWeights();

?>

<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Leichter</title>
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
				margin: 1em;
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
			input {
				border: 1px solid gray;
				font: inherit;
				outline: none;
				padding: .5em;
			}
			a {
				color: gray;
				font-size: .7em;
			}
			input[type="text"] {
				width: 3.33em;
			}
			input[type="submit"] {
				background-color: lightgray;
			}
		</style>
	</head>
	<body>
		<form action="index.php" method="post">
			<input type="text" name="weight" autofocus placeholder="kg">
			<input type="submit" value="Add"><br>
			<a href="index.php?action=removeMostRecentWeight">remove most recent</a>
		</form>
		<canvas id="myChart"></canvas>

		<script src="lib/jquery.min.js"></script>
		<script src="lib/Chart.Core.min.js"></script>
		<script src="lib/Chart.Scatter.min.js"></script>
		<script>
			var w = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);
			var h = Math.max(document.documentElement.clientHeight, window.innerHeight || 0);
			document.getElementById("myChart").width = w / 5;
			document.getElementById("myChart").height = h / 5;

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
	</body>
</html>
