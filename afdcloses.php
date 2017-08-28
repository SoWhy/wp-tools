<?php

/*
AFDCloses.php - Script to analyze how users have closed Articles for deletion discussions on en-wiki
*/

if (
($_GET['user']) && 
(strlen($_GET['user']) <= 100) && 
(
  ($_GET['limit'] == "") || 
  (($_GET['limit'] == "number") && (is_int((int)$_GET['limitnum']))) || 
  (
    ($_GET['limit'] == "date") && 
    (is_int((int)(str_replace("-", "", $_GET['from'])))) && 
    (is_int((int)(str_replace("-", "", $_GET['to'])))) && 
    ((str_replace("-". "", $_GET['from'])) <= (str_replace("-". "", $_GET['to'])))
   )
  )
) {

if (($_GET['limit'] == "number") && (!$_GET['limitnum'])) {
  $_GET['limitnum'] = 100;
}

$from = str_replace('-', '', $_GET['from']) . "000000";
$to = str_replace("-", "", $_GET['to']) . "235959";

//create empty array for outcomes
$outcomes = array(
'Delete' => 0,
'Speedy delete' => 0,
'Soft delete' => 0,
'Redirect' => 0,
'Merge' => 0,
'Rename/Move' => 0,
'No consensus' => 0,
'Draftify/Userfy' => 0,
'Keep' => 0,
'Speedy keep' => 0,
'Other/Cannot parse' => 0
); 


//sanitize username
$username = htmlspecialchars( strip_tags($_GET['user']) );
$username = str_replace("_", " ", $username);

// DB connection
$ts_pw = posix_getpwuid(posix_getuid());
$ts_mycnf = parse_ini_file($ts_pw['dir'] . "/replica.my.cnf");

$mysqli = new mysqli('enwiki.labsdb', $ts_mycnf['user'], $ts_mycnf['password'], 'enwiki_p');

// Check if user actually exists

$sql_c = 'SELECT * FROM user WHERE user_name = "' . $username . '" LIMIT 1';

if (!$result_u = $mysqli->query($sql_c)) {
    echo "<h1>Sorry, the website is experiencing problems.</h1>\n";
    echo "Error: Failed to make a MySQL connection, here is why: \n";
    echo "Errno: " . $mysqli->connect_errno . "\n";
    echo "Error: " . $mysqli->connect_error . "\n";
    exit;
}

if ($result_u->num_rows === 0) {
    echo '<h1>No such user!</h1>
    <a href="afdcloses.php">&lt;&lt;&lt; Try again</a>';
    exit;
}


//Actual query
$sql = 'SELECT page_title, revs.rev_timestamp, revs.rev_comment
FROM revision_userindex AS revs
LEFT JOIN revision_userindex AS parentrevs ON (revs.rev_parent_id= parentrevs.rev_id)
JOIN page ON revs.rev_page = page_id
LEFT JOIN user AS usr ON revs.rev_user = usr.user_id
WHERE usr.user_name = "' . $username . '"
AND page_namespace = 4
AND page_title RLIKE "Articles_for_deletion"
AND ((revs.rev_comment RLIKE "Closed as" AND revs.rev_comment RLIKE "XFDcloser") OR revs.rev_comment RLIKE "result was")';

// Add limit if requested
if ($_GET['limit'] == "number") {
$sql .= '
ORDER BY revs.rev_timestamp DESC
LIMIT ' . (int)$_GET['limitnum'];
}
elseif ($_GET['limit'] == "date") {
$sql .= '
AND revs.rev_timestamp >= ' . $from . '
AND revs.rev_timestamp <= ' . $to . '
ORDER BY revs.rev_timestamp DESC
';
}
else {
$sql .= '
ORDER BY revs.rev_timestamp DESC';
}

$header .= "<b>User:</b> $username<br/>";

$output .= '<table id="results" class="tablesorter">
<thead>
<tr>
<th>Date</th>
<th>AFD</th>
<th>Result</th>
</tr>
</thead>';

// Do query
if (!$result = $mysqli->query($sql)) {
    echo "<h1>Sorry, the website is experiencing problems.</h1>\n";
    echo "Error: Failed to make a MySQL connection, here is why: \n";
    echo "Errno: " . $mysqli->connect_errno . "\n";
    echo "Error: " . $mysqli->connect_error . "\n";
    exit;
}

// Parse results
while ($entry = $result->fetch_assoc()) {


	$lowercomment = strtolower($entry['rev_comment']);

		$output .= "<tr class=\"afd\"><td>" . date("d M Y", strtotime($entry['rev_timestamp'])) . "</td><td><a href=\"https://en.wikipedia.org/wiki/Wikipedia:" . $entry['page_title'] . "\" target =\"new\">" . str_replace("_", " ", $entry['page_title']) . "</a></td>";

		if (preg_match("/soft delete/", $lowercomment)) {

			$outcomes['Soft delete']++;

			$output .= '<td class="del">Soft delete</td>';

		}
		elseif (preg_match("/speedy delete/", $lowercomment)) {

			$outcomes['Speedy delete']++;

			$output .= '<td class="del">Speedy delete</td>';

		}
		elseif (preg_match("/delete/", $lowercomment)) {

			$outcomes['Delete']++;

			$output .= '<td class="del">Delete</td>';

		}
		elseif (preg_match("/speedy keep/", $lowercomment)) {

			$outcomes['Speedy keep']++;

			$output .= '<td class="keep">Speedy keep</td>';

		}
		elseif (preg_match("/keep/", $lowercomment)) {

			$outcomes['Keep']++;

			$output .= '<td class="keep">Keep</td>';

		}
		elseif (preg_match("/edirect/", $lowercomment)) {

			$outcomes['Redirect']++;

			$output .= '<td class="rm">Redirect</td>';

		}
		elseif (preg_match("/merge/", $lowercomment)) {

			$outcomes['Merge']++;

			$output .= '<td class="rm">Merge</td>';

		}		
		elseif (preg_match("/no consensus/", $lowercomment)) {

			$outcomes['No consensus']++;

			$output .= '<td class="nc">No consensus</td>';

		}	
		elseif (preg_match("/withdrawn/", $lowercomment)) {

			$outcomes['Speedy keep']++;

			$output .= '<td class="keep">Speedy keep</td>';

		}	
		elseif ((preg_match("/move/", $lowercomment)) || (preg_match("/rename/", $lowercomment))) {

			$outcomes['Rename/Move']++;

			$output .= '<td class="rm">Rename/Move</td>';

		}
		elseif ((preg_match("/draftify/", $lowercomment)) || (preg_match("/userfy/", $lowercomment))) {

			$outcomes['Draftify/Userfy']++;

			$output .= '<td class="rm">Draftify/Userfy</td>';

		}			
		else {

			$outcomes['Other/Cannot parse']++;

			$otherstuff[] = $lowercomment;

			$output .=  "<td>Other/Cannot parse</td>";

		}	

		$output .=  "</tr>\n";

}

$output .= "</table><br/>";

//Display info about number of closes
if (((int)$_GET['limitnum']) && (array_sum($outcomes) <= (int)$_GET['limitnum'])) {

  $header .= "Displaying the last <strong>" . array_sum($outcomes) . "</strong> AFDs closed by this user<br/>";

}
else {

  $header .= "Total AFDs closed by this user: <strong>" . array_sum($outcomes) . "</strong><br/>";

  if ($_GET['limit'] == "date") {
    
    $header .= "(Daterange analyzed: " . $_GET['from'] . " - " . $_GET['to'] . ")<br/>";
    
  }

}

}


