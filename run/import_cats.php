<?php
$base = $_SERVER['HOME'] . "/apps/modanellsons";
include($base . '/public/wp-blog-header.php');
require_once('decode.php');

$categories = json_decode(file_get_contents($base."/import/run/categories.json"), true);

nellcorp_import_categories();

?>