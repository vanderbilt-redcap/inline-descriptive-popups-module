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
		?>
		<style>
			a[tooltip]:hover{
				font-size: 14px;
			}
		</style>
		<?php

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

			a[tooltip]{
				cursor: pointer;
			}
		</style>
		<?php

		$projectSettings = ExternalModules::getProjectSettingsAsArray($this->PREFIX,$project_id);

		$number = 1;
		foreach($projectSettings['link-text']['value'] as $instanceKey => $linkText) {
			if($projectSettings[$enabledSettingName]['value'][$instanceKey] != 1){
				continue;
			}

			$text = $projectSettings['text']['value'][$instanceKey];

			if(!empty($linkText) && !empty($text)) {
				?>
				<div id="popup-content-<?=$number?>" style="display: none"><?=$text?></div>
				<script>
					$(function(){
						$('.labelrc').each(function(){
							var label = $(this)

							if(label.closest('tr.surveysubmit').length != 0){
								// Don't replace the survey buttons (they might have associated event handlers).
								return
							}

							label.html(label.html().replace(/<?=preg_quote($linkText)?>/g, "<a tooltip='<?=$number?>'>" + <?=json_encode($linkText)?> + "</a>"))
						})
					})
				</script>
				<?php
			}

			$number++;
		}

		?>
		<script>
			$(function(){
				// The Tippy calls cannot be inside the same loop as the replace calls, because replacing elements undoes any previous Tippy calls.
				$('a[tooltip]').each(function() {
					new Tippy(this, {
						html: 'popup-content-' + $(this).attr('tooltip'),
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