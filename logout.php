<?php
	session_start();
	unset($_SESSION['loggedin']);
	echo 'You are logged out<br>';
	echo '<a href="index.html">Return to homepage</a>';
?>
