<?php
/**
 * Created by PhpStorm.
 * User: mcguffk
 * Date: 3/8/2017
 * Time: 4:39 PM
 */

namespace ExternalModules;
require_once dirname(__FILE__) . '/../../external_modules/classes/ExternalModules.php';
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
		</style>
		<?php

		$linkTexts = $this->getProjectSetting('link-text');
		$texts = $this->getProjectSetting('text');
		$enabledFlags = $this->getProjectSetting($enabledSettingName);

		// This block can be removed once the repeatable-first-value-arrays branch has been merged.
		if(!is_array($linkTexts)){
			$linkTexts = [$linkTexts];
			$texts = [$texts];
			$enabledFlags = [$enabledFlags];
		}

		for($i=0; $i<count($linkTexts); $i++){
			$linkText = $linkTexts[$i];
			$text = $texts[$i];
			$enabledFlag = $enabledFlags[$i];

			if($enabledFlag != 1){
				continue;
			}

			if(!empty($linkText) && !empty($text)) {
				?>
				<div id="popup-content-<?=$i?>" style="display: none">
					<?=$text?>
					<!-- The following div exists to make sure any floated elements at the end of the content are contained within the popup. -->
					<div style="clear:both"></div>
				</div>
				<script>
					$(function(){
						var nodeIterator = document.createNodeIterator($('#form')[0], NodeFilter.SHOW_TEXT, {
							acceptNode: function(node) {
								if(node.parentElement.nodeName == 'SCRIPT' || node.textContent.trim() == ''){
									return NodeFilter.FILTER_REJECT
								}

								return NodeFilter.FILTER_ACCEPT
							}
						})

						var node
						while(node = nodeIterator.nextNode()){
							// We force the font size to match the original text to get around the REDCap behavior where link font size changes on hover (on surveys).
							var fontSize = $(node.parentNode).css('font-size')

							var newContent = node.textContent.replace(/<?=preg_quote($linkText)?>/g, "<a popup='<?=$i?>' style='font-size: " + fontSize + "'>" + <?=json_encode($linkText)?> + "</a>");
							if(newContent != node.textContent){
								// Insert before, then remove.  Using replaceWith() or inserting after causes an infinite loop.
								$(node).before($('<span>' + newContent + '<span>'))
								$(node).remove()
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
						html: 'popup-content-' + $(this).attr('popup'),
						trigger: 'mouseenter',
	//					trigger: 'click',
						theme: 'light',
						arrow: true,
						interactive: true
					})
				})
			})
		</script>
		<?php
	}
}