else {
// Standard form
$output .= <<<HTML
<form action="afdcloses.php" method="get">
	<table border="0">
		<tr>
			<td><b>User:</b></td>
			<td colspan="2"><input type=text name="user" maxlength="85"></td>
		</tr>
		<tr>
			<td><b>Create pie chart?</b></td>
			<td colspan="2">
        <input type="radio" name="chart" value="yes" checked>Yes <input type="radio" name="chart" value="no">No<br></td>
		</tr>
		<tr>
      <td><b>Limit output?</b></td>
      <td colspan="2"><label><div style="width: 100%"><input type="radio" name="limit" value="" id="no" checked>No</b></div></label></td>
    </tr>
    <tr>
      <td></td>
      <td><input type="radio" id="number" name="limit" value="number"><label for="number">By number:</label></td>
      <td>
        <label for="number">Display the last <input name="limitnum" placeholder="100" type="text" size="7"> AFDs closed</label><br/>
      </td>
    </tr>
    <tr>
      <td></td>
      <td><input type="radio" name="limit" id="date" value="date"><label for="date">By daterange:</td>
      <td>
       <input type="text" id="from" name="from"><input type="text" id="to" name="to"></label>
      </td>
    </tr>
		<tr>
			<td align="right" colspan="3"><input type="submit" value="Get stats"></td>
		</tr>
	</table>
	</form>
	
	<h4>Notes:</h4>
	<ul>
    <li>This script only works with closes made by either <a href="https://en.wikipedia.org/wiki/User_talk:Evad37/XFDcloser.js" target="new">XFDCloser</a> or <a href="https://en.wikipedia.org/wiki/User:Mr.Z-man/closeAFD2.js" target="new">Mr.Z-man's closeAFD script</a>. It relies on parsing the edit summaries used by these scripts.</li>
    <li>The results will probably include false positives since I cannot figure out a sure way to identify reverted closes (checking for the last revision does not work since sometimes edits happen after the close, such as adding a rationale or removal of categories). Feel free to tell me if you think of a way (preferably in SQL form).</li>
  </ul>
HTML;

}

// Page output starts hereafter
?>

<html> 
<head>
  <meta charset="UTF-8">
