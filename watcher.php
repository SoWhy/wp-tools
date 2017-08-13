<?php
// Create namespace array

$nsquery = file_get_contents("https://en.wikipedia.org/w/api.php?action=query&meta=siteinfo&siprop=namespaces&format=json");

$parsed_json = json_decode($nsquery, true);

$namespaces = $parsed_json['query']['namespaces'];

foreach ($namespaces as $namespace) {
  if ($namespace['id'] == 0) {
    $namespaceselect .= '<option value="' . $namespace['id'] . '" selected>(article)</option>';
  }
  elseif ($namespace['id'] > 0) {
    $namespaceselect .= '<option value="' . $namespace['id'] . '">' . $namespace['*'] . '</option>';
  }
}

if (($_GET['page']) && (strlen($_GET['page']) <= 255) && (is_int((int)$_GET['namespace'])) && ($namespaces[$_GET['namespace']] != "")) {

//sanitize page name
$page = htmlspecialchars( strip_tags($_GET['page']) );
$page = str_replace(" ", "_", $page);

if ((int)$_GET['namespace'] != 0) {
$fullpage = $namespaces[$_GET['namespace']]['*'] . ":" . $page;
}
else {
$fullpage = $page;
}

// DB connection
$ts_pw = posix_getpwuid(posix_getuid());
$ts_mycnf = parse_ini_file($ts_pw['dir'] . "/replica.my.cnf");

$mysqli = new mysqli('enwiki.labsdb', $ts_mycnf['user'], $ts_mycnf['password'], 'enwiki_p');

//Actual query
$sql = 'SELECT watchers FROM watchlist_count WHERE wl_title = "' . $page . '" AND wl_namespace = ' . $_GET['namespace'];

// Do query
if (!$result = $mysqli->query($sql)) {
    echo "<h1>Sorry, the website is experiencing problems.</h1>\n";
    exit;
    }

// Get Jimbo's watchers

if (($_GET['namespace'] == 2) || ($_GET['namespace'] == 3)) {

$sql_j = 'SELECT watchers FROM watchlist_count WHERE wl_title = "Jimbo_Wales" AND wl_namespace = 2';

if (!$result_j = $mysqli->query($sql_j)) {
    echo "<h1>Sorry, the website is experiencing problems.</h1>\n";
    exit;
    }

while($entry_j = $result_j->fetch_assoc()) {

$jw = $entry_j['watchers'];
}

}

// Parse results


if ($result->num_rows === 0) {

$output .= "<b>" . $fullpage . "</b> does not exist or has less than 30 watchers.";

}
else {

$output = "<table>";
while($entry = $result->fetch_assoc()) {

  $output .= "<tr><td><b>Page:</b></td><td>" . str_replace("_", " ", $fullpage) . "</td></tr>";

  $output .= "<tr><td><b>Watchers: </b></td><td>" . $entry['watchers'] . "</td></tr>";

  if (($_GET['namespace'] == 2) || ($_GET['namespace'] == 3)) {

    $cj = round((($entry['watchers'] / $jw) * 100), 2);

    $output .= "<tr><td><b>Centijimbos: </b></td><td>(" . $entry['watchers'] . " / " . $jw . ") * 100 ≈ <b>" . $cj . "</b></td></tr>";;

  }

}

$output .= '</table>';
}

$output .= '<br/><br/><br/><a href="watcher.php">&lt;&lt;&lt; Query another page</a>';

}
else {
// Standard form
$output .= <<<HTML
<form action="watcher.php" method="get">
	<table border="0">
		<tr>
			<td><b>Page:</b></td>
      <td><select name="namespace">
HTML;

$output .= $namespaceselect;

$output .= <<<HTML
			</select></td>
			<td><input type=text name="page" maxlength="255"></td>
		</tr>
		<tr>
			<td align="right" colspan="3"><input type="submit" value="Get watchers"></td>
		</tr>
	</table>
	</form>

	<h4>Notes:</h4>The tool relies on querying the replica database which includes only public information. Pages with less than 30  watchers are not included in these replicaes, so the tool will claim them not to exist.
HTML;

}

// Page output starts hereafter
?>

<html>
<head>
  <meta charset="UTF-8">
<title>SoWhy's Watcher tool (v0.01)</title>
<link rel="stylesheet" type="text/css" href="sowhy.css">


</head>

<body>
<h3>SoWhy's watcher tool</h3>
<h5>This tool displays how many people watch a certain page (and displays the amount in <a href="https://en.wikipedia.org/wiki/Wikipedia:Centijimbos" target="new"> centijimbos</a> when checking a user page).</h5>

<?php

echo $output;

?>

<div id="footer">A <a href="https://en.wikipedia.org/wiki/User:SoWhy" target="new"><span style="font-variant: small-caps"><span style="color: #7A2F2F">So</span><span style="color: #474F84">Why</span></span></a> script. Feedback welcome.<br/>Source code available upon request.

</div>
</body>
</html>
