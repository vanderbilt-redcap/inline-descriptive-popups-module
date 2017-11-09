<?php

// This API key it for the account with email mark.mcever@vanderbilt.edu.
// This could easily be switched to another account (like the datacore email) if need be.
const API_KEY = '8bb9d0cd-d2ee-4a10-af12-e85a87155390';
const DICTIONARY_TYPE = 'medical/v2';
const BACKUP_API_KEY = 'f0898fcd-04a9-44f9-82a3-08af614d31e9';
const BACKUP_DICTIONARY_TYPE = 'collegiate/v1';

function getResponse($word){
	foreach([DICTIONARY_TYPE => API_KEY,BACKUP_DICTIONARY_TYPE => BACKUP_API_KEY] as $dictionaryType => $apiKey) {
		$dictionaryApiLink = "http://www.dictionaryapi.com/api/references/" . $dictionaryType . "/xml/" . urlencode($word) . "?key=" . urlencode($apiKey);

		$response = simplexml_load_string(file_get_contents($dictionaryApiLink));

		$entry = @$response->entry;
		$wordFromEntry = @$entry->ew;
		$errorMessage = null;

		if(!$wordFromEntry){
			$errorMessage = "Could not find a matching term for '$word'.  Here is the list of suggestions:\n\n";

			foreach(@$response->suggestion as $suggestion){
				$errorMessage .= $suggestion . "\n";
			}
		}
//		else if($entry->ew != $word){
//			$errorMessage = "Could not find an exact match for '$word'.  The closest entry was '{$entry->ew}'.";
//		}

		## If a term is found in the medical dictionary, don't bother checking the collegiate dictionary
		if(!$errorMessage) break;
	}

	if($errorMessage){
		$response = ['error' => $errorMessage];
	}
	else{
		$wav = @$entry->vr->sound->wav; // Ex: "dyspnea"

		if(!$wav){
			$wav = @$entry->uro->sound->wav;
		}

		if(!$wav){
			$wav = $entry->sound->wav; // Ex: "shampoo"
		}

		$response = ['filename' => $wav->__toString()];
	}

	return json_encode($response);
}

$word = $_GET['word'];

$dir = sys_get_temp_dir() . "/dictionary-audio-url-cache/";

// We encode the word as an easy way to prevent malicious parameters.
$path = $dir . md5($word);

## Cache files for an hour
if(!file_exists($path) || (time() - filemtime($path) >= 3600)){
	@mkdir($dir);
	file_put_contents($path, getResponse($word));
}

header('Content-type: application/json');
echo file_get_contents($path);