<title>SoWhy's AFD analyzer (v0.03)</title>
<link rel="stylesheet" type="text/css" href="sowhy.css">

  <link rel="stylesheet" href="jquery-ui/jquery-ui.css">
  <link rel="stylesheet" href="jquery-ui/style.css">
  <script src="jquery-ui/jquery.js"></script>
  <script src="jquery-ui/jquery-ui.js"></script>
  <script src="jquery-ui/jquery.tablesorter.js"></script>
  <script src="jquery-ui/jquery.metadata.js"></script>
  <script>
  $( function() {
    var dateFormat = "mm/dd/yy",
      from = $( "#from" )
        .datepicker({
          changeMonth: true,
          changeYear: true,
          numberOfMonths: 1,
          maxDate: 0,
          dateFormat: "yy-mm-dd"
        })
        .on( "change", function() {
          to.datepicker( "option", "minDate", getDate( this ) );
        }),
      to = $( "#to" ).datepicker({
        changeMonth: true,
        changeYear: true,
        numberOfMonths: 1,
        maxDate: 0,
        dateFormat: "yy-mm-dd"
      })
      .on( "change", function() {
        from.datepicker( "option", "maxDate", getDate( this ) );
      });
 
    function getDate( element ) {
      var date;
      try {
        date = $.datepicker.parseDate( dateFormat, element.value );
      } catch( error ) {
        date = null;
      }
 
      return date;
    }
  } );
  
  $(document).ready(function(){
    $("form").submit(function(){
        $("input").each(function(index, obj){
            if($(obj).val() == "") {
                $(obj).remove();
            }
        });
    });
  });

  $(document).ready(function() 
    { 
        $("#results").tablesorter(); 
    } 
  ); 
    

  </script>
  
<?php
if ($outcomes && ($_GET['chart'] == "yes")) {
?>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">
      google.charts.load('current', {'packages':['corechart']});
      google.charts.setOnLoadCallback(drawChart);

      function drawChart() {

        var data = google.visualization.arrayToDataTable([
          ['AFD', 'Outcome'],
<?php
    foreach ($outcomes as $key => $value) {
    echo "['" . $key . "', " . $value . "],\n";
    }
    
    /*
    $varoutput .= "['Delete', " . $outcomes['Delete'] . "],\n";
    $varoutput .= "['Speedy delete', " . $outcomes['Speedy delete'] . "],\n";
    $varoutput .= "['Soft delete', " . $outcomes['Soft delete'] . "],\n";
    $varoutput .= "['Redirect', " . $outcomes['Redirect'] . "],\n";
    $varoutput .= "['Merge', " . $outcomes['Merge'] . "],\n";
    $varoutput .= "['Rename/Move', " . $outcomes['Rename/Move'] . "],\n";
    $varoutput .= "['No consensus', " . $outcomes['No consensus'] . "],\n";
    $varoutput .= "['Draftify/Userfy', " . $outcomes['Draftify/Userfy'] . "],\n";
    $varoutput .= "['Keep', " . $outcomes['Keep'] . "],\n";
    $varoutput .= "['Speedy keep', " . $outcomes['Speedy keep'] . "],\n";
    $varoutput .= "['Other/Cannot parse', " . $outcomes['Other/Cannot parse'] . "]\n";
    
    echo $varoutput;
    */
?>
    ]);

    var options = {
        colors: ['#c63d3d', '#ad1818','#c97c7c', '#FF9900', '#aaaa52', '#5858B0', '#3196b7', '#939393', '#2eaa33', '#156e19', '#8A4A8A'],
        pieSliceText: 'value',
        pieSliceBorderColor: 'black',
        legend: {position: 'labeled', textStyle:{color: '#555', fontSize: '0.9rem', bold: 1}},
        is3D: true,

    };

    var chart = new google.visualization.PieChart(document.getElementById('piechart'));
    chart.draw(data, options);
}
</script>
<?php
}
?>

</head>

<body>
<h3>SoWhy's script to analyze AFD closes</h3>
<h5>This tool attempts to parse AFDs closed by a certain user (if they used a script) and display them in a handy chart.</h5>
<?php 
if ($header) {
echo $header;
echo '<a href="afdcloses.php">&lt;&lt;&lt; Query another user</a><br/>';
}

if ($outcomes && ($_GET['chart'] == "yes")) {
echo '<div id="piechart" style="width: 900px; height: 500px;"></div>';
}

echo $output; 

?>
<div id="footer">A <a href="https://en.wikipedia.org/wiki/User:SoWhy" target="new"><span style="font-variant: small-caps"><span style="color: #7A2F2F">So</span><span style="color: #474F84">Why</span></span></a> script. Feedback welcome.<br/>Uses <a href="https://developers.google.com/chart/" target="new">Google chart tools</a> and <a href="https://jquery.com/" target="new">jQuery</a>. Source code available at <a href="https://github.com/SoWhy/wp-tools" target="new">GitHub</a>

</div>
</body>
</html>

<?php
function GetBetween($var1="",$var2="",$pool){
$temp1 = strpos($pool,$var1)+strlen($var1);
$result = substr($pool,$temp1,strlen($pool));
$dd=strpos($result,$var2);
if($dd == 0){
$dd = strlen($result);
}

return substr($result,0,$dd);
}
?>
