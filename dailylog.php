<?php
	//This page sits behind the login page index.html
	error_reporting(0);
	//echo 'dailylog.php';
	session_start();
	try {
		require_once('productionlogin.php');
		$db = new PDO($dsn,$db_username,$db_password);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		//echo 'Debug: No problem logging in to db<br>';
		//echo 'Debug: $_SESSION[\'loggedin\'] == ' . $_SESSION['loggedin'] . '<br>';
	}
	catch (PDOException $e) {
		printf("We had a problem connecting: %s\n", $e->getMessage());
		exit();
	}//end catch

	if ($_SESSION['loggedin'] == 0) {
		//echo 'Debug: $_SESSSION[\'loggedin\'] is 0<br>';
		$username = $_POST['username'];
		$password = $_POST['password'];
	
		if (!$username || !$password) { //no username or password
				echo 'You didn\'t enter a username and/or password<br>';
				echo '<a href="index.html">Return to login</a>';
				exit();
			}
			
		$username = $_POST['username'];
		$pwsha1 = sha1($_POST['password']);
		
		$query = "SELECT username FROM userlogins WHERE password = '$pwsha1'";
		$result = $db->query($query);
		$row = $result->fetch(PDO::FETCH_ASSOC);
		
		if ($row["username"] == $username) {
			$_SESSION['loggedin'] = 1;
			//echo 'Debug: Successful login, $_SESSION[\'loggedin\'] == 1';
		}
			
		else {
		echo 'Invalid username or password';
		echo '<a href="userlogin.html">Return to login</a>';
		exit();
		}
		
		
	}
	if ($_SESSION['loggedin'] == 1) {
	
      //start postback check, input validation, and insert attempt
		
      if (isset($_POST['submit'])) {
         $_POST['specificskills'] = addslashes($_POST['specificskills']);
         $_POST['successes'] = addslashes($_POST['successes']);
         $_POST['challenges'] = addslashes($_POST['challenges']);
         $_POST['recommendations'] = addslashes($_POST['recommendations']);
		
         //validate input fields
         if (!$_POST['date'] || !$_POST['staff']  || !$_POST['activity'] || !$_POST['participant'] || !$_POST['specificskills']) {
				echo 'You must enter at least a date, staff, activity, and participant';
         }   
         //input validated, proceed with query
         else {
            try {
					$dailylogstmt = $db->prepare("INSERT INTO asc.daily_log (date, specificskills, successes, challenges, recommendations)
												VALUES (:date, :specificskills, :successes, :challenges, :recommendations)");
					$dailylogstmt->execute(array(":date" => "$_POST[date]",
															":specificskills" => "$_POST[specificskills]",
															":successes" => "$_POST[successes]",
															":challenges" => "$_POST[challenges]",
															":recommendations" => "$_POST[recommendations]"));
					$lastidDailyLog = $db->lastInsertId(); //This is MySql specific; grabs the last iddailylog value for use in next query
				
					//insert into daily_activity all checked activities
					$activitystmt = $db->prepare("INSERT INTO asc.daily_activity (iddailylog,idactivity) VALUES ($lastidDailyLog, :item)");
					foreach ($_POST['activity'] as $item){               
						$activitystmt->execute(array(":item" => "$item"));
					}//end foreach
					
					//insert into daily_staff table all checked staff
					$dailystaffstmt = $db->prepare("INSERT INTO asc.daily_staff (iddailylog, idstaff) 
																VALUES ('$lastidDailyLog', :item)");
					foreach ($_POST['staff'] as $item) {
						$dailystaffstmt->execute(array(":item" => "$item"));
					}//end foreach
					
					//insert into daily_participant table all checked participants
					$dailyparticipantstmt = $db->prepare("INSERT INTO asc.daily_participant (iddailylog, idparticipant) VALUES ('$lastidDailyLog', :item)");
					foreach ($_POST['participant'] as $item) { 
						$dailyparticipantstmt->execute(array(":item" => "$item"));
					}//end foreach
					
					//clear all variables
					$_POST = array();
					echo 'Success! Thank you for logging<br>';
				}//end try
				catch (PDOException $e) {
					echo 'There was a problem logging<br>' . $e->getMessage();
					echo $lastidDailyLog;
					
				}//end catch 
         }//end else
      }//end postback/validation check and queries
	}
?>

<!DOCTYPE html>
<html>
   <head>
      <title>Staff Log</title>
      <link type="text/css" rel="stylesheet" href="CSS\StyleSheet.css"/>

	</head>
	
<body>   
	<a href="logout.php">Log out</a>
   <form action="dailylog.php" method="post">
      <div id="logo" style="background-color: #fff">
         <img src="img/ASClogo.png" alt="Austin Sunshine Camps" height="100px" width="200px"/>
      </div>
      <div>         
         <h2>Staff Log</h2>   
         <p>Date:</p>
         <input type="date" required size="" name="date" />
         <br />
   
         <p>Staff:</p>         
            <?php               
               $result = $db->query("SELECT * FROM staff WHERE active = 1");
               
               while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
						if (in_array($row["idstaff"], $_POST['staff']))//checks boxes if they were checked during last submission attempt
							$checked = "checked";
						else $checked = "";
						
                  printf('<input type="checkbox" name="staff[]" %s value="%s">%s </input>', 
							$checked, $row["idstaff"], $row["firstname"]);                             
               }
            ?>
         <br/>
			
			<p>Participants</p>
				<table>
				<?php               
               $result = $db->query("SELECT * FROM participant WHERE active = 1");
               $columncount = 1;
					
               while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
						//re-check checkboxes from last submission attempt
						if (in_array($row["idparticipant"],$_POST['participant']))
							$pchecked = "checked";
						else
							$pchecked = "";
						
						//after every 5th participant, create a new row
						if ($columncount % 6 == 0) echo '<tr>'; 
                  printf('<td><input type="checkbox" %s name="participant[]" value="%s">%s </input></td>', $pchecked, $row["idparticipant"], $row["firstname"]);
						if ($columncount % 6 == 0) echo '</tr>';
						$columncount += 1;						
               }
            ?>
				</table>
         <br/>

         <p>Activity:</p>
            <?php
               $result = $db->query("SELECT * FROM activity");
               
               while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
						//re-checks boxes if they were checked during last submission attempt
						if (in_array($row["idactivity"], $_POST['activity']))
							$checked = "checked";
						else $checked = "";
                  printf('<input type="checkbox" %s name="activity[]" value="%s">%s </input>', 
									$checked, $row["idactivity"], $row["activityname"]);   
               }
            ?>
         <br/>

         <p>Specific skills:</p>      
         <textarea class='inputbox' name="specificskills" required><?php echo $_POST['specificskills'];?></textarea>
         <br/>

         <p>Challenges encountered:</p>
         <textarea class='inputbox' name="challenges"><?php echo $_POST['challenges'];?></textarea>
         <br/>

         <p>Successes:</p>
         <textarea class='inputbox' name="successes"><?php echo $_POST['successes'];?></textarea>
         <br/>

         <p>Recommendations:</p>
         <textarea class='inputbox' name="recommendations"><?php echo $_POST['recommendations'];?></textarea>
         <br/>

         <input type="submit" value="Submit Log" name="submit"/>
			
         <br/>
      </div>
   </form>  
</body>
</html>
