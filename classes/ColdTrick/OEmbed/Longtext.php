<?php

namespace ColdTrick\OEmbed;

class Longtext {
	
	/**
	 * Process oEmbed URLs in output/longtext
	 *
	 * This hook is registered on a high priority because it changes the 'sanitize' value because of filtering issues with iframes
	 *
	 * @param string $hook         the name of the hook
	 * @param string $type         the type of the hook
	 * @param array  $return_value current return value
	 * @param array  $params       supplied params
	 *
	 * @return array
	 */
	public static function process($hook, $type, $return_value, $params) {
		
		$oembed = (bool) elgg_extract('oembed', $return_value, true);
		unset($return_value['oembed']);
		if (!$oembed) {
			return $return_value;
		}
		
		$value = elgg_extract('value', $return_value);
		if (empty($value)) {
			return $return_value;
		}
		
		if (elgg_extract('sanitize', $return_value, true)) {
			// apply filter_tags before embed replacement to allow iframes
			$value = filter_tags($value);
		}
		$return_value['sanitize'] = false;
		
		$processor = Process::create($value);
		$return_value['value'] = $processor->parseText();
		
		return $return_value;
	}
}
