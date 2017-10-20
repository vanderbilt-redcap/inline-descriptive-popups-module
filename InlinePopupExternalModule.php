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
			$linkText = $linkTexts[$i];
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
								var nodeIterator = document.createNodeIterator(currentItem, NodeFilter.SHOW_TEXT, {
									acceptNode: function(node) {
										if(node.parentElement.nodeName == 'SCRIPT' || node.textContent.trim() == '' || !$(node.parentElement).is(":visible")){
											return NodeFilter.FILTER_REJECT
										}

										return NodeFilter.FILTER_ACCEPT
									}
								});
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

								var findString
								if(firstMatchOnly){
									findString = linkText
								}
								else{
									findString = /([^a-zA-Z]|^)(<?=preg_quote($linkText)?>)([^a-zA-Z]|$)/g
								}

								var newContent = node.textContent.replace(findString, "$1<a popup='<?=$i?>' style='font-size: " + fontSize + "'>" + linkText + "</a>$3");
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
					var linkText = $(this).closest('.inline-popup-content-inner').data('link-text')
					$.get(<?=json_encode($this->getUrl('get-audio-filename.php'))?> + '&NOAUTH&word=' + linkText, function(response){
						if(response.error){
							console.log(response.error)
						}
						else{
							filenames[linkText] = response.filename
						}
					})
				})

				$(document).on('click', listenButtonSelector, function(e){
					e.preventDefault()

					var popupInner = $(this).closest('.inline-popup-content-inner')
					var linkText = popupInner.data('link-text')
					var useOddcast = popupInner.data('use-oddcast')

					if(useOddcast){
						if(OddcastAvatarExternalModule){
							OddcastAvatarExternalModule.sayText(linkText)
						}
					}
					else{
						var filename = filenames[linkText]
						if(filename){
							var url = 'http://media.merriam-webster.com/soundc11/' + filename[0] + '/' + filename
							$('<audio src="' + url + '">')[0].play()
						}
					}
				})
			})
		</script>
		<?php
	}
}
