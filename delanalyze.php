<?php

/*

DelAnalyze.php - Script to analyze deletions

(C) 2017-2020 Alessandro Fuschi (SoWhy) // sowhy@sowhy.de

Released under the MIT License: https://opensource.org/licenses/MIT

*/

$version = "0.09α (2020-01-04)";
$maxlimit = 500000;

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
'Speedy deletion' => 0,
'PROD' => 0,
'BLPPROD' => 0,
'Old file revisions' => 0,
'Revision deletion' => 0,
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

//try to match as many csd templates as possible
$csdtemplates = array(
'db-noncom' => 'F3',
'db-author' => 'G7',
'db-attack' => 'G10',
'db-bio' => 'A7',
'db-band' => 'A7',
'db-event' => 'A7',
'db-org' => 'A7',
'db-corp' => 'A7',
'db-inc' => 'A7',
'db-club' => 'A7',
'db-web' => 'A7',
'db-animal' => 'A7',
'nn-bio' => 'A7',
'nn-band' => 'A7',
'db-badfairuse' => 'F7',
'db-blanked' => 'G7',
'db-blank' => 'A3',
'db-nonsense' => 'G1',
'db-vandalism' => 'G3',
'db-spam' => 'G11',
'db-copyvio' => 'G12',
'db-notability' => 'A7',
'db-nocontext' => 'A1',
'db-test' => 'G2',
'db-redirtypo' => 'R3',
'db-hoax' => 'G3',
'db-repost' => 'G4',
'db-banned' => 'G5',
'db-copypaste' => 'G6',
'db-disambig' => 'G6',
'db-error' => 'G6',
'db-move' => 'G6',
'db-xfd' => 'G6',
'db-redircom' => 'G6',
'db-blankdraft' => 'G6',
'db-self' => 'G7',
'db-imagepage' => 'G8',
'db-redirnone' => 'G8', 
'db-subpage' => 'G8', 
'db-talk' => 'G8', 
'db-templatecat' => 'G8',
'db-negublp' => 'G10',
'db-afc' => 'G13',
'db-draft' => 'G13',
'db-foreign' => 'A2',
'db-empty' => 'A3',
'db-transwiki' => 'A5',
'db-song' => 'A9',
'db-album' => 'A9',
'db-same' => 'A10',
'db-invented' => 'A11',
'db-madeup' => 'A11',
'di-no source' => 'F4', 
'di-no license' => 'F4', 
'di-no source no license' => 'F4', 
'di-dw no source' => 'F4', 
'di-dw no license' => 'F4', 
'di-dw no source no license' => 'F4',
'di-orphaned fair use' => 'F5',
'di-no fair use rationale' => 'F6', 
'di-missing article links' => 'F6',
'db-catempty' => 'C1',
'db-userreq' => 'U1',
'db-nouser' => 'U2',
'db-gallery' => 'U3',
'db-notwebhost' => 'U5',
'db-policy' => 'T2',
'db-duplicatetemplate' => 'T3'
);

// Namespaces
$namespaces = array(
0 => "",
1 => "Talk",
2 => "User",
3 => "User talk",
4 => "Wikipedia",
5 => "Wikipedia talk",
6 => "File",
7 => "File talk",
8 => "MediaWiki",
9 => "MediaWiki talk",
10 => "Template",
11 => "Template talk",
12 => "Help",
13 => "Help talk",
14 => "Category",
15 => "Category talk",
100 => "Portal",
101 => "Portal talk",
108 => "Book",
109 => "Book talk",
118 => "Draft",
119 => "Draft talk",
446 => "Education program",
447 => "Education program talk",
710 => "TimedText",
711 => "TimedText talk",
828 => "Module",
829 => "Module talk",
2300 => "Gadget",
2301 => "Gadget talk",
2302 => "Gadget definition",
2303 => "Gadget definition talk",
-1 => "Special",
-2 => "Media"
);

//sanitize username
$username = htmlspecialchars( strip_tags($_GET['user']) );
$username = str_replace("_", " ", $username);

// DB connection
$ts_pw = posix_getpwuid(posix_getuid());
$ts_mycnf = parse_ini_file($ts_pw['dir'] . "/replica.my.cnf");

$mysqli = new mysqli('enwiki.labsdb', $ts_mycnf['user'], $ts_mycnf['password'], 'enwiki_p');
if ($mysqli->connect_errno) {
    die("Verbindung fehlgeschlagen: " . $mysqli->connect_error);
}

// Check if user actually exists and if so, extract user id

$sql_c = 'SELECT user_id FROM user WHERE user_name = "' . $username . '" LIMIT 1';


$statement = $mysqli->prepare($sql_c);
$statement->execute();

$result_u = $statement->get_result();

