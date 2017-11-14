<?php
$word = $_GET['word'];

header('Content-type: application/json');
echo file_get_contents($module->getDictionaryAudioCachePath($word));