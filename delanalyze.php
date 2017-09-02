<?php

/*

DelAnalyze.php - Script to analyze deletions

(C) 2017 Alessandro Fuschi (SoWhy) // sowhy@sowhy.de

Released under the MIT License: https://opensource.org/licenses/MIT

*/

$version = "0.03α (2017/09/02)";

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

//create empty arrays
$csdreasons = array(
'G1' => 0,
'G2' => 0,
'G3' => 0,
'G4' => 0,
'G5' => 0,
'G6' => 0,
'G7' => 0,
'G8' => 0,
'G9' => 0,
'G10' => 0,
'G11' => 0,
'G12' => 0,
'G13' => 0,

'A1' => 0,
'A2' => 0,
'A3' => 0,
'A4' => 0,
'A5' => 0,
'A6' => 0,
'A7' => 0,
'A8' => 0,
'A9' => 0,
'A10' => 0,
'A11' => 0,

'R1' => 0,
'R2' => 0,
'R3' => 0,

'F1' => 0,
'F2' => 0,
'F3' => 0,
'F4' => 0,
'F5' => 0,
'F6' => 0,
'F7' => 0,
'F8' => 0,
'F9' => 0,
'F10' => 0,
'F11' => 0,

'C1' => 0,
'C2' => 0,
'C3' => 0,

'U1' => 0,
'U2' => 0,
'U3' => 0,
'U4' => 0,
'U5' => 0,

'T1' => 0,
'T2' => 0,
'T3' => 0,

'P1' => 0,
'P2' => 0,

'X1' => 0,
'X2' => 0
); 

$delreasons = array(
'XFD' => 0,
'SD' => 0,
'PROD' => 0,
'BLPPROD' => 0,
'Other' => 0
); 

$xfd = array(
'AFD' => 0,
'RFD' => 0,
'TFD' => 0,
'FFD' => 0,
'CFD' => 0,
'MFD' => 0,
);

//sanitize username
$username = htmlspecialchars( strip_tags($_GET['user']) );
$username = str_replace("_", " ", $username);

// DB connection
$ts_pw = posix_getpwuid(posix_getuid());
$ts_mycnf = parse_ini_file($ts_pw['dir'] . "/replica.my.cnf");

$mysqli = new mysqli('enwiki.labsdb', $ts_mycnf['user'], $ts_mycnf['password'], 'enwiki_p');

// Check if user actually exists and if so, extract user id

$sql_c = 'SELECT user_id FROM user WHERE user_name = "' . $username . '" LIMIT 1';

if (!$result_u = $mysqli->query($sql_c)) {
    echo "<h1>Sorry, the website is experiencing problems.</h1>\n";
    echo "Error: Failed to make a MySQL connection, here is why: \n";
    echo "Errno: " . $mysqli->connect_errno . "\n";
    echo "Error: " . $mysqli->connect_error . "\n";
    exit;
}

if ($result_u->num_rows === 0) {
    echo '<h1>No such user!</h1>
    <a href="delanalyze.php">&lt;&lt;&lt; Try again</a>';
    exit;
}
else {
  $userid = $result_u->fetch_assoc()['user_id'];
  }


//Actual query - since we need all entries anyway, no point in sorting through them before
$sql = 'SELECT log_timestamp AS "timestamp", log_namespace AS "namespace", log_title AS "page", log_comment AS "comment"
 FROM logging_userindex
 WHERE log_action = "delete"
 AND log_user = ' . $userid . '';

// Add limit if requested
if ($_GET['limit'] == "number") {
  
  // Set a hard limit to avoid huge deletion logs from crashing the script
  if ((int)$_GET['limitnum'] > 100000) {
    $_GET['limitnum'] = 100000;
    }
    
$sql .= '
ORDER BY log_timestamp DESC
LIMIT ' . (int)$_GET['limitnum'];
}
elseif ($_GET['limit'] == "date") {
$sql .= '
AND log_timestamp >= ' . $from . '
AND log_timestamp <= ' . $to . '
ORDER BY log_timestamp DESC
';

}
else {
// Add a hard max limit to avoid huge deletion logs from crashing the script
$sql .= '
ORDER BY log_timestamp DESC
LIMIT 100000';
}

