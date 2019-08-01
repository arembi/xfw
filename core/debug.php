<?php

namespace Arembi\Xfw\Core;

abstract class Debug {
	public static $log;

	// keys are the notations
	// values: debug message notation , message color
	private static $alertLevels = [
		'default' => ['', '#FFF'],
		'i' => ['[I]', '#C0C'],
		'o' => ['[OK]', '#0C0'],
		'n' => ['[N]', '#0CC'],
		'w' => ['[WARNING]', '#CC0'],
		'f' => ['[FAILED]', '#C60'],
		'e' => ['[ERROR]', '#C00']
	];

	private static $style = '
		div#debugArea{
			width: 40%;
			height: 98%;
			padding: 1% 0 0 1%;
			font-size: 10pt;
			background: rgba(0,0,0,0.9);
			z-index: 1000;
			position: fixed;
			left: 99%;
			top: 0;
			transition: left 0.5s;
			border-left: 1px solid #FFF;
			overflow-y: scroll;
			box-shadow: 0 0 10px #000;
		}
		div#debugArea:hover{
			left: calc(60% - 15px);
		}
		div#debugAreaHeading{
			color: #FFF;
			font-style: italic;
			font-weight: bold;
			margin: 0 0 5px;
			padding: 0 10px;
			border-bottom: 1px solid #FFF;
		}
		div.debugAlert{
			padding: 2px 0;
			cursor: default;
		}
		ul.debugAlertList{
			margin: 0;
			list-style-position: inside;
			cursor: pointer;
		}
		ul.debugAlertList > li{
			list-style-type: none;
			margin: 0 0 0 1em;
			padding: 2px;
		}
		ul.debugAlertList > li:hover{
			background: rgba(255,255,255,0.1);
			border-radius: 5px 0 0 5px;
		}
		';

	private static $alertNo = 1;
	private static $alerts = [];

	public static function init()
	{
	}


	public static function alert($message, $alertLevel = 'default')
	{
		if (Config::_('debugMode') || (isset($_SESSION['debugMode']) && $_SESSION['debugMode'])) {
			self::$alerts[] = [$message, $alertLevel];
		}
	}



	// Shows the HTML output of the debug alerts. HTML will be escaped in the messages
	public static function render()
	{
		if (!Config::_('debugMode') || (defined('IS_LOCALHOST') && !IS_LOCALHOST)) {
			return false;
		}

		$HTML = '
			<style>' . self::$style . '</style>
			<div id="debugArea">
				<div id="debugAreaHeading">Debug Panel</div>';

		foreach (self::$alerts as $alertKey => $alert) {
			$currentAlert = '<div class="debugAlert" style="color:' . self::$alertLevels[$alert[1]][1] . ';">' . ($alertKey + 1 ) . '. ' . self::$alertLevels[$alert[1]][0] . ' ';

			if (is_array($alert[0])) {
				foreach ($alert[0] as $key => &$value) {
					$value = htmlspecialchars($value);
					$value = nl2br($value);
				}
				unset($value);

				$currentAlert .= $alert[0]['debugTitle'];
				unset($alert[0]['debugTitle']);
				$currentAlert .= '<ul class="debugAlertList">';
				foreach ($alert[0] as $key => $value) {
					$currentAlert .= '<li>' . $key . ': ' . $value . '</li>';
				}
				$currentAlert .= '</ul>';
			} else {
				$currentAlert .= htmlspecialchars($alert[0]);
			}

			$currentAlert .= '</div>';

			$HTML .= $currentAlert;
		}

		$HTML .= '</div>
		';

		echo $HTML;
	}

}
