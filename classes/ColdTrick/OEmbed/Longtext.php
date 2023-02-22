<?php

namespace ColdTrick\OEmbed;

/**
 * Modify output/longtext output
 */
class Longtext {
	
	/**
	 * Process oEmbed URLs in output/longtext
	 *
	 * This hook is registered on a high priority because it changes the 'sanitize' value because of filtering issues with iframes
	 *
	 * @param \Elgg\Event $event 'view_vars', 'output/longtext'
	 *
	 * @return void|array
	 */
	public static function process(\Elgg\Event $event) {
		
		$vars = $event->getValue();
		if (!(bool) elgg_extract('oembed', $vars, true)) {
			return;
		}
		
		$value = elgg_extract('value', $vars);
		if (empty($value)) {
			return;
		}
		
		if (elgg_extract('sanitize', $vars, true)) {
			// apply sanitization before embed replacement to allow iframes
			$value = elgg_sanitize_input($value);
		}
		
		$vars['sanitize'] = false;
		
		try {
			$processor = Process::create($value);
			$vars['value'] = $processor->parseText();
		} catch (\InvalidArgumentException $e) {
			// non text value passed to Processor
		}
		
		return $vars;
	}
}