$header .= "<b>User:</b> $username<br/>";

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

$lowercomment = str_replace("_", " ", strtolower($entry['comment']));

  // Sort deletions by reason 
  
   if(preg_match("/multiple reasons/", $lowercomment)) {
    
    $delreasons['SD']++;

    preg_match_all("/wp:[a-z][0-9]{1,2}/", $lowercomment, $matches);
  
    foreach($matches[0] as $criterion) {

      $criterion = strtoupper(str_replace("wp:", "", $criterion));
           
      // Add criterion to array if correctly identified 
      if (array_key_exists("$criterion", $csdreasons)) {
    
        $csdreasons["$criterion"]++;
    
      }
    
    }
    
  } 
 
   elseif(preg_match("/[[wp:csd#[a-z][0-9]{1,2}/", $lowercomment)) {
    $delreasons['SD']++;

    $criterion = strtoupper(GetBetween("[[wp:csd#", "|", $lowercomment));
    
    // Add criterion to array if correctly identified 
    if (array_key_exists("$criterion", $csdreasons)) {
    
      $csdreasons["$criterion"]++;
    
    }
      
  } 
 
  elseif(preg_match("/[[wp:[a-z][0-9]{1,2}/", $lowercomment)) {
    $delreasons['SD']++;
    
    $criterion =  strtoupper(GetBetween("[[wp:", "|", $lowercomment));
    
    // Add criterion to array if correctly identified 
    if (array_key_exists("$criterion", $csdreasons)) {
    
      $csdreasons["$criterion"]++;
    
    }
    
  }
  
  elseif(preg_match("/wp:prod/", $lowercomment)) {
    $delreasons['PROD']++;
	}
   elseif(preg_match("/wp:blpprod/", $lowercomment)) {
    $delreasons['BLPPROD']++;
	}
   elseif(preg_match("/articles for deletion/", $lowercomment)) {
    $delreasons['XFD']++;
    $xfd['AFD']++;
	}
   elseif(preg_match("/redirects for discussion/", $lowercomment)) {
    $delreasons['XFD']++;
    $xfd['RFD']++;
	}
   elseif((preg_match("/templates for discussion/", $lowercomment)) || (preg_match("/templates for deletion/", $lowercomment))) {
    $delreasons['XFD']++;
    $xfd['TFD']++;
	}
   elseif(preg_match("/files for discussion/", $lowercomment)) {
    $delreasons['XFD']++;
    $xfd['FFD']++;
	}
   elseif(preg_match("/categories for discussion/", $lowercomment)) {
    $delreasons['XFD']++;
    $xfd['CFD']++;
	}
   elseif(preg_match("/miscellany for deletion/", $lowercomment)) {
    $delreasons['XFD']++;
    $xfd['MFD']++;
	}
   else {
    $delreasons['Other']++;
    
    if ($_GET['displayother'] == "yes") {
	    
	    if ($entry['comment'] == "") {
        $entry['comment'] = "&nbsp;";
      }
        
	    $otherreasons .= "<tr class='other'><td>" . $entry['comment'] . "</td></tr>\n";
	    
	}

}

}

//Display info about number of deletions
if (($_GET['limit'] == "number") && (array_sum($delreasons) >= (int)$_GET['limitnum'])) {

  $header .= "Displaying the last <strong>" . number_format(array_sum($delreasons)) . "</strong> deletions by this user<br/>";

}
else {

  $header .= "Total deletions by this user: <strong>" . number_format(array_sum($delreasons)) . "</strong><br/>";

  if ($_GET['limit'] == "date") {
    
    $header .= "(Daterange analyzed: " . $_GET['from'] . " - " . $_GET['to'] . ")<br/>";
    
  }

}

