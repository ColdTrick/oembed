<?php

namespace ColdTrick\OEmbed;

class Longtext {
	
	/**
	 * Process oEmbed URLs in output/longtext
	 *
	 * This hook is registered on a high priority because it changes the 'sanitize' value because of filtering issues with iframes
	 *
	 * @param \Elgg\Hook $hook 'view_vars', 'output/longtext'
	 *
	 * @return void|array
	 */
	public static function process(\Elgg\Hook $hook) {
		
		$vars = $hook->getValue();
		if (!(bool) elgg_extract('oembed', $vars, true)) {
			return;
		}
		
		$value = elgg_extract('value', $vars);
		if (empty($value)) {
			return;
		}
		
		if (elgg_extract('sanitize', $vars, true)) {
			// apply filter_tags before embed replacement to allow iframes
			$value = filter_tags($value);
		}
		$vars['sanitize'] = false;
		
		try {
			$processor = Process::create($value);
			$vars['value'] = $processor->parseText();
		} catch (\InvalidArgumentException $e) {
			// non text value passed to Proccessor
		}
		
		return $vars;
	}
}
