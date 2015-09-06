<?php

require_once "backend.php";

if ($_POST["really"] == "yes") {
	resetDatabase();
	echo "done";
} else {

?>

<form action="reset.php" method="POST">
	<input type="text" name="really" autofocus placeholder="yes/no">
	<input type="submit">
</form>

<?php

}