// Generate the output for the data processed
 
  //Create array for JS and use the same function to create table with data. Moves the generation of the JS code outside the rest of the JS but this way we only need to foreach() once
    // Reasons
    foreach ($delreasons as $key => $value) {
      if ($_GET['chart'] == "yes") {
	if (!($value == 0)) {
      	$chartcode_dr .=  "['" . $key . "', " . $value . "],\n";
      	}      
      }
	    
      $dr_output .= "<tr><td>$key</td><td class='right'>$value</td><td class='right'>" . number_format((($value / (array_sum($delreasons))) * 100), 2) . "%</td></tr>";
    }
    // XFD
    foreach ($xfd as $key => $value) {
      if ($_GET['chart'] == "yes") {
	if (!($value == 0)) {
      	$chartcode_xfd .=  "['" . $key . "', " . $value . "],\n";
      	}      
      }
	    
      $xfd_output .= "<tr><td>$key</td><td class='right'>$value</td><td class='right'>" . number_format((($value / (array_sum($delreasons))) * 100), 2) . "%</td></tr>";
    }
    // Speedy
    foreach ($csdreasons as $key => $value) {
      if (!($value == 0)) {
      if ($_GET['chart'] == "yes") {
      	$chartcode_sd .=  "['" . $key . "', " . $value . "],\n";
      	}      
      
	    $sd_output .= "<tr><td>$key</td><td class='right'>$value</td><td class='right'>" . number_format((($value / (array_sum($delreasons))) * 100), 2) . "%</td></tr>";
      }      
    }
	
// Generate chart js if requested
if ($_GET['chart'] == "yes") {

// For deletion reasons
$chartcode .= <<<HTML
      function drawReasonsChart() {

        var data = google.visualization.arrayToDataTable([
          ['Type of deletion', 'Amount'],
HTML;

$chartcode .= $chartcode_dr;

$chartcode .= <<<HTML
    ]);

    var options = {
        title: "Types of deletion",
        titleTextStyle: {
          fontSize: 16, 
          bold: true,  
        },
        colors: ['#FF9900', '#aaaa52', '#5858B0', '#3196b7', '#939393', '#2eaa33', '#156e19', '#8A4A8A'],
        pieSliceText: 'value',
        pieSliceBorderColor: 'black',
        legend: {position: 'labeled', textStyle:{color: '#555', fontSize: '0.9rem', bold: 1}},
        is3D: true,

    };

    var chart = new google.visualization.PieChart(document.getElementById('delreasons_div'));
    chart.draw(data, options);
}
HTML;

// for XFD types
$chartcode .= <<<HTML
      function drawXFDChart() {

        var data = google.visualization.arrayToDataTable([
          ['Type of XFD', 'Amount'],
HTML;

$chartcode .= $chartcode_xfd;

$chartcode .= <<<HTML
    ]);

    var options = {
        title: "XFD types",
        titleTextStyle: {
          fontSize: 16, 
          bold: true,  
        },
        colors: ['#FF9900', '#aaaa52', '#5858B0', '#3196b7', '#939393', '#2eaa33', '#156e19', '#8A4A8A'],
        pieSliceText: 'value',
        pieSliceBorderColor: 'black',
        legend: {position: 'labeled', textStyle:{color: '#555', fontSize: '0.9rem', bold: 1}},
        is3D: true,

    };

    var chart = new google.visualization.PieChart(document.getElementById('xfds_div'));
    chart.draw(data, options);
}
HTML;

// for speedy reasons
$chartcode .= <<<HTML
      function drawSpeedyChart() {

        var data = google.visualization.arrayToDataTable([
          ['Speedy criterion', 'Amount'],
HTML;
	
$chartcode .= $chartcode_sd;

$chartcode .= <<<HTML
    ]);

    var options = {
        title: "Types of speedy deletion",
        titleTextStyle: {
          fontSize: 16, 
          bold: true,  
        },
        pieSliceText: 'value',
        pieSliceBorderColor: 'black',
        legend: {position: 'labeled', textStyle:{color: '#555', fontSize: '0.9rem', bold: 1}},
        is3D: true,

    };

    var chart = new google.visualization.PieChart(document.getElementById('speedy_div'));
    chart.draw(data, options);
}
HTML;

}

}
// How to handle calls to the script with no or wrong variables
else {
// Standard form
$output .= <<<HTML
<form action="delanalyze.php" method="get">
<table border="0">
    <tr>
	<td><b>User:</b></td>
	<td colspan="2"><input type=text name="user" maxlength="85"></td>
    </tr>
    <tr>
	<td><b>Create pie chart?</b></td>
	<td colspan="2">
        	<input type="radio" name="chart" value="yes" checked>Yes <input type="radio" name="chart" value="no">No
	</td>
    </tr>
    <tr>
      <td><b>Limit output?</b></td>
      <td colspan="2"><label><div style="width: 100%"><input type="radio" name="limit" value="" id="no" checked>No</b></div></label></td>
    </tr>
    <tr>
      <td></td>
      <td><input type="radio" id="number" name="limit" value="number"><label for="number">By number:</label></td>
      <td>
        <label for="number">Display the last <input name="limitnum" placeholder="100" type="text" size="7"> deletions</label><br/>
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
	<td style="width:250px">
		<b>Display "other" reasons?</b><br/>
		<small>Will display all comments that the script was unable to sort into one of the categories. Depending on the user in question, this can be a lot of data.</small>
	</td>
	<td colspan="2"><input type="radio" name="displayother" value="yes">Yes <input type="radio" name="displayother" value="no" checked>No<br></td>
    </tr>
    <tr>
	<td align="right" colspan="3"><input type="submit" value="Get stats"></td>
    </tr>
</table>
</form>
	
<h4>Notes:</h4>
  <ul>
    <li>This script only works with deletions that follow a certain standard. Deletions without reasoning or non-standardized reasons (like very old speedy deletions) will not be interpreted correctly.</li>
    <li>The results will include mistakes when it comes to speedy deletions since I cannot figure out a sure way to identify multiple criteria in a single deletion comment. The script uses preg_match_all() for this and should find most of them though. Feel free to tell me if you think of a better way.</li>
  </ul>
HTML;

}

