<?php
// Watcher script 2.0 - now with API

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

if ((int)$_GET['namespace'] != 0) {
$fullpage = $namespaces[$_GET['namespace']]['*'] . ":" . $page;
}
else {
$fullpage = $page;
}

$apiquery = 'https://en.wikipedia.org/w/api.php?action=query&format=json&prop=info&titles=' . urlencode($fullpage) . '&inprop=watchers';

// set up cURL to query API, thanks for the code to DaveRandom at https://stackoverflow.com/questions/8956331/how-to-get-results-from-the-wikipedia-api-with-php/8956526
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiquery);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_USERAGENT, 'SoWhyScript/0.2 (https://tools.wmflabs.org/sowhy/)');

$result = curl_exec($ch);

if (!$result) {
  exit('cURL Error: '.curl_error($ch));
}

$parsed_api = json_decode($result, true);

$page_infos = $parsed_api['query']['pages'];


foreach ($page_infos as $page_info) {

	$watchers = $page_info['watchers'];

}



// Get Jimbo's watchers

if (($_GET['namespace'] == 2) || ($_GET['namespace'] == 3)) {

$api_j = 'https://en.wikipedia.org/w/api.php?action=query&format=json&prop=info&titles=User:Jimbo%20Wales&redirects=1&inprop=watchers';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_j);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_USERAGENT, 'SoWhyScript/0.2 (https://tools.wmflabs.org/sowhy/)');

$result_j = curl_exec($ch);

if (!$result_j) {
  exit('cURL Error: '.curl_error($ch));
}

$parsed_api_j = json_decode($result_j, true);

$parsed_api_j = json_decode($result_j, true);

$jimbo_watchers = $parsed_api_j['query']['pages']['2829412']['watchers'];
}

if($watchers) {
$output = "<table>";

  $output .= "<tr><td><b>Page:</b></td><td>" . str_replace("_", " ", $fullpage) . "</td></tr>";

  $output .= "<tr><td><b>Watchers: </b></td><td>" . $watchers . "</td></tr>";

  if (($_GET['namespace'] == 2) || ($_GET['namespace'] == 3)) {

    $cj = round((($watchers / $jimbo_watchers) * 100), 2);

    $output .= "<tr><td><b>Centijimbos: </b></td><td>(" . $watchers . " / " . $jimbo_watchers . ") * 100 ≈ <b>" . $cj . "</b></td></tr>";;

  }
  
  $output .= '</table>';

}
else {

$output .= "<b>" . $fullpage . "</b> does not exist or has less than 30 watchers.";

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

	<h4>Notes:</h4>The tool relies on querying the API which includes only public information. Pages with less than 30  watchers are not shown, so the tool will claim them not to exist.
HTML;

}

// Page output starts hereafter
?>

<html>
<head>
  <meta charset="UTF-8">
<title>SoWhy's Watcher tool (v0.02-API)</title>
<link rel="stylesheet" type="text/css" href="sowhy.css">


</head>

<body>
<h3>SoWhy's watcher tool</h3>
<h5>This tool displays how many people watch a certain page (and displays the amount in <a href="https://en.wikipedia.org/wiki/Wikipedia:Centijimbos" target="new"> centijimbos</a> when checking a user page).</h5>

<?php

echo $output;

?>

<div id="footer">A <a href="https://en.wikipedia.org/wiki/User:SoWhy" target="new"><span style="font-variant: small-caps"><span style="color: #7A2F2F">So</span><span style="color: #474F84">Why</span></span></a> script. Feedback welcome.<br/>Source code available at <a href="https://github.com/SoWhy/wp-tools" target="new">GitHub</a>

</div>
</body>
</html>
