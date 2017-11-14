<?php
$word = $_GET['word'];

header('Content-type: application/json');
echo $module->getDictionaryResponse($word);