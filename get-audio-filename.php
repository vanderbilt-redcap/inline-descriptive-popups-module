<?php

// This API key it for the account with email mark.mcever@vanderbilt.edu.
// This could easily be switched to another account (like the datacore email) if need be.
const API_KEY = '8bb9d0cd-d2ee-4a10-af12-e85a87155390';
const DICTIONARY_TYPE = 'medical';

function getResponse($word){
	$response = simplexml_load_string(file_get_contents("http://www.dictionaryapi.com/api/references/" . urlencode(DICTIONARY_TYPE) . "/v2/xml/" . urlencode($word) . "?key=" . urlencode(API_KEY)));

	$entry = @$response->entry;
	$wordFromEntry = @$entry->ew;
	$errorMessage = null;

	if(!$wordFromEntry){
		$errorMessage = "Could not find a matching term for '$word'.  Here is the list of suggestions:\n\n";

		foreach(@$response->suggestion as $suggestion){
			$errorMessage .= $suggestion . "\n";
		}
	}
	else if($entry->ew != $word){
		$errorMessage = "Could not find an exact match for '$word'.  The closest entry was '{$entry->ew}'.";
	}

	if($errorMessage){
		$response = ['error' => $errorMessage];
	}
	else{
		$response = ['filename' => $entry->sound->wav->__toString()];
	}

	return json_encode($response);
}

$word = $_GET['word'];

$dir = sys_get_temp_dir() . "/dictionary-audio-url-cache/";

// We encode the word as an easy way to prevent malicious parameters.
$path = $dir . md5($word);

if(!file_exists($path)){
	@mkdir($dir);
	file_put_contents($path, getResponse($word));
}

header('Content-type: application/json');
echo file_get_contents($path);