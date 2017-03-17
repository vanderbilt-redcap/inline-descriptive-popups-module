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
	static $jsIncluded = false;

	function hook_data_entry_form($project_id) {
		$this->includeJavascript();

		$projectSettings = ExternalModules::getProjectSettingsAsArray($this->PREFIX,$project_id);

//		var_dump($projectSettings);

		echo '<div style="display:none" id="popupDisplayModal"></div>';

		foreach($projectSettings["on-data-entry-forms"]['value'] as $instanceKey => $displayOnSurvey) {
			if($displayOnSurvey) {
				$this->generateFormPopup($project_id,$instanceKey);
			}
		}
	}

	function hook_survey_page($project_id) {
		$this->includeJavascript();

		$projectSettings = ExternalModules::getProjectSettingsAsArray($this->PREFIX,$project_id);

//		var_dump($projectSettings);

		echo '<div style="display:none" id="popupDisplayModal"></div>';

		foreach($projectSettings['on-surveys']['value'] as $instanceKey => $displayOnForm) {
			if($displayOnForm) {
				$this->generateFormPopup($project_id,$instanceKey);
			}
		}
	}

	function generateFormPopup($project_id,$instanceKey) {
		$projectSettings = ExternalModules::getProjectSettingsAsArray($this->PREFIX,$project_id);

		$field = $projectSettings['name']['value'][$instanceKey];
		$textLocation = $projectSettings['location']['value'][$instanceKey];
		$text = $projectSettings['text']['value'][$instanceKey];

		if($field && $textLocation && $text) {
			echo "<script type='text/javascript'>
				ExternalModules.popupTexts.push('".htmlspecialchars($text,ENT_QUOTES)."');
				ExternalModules.popupLocations.push('".htmlspecialchars($textLocation,ENT_QUOTES)."');
				ExternalModules.popupFields.push('".htmlspecialchars($field,ENT_QUOTES)."');
			</script>";
		}
	}

	function includeJavascript() {
		if(!self::$jsIncluded) {
			ExternalModules::addResource('js/globals.js');

			echo "Start len: ".strlen(__DIR__)." small size: ".strlen(dirname(dirname(__DIR__)))."<br />";

			$moduleFolder = substr(__DIR__,strlen(dirname(dirname(__DIR__))));
			$moduleUrl = APP_PATH_WEBROOT_FULL."/".$moduleFolder;
			echo "<script src='".$moduleUrl."/js/addPopup.js'></script>";

			self::$jsIncluded = true;
		}
	}
}