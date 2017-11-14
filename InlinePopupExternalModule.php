<?php
/**
 * Created by PhpStorm.
 * User: mcguffk
 * Date: 3/8/2017
 * Time: 4:39 PM
 */

namespace Vanderbilt\InlinePopupExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

// This API key it for the account with email mark.mcever@vanderbilt.edu.
// This could easily be switched to another account (like the datacore email) if need be.
const API_KEY = '8bb9d0cd-d2ee-4a10-af12-e85a87155390';
const DICTIONARY_TYPE = 'medical/v2';
const BACKUP_API_KEY = 'f0898fcd-04a9-44f9-82a3-08af614d31e9';
const BACKUP_DICTIONARY_TYPE = 'collegiate/v1';

class InlinePopupExternalModule extends AbstractExternalModule {

	function hook_data_entry_form($project_id) {
		$this->includeSharedCode($project_id, 'on-data-entry-forms');
	}

	function hook_survey_page($project_id) {
		$this->includeSharedCode($project_id, 'on-surveys');
	}

	function includeSharedCode($project_id, $enabledSettingName) {
		?>
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tippy.js/0.3.0/tippy.css" integrity="sha256-s2OFSYvBLhc6NZMWLBpEmjCS9bI27OoN1ckY1z7Z/3w=" crossorigin="anonymous" />
		<script src="https://cdnjs.cloudflare.com/ajax/libs/tippy.js/0.3.0/tippy.js" integrity="sha256-eW1TBvNkruqAr5vlt8AwpDDHmoCSq8yfxIpveZ+LO9o=" crossorigin="anonymous"></script>

		<style>
			.tippy-popper{
				outline: 0;
			}

			.tippy-popper img{
				max-width: 100%;
			}

			.tippy-tooltip.light-theme{
				color: black;
				font-size: 14px;
				max-width: 500px;
				text-align: left;
			}

			a[popup]{
				cursor: pointer;
			}

			.tippy-popper a.pronunciation-audio{
				border: 1px solid #efdede;
				border-radius: 15px;
				padding: 1px 5px;
				text-decoration: none;
				display: inline-block;
				margin-bottom: 2px;
				background: white;
				box-shadow: 0px 1px 5px #dedede;
			}
		</style>
		<?php

		$linkTexts = $this->getProjectSetting('link-text');
		$texts = $this->getProjectSetting('text');
		$enabledFlags = $this->getProjectSetting($enabledSettingName);
		$firstMatchOnlyFlags = $this->getProjectSetting('first-match-only');
		$audioFlags = $this->getProjectSetting('show-pronunciation-audio');
		$oddcastFlags = $this->getProjectSetting('use-oddcast');

		for($i=0; $i<count($linkTexts); $i++){
			// We make the following lower case so that dictionary requests work properly.
			$linkText = strtolower($linkTexts[$i]);
			$text = $texts[$i];
			$enabledFlag = $enabledFlags[$i];
			$audioFlag = $audioFlags[$i];
			$oddcastFlag = $oddcastFlags[$i];

			if($enabledFlag != 1){
				continue;
			}

			if($audioFlag == 1){
				$text .= "<div style='text-align: center'><a href='#' class='pronunciation-audio'>&#x1f50a; Listen</a></div>";
			}

			if(!empty($linkText) && !empty($text)) {
				?>
				<div id="inline-popup-content-<?=$i?>" style="display: none">
					<div class="inline-popup-content-inner" data-link-text='<?=htmlspecialchars($linkText)?>' data-use-oddcast='<?=$oddcastFlag?>'>
						<?=$text?>
					</div>
					<!-- The following div exists to make sure any floated elements at the end of the content are contained within the popup. -->
					<div style="clear:both"></div>
				</div>
				<script>
					$(function(){
						var linkText = <?=json_encode($linkText)?>;
						var firstMatchOnly = <?=json_encode($firstMatchOnlyFlags[$i] == 1)?>;
						var searchFields = ["#surveyinstructions","#form"];
						var nodes = [];
						var currentItem;

						for(var i = 0; i < searchFields.length; i++) {
							if(currentItem = $(searchFields[i])[0]) {
								var nodeIterator = document.createNodeIterator(
									currentItem,
									NodeFilter.SHOW_TEXT,
									function(node) {
										if(node.parentNode.nodeName == 'SCRIPT' || node.textContent.trim() == '' || !$(node.parentNode).is(":visible")){
											return NodeFilter.FILTER_REJECT
										}

										return NodeFilter.FILTER_ACCEPT
									},
									false  // This argument is required by IE11, but does nothing.
								);
								nodes.push(nodeIterator);
							}
						}

						full_node_loop:
						for(var i = 0; i < nodes.length; i++) {
							var node;
							var nodeIterator = nodes[i];
							while(node = nodeIterator.nextNode()){
								// We force the font size to match the original text to get around the REDCap behavior where link font size changes on hover (on surveys).
								var fontSize = $(node.parentNode).css('font-size')

								var findString = /([^a-zA-Z]|^)(<?=preg_quote($linkText)?>)([^a-zA-Z]|$)/gi
								var replaceString = "$1<a popup='<?=$i?>' style='font-size: " + fontSize + "'>$2</a>$3"
								var newContent = node.textContent.replace(findString, replaceString)
								if(newContent != node.textContent){
									// Insert before, then remove.  Using replaceWith() or inserting after causes an infinite loop.
									$(node).before($('<span>' + newContent + '<span>'))
									$(node).remove()

									if(firstMatchOnly){
										// Break out of the while loop to stop replacing this term.
										break full_node_loop;
									}
								}
							}
						}
					})
				</script>
				<?php
			}
		}

		?>
		<script>
			$(function(){
				$('a[popup]').each(function() {
					new Tippy(this, {
						html: 'inline-popup-content-' + $(this).attr('popup'),
						trigger: 'mouseenter',
//						trigger: 'click',
						hideOnClick: false,
						theme: 'light',
						arrow: true,
						interactive: true
					})
				})

				var listenButtonSelector = '.inline-popup-content-inner a.pronunciation-audio';
				var filenames = {}

				$(listenButtonSelector).each(function(){
					var popupInner = $(this).closest('.inline-popup-content-inner')
					var linkText = popupInner.data('link-text')
					$.get(<?=json_encode($this->getUrl('get-audio-filename.php'))?> + '&NOAUTH&word=' + linkText, function(response){
						if(response.filename){
							filenames[linkText] = response.filename
						}
						else{
							console.log(response)

							if(response.error){
								alert(response.error)
							}
						}
					})
				})

				$(document).on('click', listenButtonSelector, function(e){
					e.preventDefault()

					var popupInner = $(this).closest('.inline-popup-content-inner')
					var linkText = popupInner.data('link-text')
					var useOddcast = popupInner.data('use-oddcast')

					if(useOddcast && window.OddcastAvatarExternalModule){
						OddcastAvatarExternalModule.sayText(linkText)
					}
					else{
						var filename = filenames[linkText]
						if(filename){
							var url = 'http://media.merriam-webster.com/soundc11/' + filename[0] + '/' + filename

							// This IE code was copied from here: https://stackoverflow.com/questions/39354085/how-to-play-wav-files-on-ie
							if(/msie/i.test(navigator.userAgent) || /trident/i.test(navigator.userAgent)){
								var origPlayer = document.getElementById('currentWavPlayer');
								if(origPlayer){
									origPlayer.src = '';
									origPlayer.outerHtml = '';
									document.body.removeChild(origPlayer);
									delete origPlayer;
								}
								var newPlayer = document.createElement('bgsound');
								newPlayer.setAttribute('id', 'currentWavPlayer');
								newPlayer.setAttribute('src', url);
								document.body.appendChild(newPlayer);
							}
							else{
								$('<audio src="' + url + '">')[0].play()
							}
						}
					}
				})
			})
		</script>
		<?php
	}

