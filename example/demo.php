<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
</head>
<body>
<form method="POST">
<input type="text" name="txt" value="">
<input type="submit" value="segment">
</form>
<?php
require_once "../src/WordBreaker.php";
use PhlongTaIam\WordBreaker as WordBreaker;

if (isset($_POST["txt"])) {
    echo "Text:".htmlspecialchars($_POST["txt"], ENT_QUOTES, "UTF-8");
    $wordBreaker = new WordBreaker("../data/tdict-std.txt");
?>
<ul>
<?php
    foreach($wordBreaker->breakIntoWords($_POST["txt"]) as $w) {
        print "<li>" . htmlspecialchars($w, ENT_QUOTES, "UTF-8") . "</li>\n";
    }
}
?>
</ul>
</body>
</html>