// Page output starts hereafter
?>

<html> 
<head>
  <meta charset="UTF-8">
<title>SoWhy's Deletion analyzer (<?php echo $version; ?>)</title>
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
//Add the chart code to head if one was created above
if ($chartcode) {

// General code for all charts
?>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">
      google.charts.load('current', {'packages':['corechart']});
      google.charts.setOnLoadCallback(drawReasonsChart);
      google.charts.setOnLoadCallback(drawXFDChart);
      google.charts.setOnLoadCallback(drawSpeedyChart);

<?php
// specific code
echo $chartcode;
?></script><?php
}
?>

</head>

<body>
<h3>SoWhy's script to analyze deletions</h3>
<h5>This tool attempts to parse deletions made by a single user and display them in a handy chart.</h5>
<?php 
if ($header) {
echo $header;
echo '<a href="delanalyze.php">&lt;&lt;&lt; Query another user</a><br/>';

//Create the boxes with the results
?>
<div class="container">
<div class="box">
<?php
if ($_GET['chart'] == "yes") {
echo '<div id="delreasons_div" style="width: 500px; height: 350px;"></div>';
}
echo "<table width='100%' class='tablesorter'><tr><th class='header'>Type</th><th class='header'>Amount</th><th class='header'>Percentage</th></tr><tbody>$dr_output</tbody></table>";

// Display a list of unparsed reasons if requested
if (($_GET['displayother'] == "yes") && ($otherreasons)) {
    
    ?>
    <input id="toggle" type="checkbox" style="display:none"><label for="toggle" class="expand">Deletion reasons that could not be parsed</label>
    <?php    
    echo "<div id='expand'><table>$otherreasons</table></div>";
    
}
    
?>
</div>
<div class="box">
<?php
if ($_GET['chart'] == "yes") {
echo '<div id="xfds_div" style="width: 500px; height: 350px;"></div>';
}
echo "<table width='100%' class='tablesorter'><tr><th class='header'>XFD</th><th class='header'>Amount</th><th class='header'>Percentage</th></tr><tbody>$xfd_output</tbody></table>";
?>
</div>
<div class="box">
<?php
if ($_GET['chart'] == "yes") {
echo '<div id="speedy_div" style="width: 500px; height: 350px;"></div>';
}
echo "<table width='100%' class='tablesorter'><tr><th class='header'>Criterion</th><th class='header'>Amount</th><th class='header'>Percentage</th></tr><tbody>$sd_output</tbody></table>";


?></div></div><?php
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