	public function validateSettings($settings){
		$terms = $settings['link-text'];
		$audioFlags = $settings['show-pronunciation-audio'];
		$oddcastFlags = $settings['use-oddcast'];

		$errorMessages = [];
		for($i=0;$i<count($terms);$i++){
			$term = $terms[$i];
			$audioFlag = $audioFlags[$i];
			$oddcastFlag = $oddcastFlags[$i];

			if($audioFlag && !$oddcastFlag){
				$response = json_decode($this->getDictionaryResponse($term), true);
				$error = @$response['error'];
				if($error){
					$errorMessages[] = $error;
				}
			}
		}

		return implode("\n", $errorMessages);
	}

	public function getDictionaryResponse($word){
		$dir = sys_get_temp_dir() . "/dictionary-audio-url-cache/";

		// We encode the word as an easy way to prevent malicious parameters.
		$path = $dir . md5($word);
		if(file_exists($path)){
			return file_get_contents($path);
		}

		foreach([DICTIONARY_TYPE => API_KEY,BACKUP_DICTIONARY_TYPE => BACKUP_API_KEY] as $dictionaryType => $apiKey) {
			$dictionaryApiLink = "http://www.dictionaryapi.com/api/references/" . $dictionaryType . "/xml/" . urlencode($word) . "?key=" . urlencode($apiKey);

			$response = simplexml_load_string(file_get_contents($dictionaryApiLink));

			$entry = @$response->entry;
			$wordFromEntry = @$entry->ew;
			$errorMessage = null;

			if(!$wordFromEntry){
				$errorMessage = "Pronunciation audio for the term '$word' could not be found.";
				$suggestions = @$response->suggestion;
				if($suggestions){
					$errorMessage .= "  Here is the list of suggestions:\n\n";
					foreach($suggestions as $suggestion){
						$errorMessage .= $suggestion . "\n";
					}
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

			if(!$wav) {
				$response = [
					'error' => "Could not find audio for the term '$word'.  Please check the response in the browser console in case the filename is in an unexpected location.",
					'response' => $entry
				];
			}
			else{
				$response = ['filename' => $wav->__toString()];
			}
		}

		$response = json_encode($response);
		
		file_put_contents($path, $response);

		return $response;
	}
}
