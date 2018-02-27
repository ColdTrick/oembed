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
	 * @return void|array
	 */
	public static function process($hook, $type, $return_value, $params) {
		
		if (!(bool) elgg_extract('oembed', $return_value, true)) {
			return;
		}
		
		$value = elgg_extract('value', $return_value);
		if (empty($value)) {
			return;
		}
		
		if (elgg_extract('sanitize', $return_value, true)) {
			// apply filter_tags before embed replacement to allow iframes
			$value = filter_tags($value);
		}
		$return_value['sanitize'] = false;
		
		try {
			$processor = Process::create($value);
			$return_value['value'] = $processor->parseText();
		} catch (\InvalidArgumentException $e) {
			// non text value passed to Proccessor
		}
		
		return $return_value;
	}
}
