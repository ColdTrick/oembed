<?php

namespace ColdTrick\OEmbed;

/**
 * oEmbed URL helper
 */
class Url {
	
	protected static array $suffixes;
	
	/**
	 * Get the domain of a URL
	 *
	 * @param string $url the URL
	 *
	 * @return string
	 */
	public static function getDomain(string $url): string {
		$host = parse_url($url, PHP_URL_HOST);
		$host = array_reverse(explode('.', $host));
		
		switch (count($host)) {
			case 1:
				return $host[0];
			case 2:
				return $host[1];
			default:
				$tld = $host[1].'.'.$host[0];
				$suffixes = self::getSuffixes();
				
				if (in_array($tld, $suffixes, true)) {
					return $host[2];
				}
				return $host[1];
		}
	}
	
	/**
	 * Get domain suffix
	 *
	 * @return array
	 */
	protected static function getSuffixes(): array {
		if (isset(self::$suffixes)) {
			return self::$suffixes;
		}
		
		self::$suffixes = [];
		
		$vendors = elgg_get_plugin_from_id('oembed')->getPath() . 'vendor';
		if (!file_exists($vendors)) {
			$vendors = \Elgg\Project\Paths::project() . 'vendor';
		}
		
		if (file_exists($vendors . '/embed/embed/src/resources/suffix.php')) {
			self::$suffixes = require $vendors . '/embed/embed/src/resources/suffix.php';
		}
		
		return self::$suffixes;
	}
}