if (!$result_u) {
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
/*
$sql = 'SELECT log.log_timestamp AS "timestamp", log.log_namespace AS "namespace", log.log_title AS "page", cmt.comment_text AS "comment"
 FROM logging_userindex AS log
 LEFT JOIN comment AS cmt ON log.log_comment_id = cmt.comment_id
 WHERE log.log_type = "delete" 
 AND (log.log_action = "delete" OR log.log_action = "revision")
 AND log.log_user = ' . $userid . '';

*/

// New query 2020 since structure was changed
$sql = 'SELECT log.log_timestamp AS "timestamp", log.log_namespace AS "namespace", log.log_title AS "page", cmt.comment_text AS "comment"
 FROM logging AS log
 LEFT JOIN actor AS act ON log.log_actor = act.actor_id
 LEFT JOIN comment AS cmt ON log.log_comment_id = cmt.comment_id
 WHERE log.log_type = "delete"
 AND (log.log_action = "delete" OR log.log_action = "revision")
 AND act.actor_user = ' . $userid . '';


// Add limit if requested
if ($_GET['limit'] == "number") {

  // Set a hard limit to avoid huge deletion logs from crashing the script
  if ((int)$_GET['limitnum'] > $maxlimit) {
    $_GET['limitnum'] = $maxlimit;
    }

$sql .= '
ORDER BY log.log_timestamp DESC
LIMIT ' . (int)$_GET['limitnum'];
}
elseif ($_GET['limit'] == "date") {
$sql .= '
AND log.log_timestamp >= ' . $from . '
AND log.log_timestamp <= ' . $to . '
ORDER BY log.log_timestamp DESC
LIMIT ' . $maxlimit;
}

else {
// Add a hard max limit to avoid huge deletion logs from crashing the script
$sql .= '
ORDER BY log.log_timestamp DESC
LIMIT ' . $maxlimit;
}

$header .= "<b>User:</b> $username<br/>";

// Do query
$statement = $mysqli->prepare($sql);
$statement->execute();

$result = $statement->get_result();

if (!$result) {
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
    
    $delreasons['Speedy deletion']++;

    preg_match_all("/wp:[a-z][0-9]{1,2}/", $lowercomment, $matches);
  
    foreach($matches[0] as $criterion) {

      $criterion = strtoupper(str_replace("wp:", "", $criterion));
           
      // Add criterion to array if correctly identified 
      if (array_key_exists("$criterion", $csdreasons)) {
    
        $csdreasons["$criterion"]++;
    
      }
    
    }
    
  } 
 
   elseif(preg_match("/\[\[wp:c?sd#[a-z][0-9]{1,2}/", $lowercomment)) {
    $delreasons['Speedy deletion']++;

    $criterion = strtoupper(GetBetween("sd#", "|", $lowercomment));
    
    // Add criterion to array if correctly identified 
    if (array_key_exists("$criterion", $csdreasons)) {
    
      $csdreasons["$criterion"]++;
    
    }
      
  } 
  
    elseif(preg_match("/criteria for speedy deletion#[a-z][0-9]{1,2}/", $lowercomment)) {
    $delreasons['Speedy deletion']++;

    $criterion = strtoupper(GetBetween("criteria for speedy deletion#", "|", $lowercomment));
    
    // Add criterion to array if correctly identified 
    if (array_key_exists("$criterion", $csdreasons)) {
    
      $csdreasons["$criterion"]++;
    
    }
      
  } 
  elseif(preg_match("/\[\[wp:[a-z][0-9]{1,2}/", $lowercomment)) {
    
    $delreasons['Speedy deletion']++;
    
    $criterion =  strtoupper(GetBetween("[[wp:", "|", $lowercomment));
    
    // Add criterion to array if correctly identified 
    if (array_key_exists("$criterion", $csdreasons)) {
    
      $csdreasons["$criterion"]++;
    
    }
    
  }
  
  elseif(preg_match("/\[\[wikipedia:[a-z][0-9]{1,2}/", $lowercomment)) {
    
    $delreasons['Speedy deletion']++;
    
    $criterion =  strtoupper(GetBetween("[[wikipedia:", "|", $lowercomment));
    
   
    // Add criterion to array if correctly identified 
    if (array_key_exists("$criterion", $csdreasons)) {
    
      $csdreasons["$criterion"]++;
    
    }

}

  elseif(preg_match("/\[\[wikipedia:csd\#[a-z][0-9]{1,2}/", $lowercomment)) {

    $delreasons['Speedy deletion']++;

    $criterion =  strtoupper(GetBetween("[[wikipedia:csd#", "|", $lowercomment));


    // Add criterion to array if correctly identified
    if (array_key_exists("$criterion", $csdreasons)) {

      $csdreasons["$criterion"]++;

    }

    
  }
  
  elseif((preg_match("/\[\[wp:rd[0-9]/", $lowercomment)) || (preg_match("/\[\[wikipedia:rd[0-9]/", $lowercomment)))  {
    
    $delreasons['Revision deletion']++;
    
  }
    
  
  elseif (preg_match("/wp:prod/", $lowercomment)) {
    $delreasons['PROD']++;
	}
   elseif (preg_match("/wp:blpprod/", $lowercomment)) {
    $delreasons['BLPPROD']++;
	}
   elseif ((preg_match("/articles for deletion/", $lowercomment)) || (preg_match("/votes for deletion/", $lowercomment))) {
    $delreasons['XFD']++;
    $xfd['AFD']++;
	}
   elseif ((preg_match("/redirects for discussion/", $lowercomment)) || (preg_match("/redirects for deletion/", $lowercomment))) {
    $delreasons['XFD']++;
    $xfd['RFD']++;
	}
   elseif ((preg_match("/templates for discussion/", $lowercomment)) || (preg_match("/templates for deletion/", $lowercomment))) {
    $delreasons['XFD']++;
    $xfd['TFD']++;
	}
   elseif ((preg_match("/files for discussion/", $lowercomment)) || (preg_match("/files for deletion/", $lowercomment)) || (preg_match("/images for deletion/", $lowercomment)) || (preg_match("/images and media for deletion/", $lowercomment))) {
    $delreasons['XFD']++;
    $xfd['FFD']++;
	}
   elseif ((preg_match("/categories for discussion/", $lowercomment)) || (preg_match("/categories for deletion/", $lowercomment))) {
    $delreasons['XFD']++;
    $xfd['CFD']++;
	}
   elseif ((preg_match("/miscellaneous deletion/", $lowercomment)) || (preg_match("/miscellany for deletion/", $lowercomment))) {
    $delreasons['XFD']++;
    $xfd['MFD']++;
	}
	elseif(preg_match("/deleted old revision/", $lowercomment)) {
	$delreasons['Old file revisions']++;
	}
	
   else {
    // Try to catch old deletions based on templates
    foreach ($csdtemplates as $template => $crit) {
      if(preg_match("/$template/", $lowercomment)) {
    
        $delreasons['Speedy deletion']++;
    
        $csdreasons["$crit"]++;
    
        $foundold = 1;
        
      }
    }
	   
    // Try to catch old deletions based on db-xx
    if(preg_match("/\{\{db-[a-z][0-9]{1,2}\}\}/", $lowercomment)) {
    
        $delreasons['Speedy deletion']++;
        
	$crit =  strtoupper(GetBetween("{{db-", "}}", $lowercomment));

        $csdreasons["$crit"]++;
    
        $foundold = 1;
        
      }   
    
   if ($foundold != 1) {
    
    $delreasons['Other']++;
    
    if ($_GET['displayother'] == "yes") {
	    
	    if ($entry['comment'] == "") {
        $entry['comment'] = " ";
      }
        
	    $otherreasons .= "<tr class='other'><td>" . date("d M Y, h:i \(\\U\\T\C)", strtotime($entry['timestamp'])) . "</td><td>" . $namespaces[htmlspecialchars(strip_tags($entry['namespace']))] . ":" . str_replace("_", " ", htmlspecialchars(strip_tags($entry['page']))) . "</td><td>" . htmlspecialchars(strip_tags($entry['comment'])) . "</td></tr>\n";
	  
	    
      }
    }
  	  $foundold = 0;
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
	    
      $dr_output .= "<tr><td>$key</td><td class='right'>" . number_format($value) . "</td><td class='right'>" . number_format((($value / (array_sum($delreasons))) * 100), 2) . "%</td></tr>";
    }
    // XFD
    foreach ($xfd as $key => $value) {
      if ($_GET['chart'] == "yes") {
	if (!($value == 0)) {
      	$chartcode_xfd .=  "['" . $key . "', " . $value . "],\n";
      	}      
      }
	    
      $xfd_output .= "<tr><td>$key</td><td class='right'>" . number_format($value) . "</td><td class='right'>" . number_format((($value / (array_sum($delreasons))) * 100), 2) . "%</td></tr>";
    }
    // Speedy
    foreach ($csdreasons as $key => $value) {
      if (!($value == 0)) {
      if ($_GET['chart'] == "yes") {
      	$chartcode_sd .=  "['" . $key . "', " . $value . "],\n";
      	}      
      
	    $sd_output .= "<tr><td>$key</td><td class='right'>" . number_format($value) . "</td><td class='right'>" . number_format((($value / (array_sum($delreasons))) * 100), 2) . "%</td></tr>";
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
    echo "<div id='expand'><table><tr><th>Date</th><th>Page name</th><th>Comment</th>$otherreasons</table></div>";
    
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
