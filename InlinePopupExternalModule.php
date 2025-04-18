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

	function hook_data_entry_form($project_id, $record, $instrument) {
		$this->includeSharedCode($project_id, $record, $instrument, 'on-data-entry-forms');
	}

	function hook_survey_page($project_id, $record, $instrument) {
		$this->includeSharedCode($project_id, $record, $instrument, 'on-surveys');
	}

	function includeSharedCode($project_id, $record, $instrument, $enabledSettingName) {
		$this->initializeJavascriptModuleObject();
		?>
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tippy.js/2.2.2/tippy.css" integrity="sha512-LuYufJlyTVvLIIRajTn4Q6x7Q7dXHGkY1fqxufU0ddqNpneKZ53AycO2WNh2Uhh3IGernZyhrvzh9zDNxOpxYg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tippy.js/2.2.2/themes/light.css" integrity="sha512-pHZ4Ml9vZuOvQQVXvt3xeLLfF5E+axs3BUAjoh7SuEvE45y92pxBIn7e3LYeOC9nyKXjY/NsOl2DscqRk6q/dA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
		<script src="https://cdnjs.cloudflare.com/ajax/libs/tippy.js/2.2.2/tippy.all.min.js" integrity="sha512-3b/o1nanVKJyVhsAET5kKOQRpgkAxxhD8Ybh5O1L0KlfuWEuQJC1ZVNu4jiXV7ZUpngQSvQZADiB9hpkR+r9Bw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
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

			.tippy-tooltip.inline-popups-theme {
				background-color: #f5f6f7;
				box-shadow: 0 0px 50px 6px rgba(36, 40, 47, 0.21);
			}

			.tippy-popper[x-placement^=top] .tippy-tooltip.inline-popups-theme .tippy-arrow{
				border-top: 7px solid #f5f6f7;
			}

			.tippy-popper[x-placement^=bottom] .tippy-tooltip.inline-popups-theme .tippy-arrow{
				border-bottom: 7px solid #f5f6f7;
			}
		</style>
		<script>
			$(function(){
				var module = <?=$this->framework->getJavascriptModuleObjectName()?>;
				module.initializers = []
				module.afterMultiLanguageInitialized = function(action){
					if(
						window.REDCap === undefined
						||
						REDCap.MultiLanguage === undefined
						||
						(
							REDCap.MultiLanguage.initialized // briefly the correct approach for a few REDCap versions
							||
							(
								REDCap.MultiLanguage.isInitialized
								&&
								REDCap.MultiLanguage.isInitialized() // the correct approach going forward
							)
						)
						
					){
						action()
					}
					else{
						setTimeout(function(){
							module.afterMultiLanguageInitialized(action)
						}, 100)
					}
				}
			})
		</script>
		<?php

		$linkColor = $this->getProjectSetting('link-color');
		$subSettings = $this->getSubSettings('field');
		for($i=0; $i<count($subSettings); $i++){
			$linkSettings = $subSettings[$i];

			// We make the following lower case so that dictionary requests work properly.
			$linkText = strtolower($linkSettings['link-text']);
			$text = $linkSettings['text'];
			$enabledFlag = $linkSettings[$enabledSettingName];
			$firstMatchOnlyFlag = $linkSettings['first-match-only'];
			$audioFlag = $linkSettings['show-pronunciation-audio'];
			$oddcastFlag = $linkSettings['use-oddcast'];
			$forms = $linkSettings['forms'] ?? [];
			$pageNumbers = $linkSettings['page-numbers'] ?? [];

			// Remove the empty string default values.
			$forms = array_filter($forms);
			$pageNumbers = array_filter($pageNumbers);

			$currentPageNumber = $_GET['__page__'];

			$matchesForm = empty($forms) || in_array($instrument, $forms);
			$matchesPageNumber = empty($pageNumbers) || in_array($currentPageNumber, $pageNumbers);

			if($enabledFlag != 1 || !$matchesForm || !$matchesPageNumber){
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
						var module = <?=$this->framework->getJavascriptModuleObjectName()?>;
						module.initializers.push(function(){
							var firstMatchOnly = <?=json_encode($firstMatchOnlyFlag == 1)?>;
							var searchFields = ["#surveyinstructions","#form"];
							var nodes = [];
							var currentItem;

							for(var i = 0; i < searchFields.length; i++) {
								if(currentItem = $(searchFields[i])[0]) {
									var nodeIterator = document.createNodeIterator(
										currentItem,
										NodeFilter.SHOW_TEXT,
										function(node) {
											// We don't always want to reject hidden elements because they may appear later due to branching logic.
											var firstMatchOnlyAndInvisible = firstMatchOnly && !$(node.parentNode).is(":visible")

											if(node.parentNode.nodeName == 'SCRIPT' || node.textContent.trim() == '' || firstMatchOnlyAndInvisible){
												return NodeFilter.FILTER_REJECT
											}

											return NodeFilter.FILTER_ACCEPT
										},
										false  // This argument is required by IE11, but does nothing.
									);
									nodes.push(nodeIterator);
								}
							}

							var shouldSkip = function(node){
								var parent = node.parentNode
								if(parent === null){
									// We've reached past the root document node
									return false
								}
								else if(['A', 'TEXTAREA'].indexOf(parent.tagName) !== -1){
									// Do not modify matching terms inside certain HTMl tags
									return true
								}
								else{
									return shouldSkip(parent)
								}
							}

							full_node_loop:
							for(var i = 0; i < nodes.length; i++) {
								var node;
								var nodeIterator = nodes[i];
								while(node = nodeIterator.nextNode()){
									// We force the font size to match the original text to get around the REDCap behavior where link font size changes on hover (on surveys).
									var fontSize = $(node.parentNode).css('font-size')

									var style = 'font-size: ' + fontSize

									<?php if(!empty($linkColor)) { ?>
										style += '; color: <?=$linkColor?>'
									<?php } ?>

									var flags = "i"
									if(!firstMatchOnly){
										flags += 'g'
									}

									var findString = new RegExp("([^a-zA-Z]|^)(<?=preg_quote($linkText)?>)([^a-zA-Z]|$)", flags)

									var replaceString = "$1<a popup='<?=$i?>' href='javascript:void(0)' data-link-text='<?=htmlspecialchars($linkText)?>' style='" + style + "'>$2</a>$3"
									var newContent = node.textContent.replace(findString, replaceString)
									if(newContent != node.textContent && !shouldSkip(node)){
										// Insert before, then remove.  Using replaceWith() or inserting after causes an infinite loop.
										$(node).before($('<span>' + newContent + '<span>'))
										$(node).remove()

										if(firstMatchOnly){
											// Break out of the outer loop to stop replacing this term.
											break full_node_loop;
										}
									}
								}
							}
						})
					})
				</script>
				<?php
			}
		}

		?>
		<script>
			$(function(){
				var module = <?=$this->framework->getJavascriptModuleObjectName()?>;
				var log = function(message, linkText, popupIndex){
					var data = {
						'link text': linkText
					}

					if(popupIndex !== undefined){
						data['popup index'] = popupIndex
					}

					module.log(message, data)
				}

				var setup = function(){
					module.initializers.forEach(function(initializer){
						initializer()
					})

					$('a[popup]').each(function(popupIndex) {
						var link = this
						var linkText = $(link).data('link-text')

						$(link).click((e) => {
							const enhancedChoice = e.target.closest('.enhancedchoice')
							if(enhancedChoice){
								// Clicking popups inside enhanced choice options requires a little extra help to select the option.
								enhancedChoice.querySelector('label').click()
							}

							return false
						})

						tippy(link, {
							html: '#inline-popup-content-' + $(this).attr('popup'),
							trigger: 'mouseenter',
	//						trigger: 'click',
							hideOnClick: false,
							theme: 'light inline-popups',
							arrow: true,
							interactive: true,
							onShow: function(){
								log('popup opened', linkText, popupIndex)
							},
							onHide: function(){
								log('popup closed', linkText, popupIndex)
							}
						})
					})
				}

				module.afterMultiLanguageInitialized(function(){
					setup()

					// We have to set them up again whenever the language changes, on both surveys and data entry forms.
					$(document).on('click', '.mlm-switcher button', setup)
					$(document).on('click', '#LanguageDropDownDiv a', setup)					

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
							}
						})
					})

					$(document).on('click', listenButtonSelector, function(e){
						e.preventDefault()

						var popupInner = $(this).closest('.inline-popup-content-inner')
						var linkText = popupInner.data('link-text')
						var useOddcast = popupInner.data('use-oddcast')

						log('listen button clicked', linkText)

						if(useOddcast && window.OddcastAvatarExternalModule && OddcastAvatarExternalModule.isEnabled()){
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
									var audio = $('<audio src="' + url + '">')
									$('body').append(audio) // Chrome seems to require the element to be added to the DOM before we can play it.
									audio[0].play()
								}
							}
							else{
								alert("The audio could not be played.  Please report this issue and/or check the console log for errors.")
							}
						}
					})
				})
			})
		</script>
		<?php
	}

	public function validateSettings($settings){
		if($this->getProjectId() === null){
			// We're saving system settings
			return null;
		}

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

		/**
		 * Avoid injection vulnerabilities in error messages.
		 */
		$word = htmlentities($word, ENT_QUOTES);

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
