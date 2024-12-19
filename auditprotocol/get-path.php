<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Path Test</title>
</head>
<body>
<?php
echo $_SERVER['DOCUMENT_ROOT'];
?>
<pre>
<?php
print_r($_SERVER);
?>
</pre>
<?php
echo dirname(__FILE__);
?>
<br>
<?php
echo getcwd();
?>
<br>
<?php
echo realpath(dirname(__FILE__));
?>
<br>
<?php
echo $_SERVER['DOCUMENT_ROOT'].dirname($_SERVER['SCRIPT_NAME'])
?>
</body>
</html>