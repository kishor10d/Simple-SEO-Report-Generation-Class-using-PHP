<?php

require_once "SeoReport.php";

if(isset($_POST["url"])){
	
	$url = $_POST["url"];
	
	$report = new SeoReport($url);
	
	$reqHTML = $report->getSeoReport();
}

?>
<DOCTYPE html>
<html>
<head>
<title>SEO Report Demo</title>
</head>
<body>
<h1>SEO Report Demo</h1>
<br><br><br>
<form action="index.php" method="post">
URL : <input type="text" name="url" />
<p>You must enter valid url</p>
<br><br>
<input type="submit" value="submit" />
</form>
<br>
<br>
<br>
<?php

if(isset($reqHTML)){
	echo $reqHTML;
}
?>

</body>
</html>