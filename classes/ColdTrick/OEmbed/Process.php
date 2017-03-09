<?php

namespace ColdTrick\OEmbed;

use Embed\Http\Url;
use Embed\Adapters\Adapter;
use Embed\Embed;

class Process {
	
	protected $text;
	protected $whitelist;
	protected $blacklist;
	
	public function __construct($text) {
		$this->text = $text;
		
		$whitelist = elgg_get_plugin_setting('whitelist', 'oembed');
		if (empty($whitelist)) {
			$this->whitelist = [];
		} else {
			$whitelist = str_ireplace(PHP_EOL, ',', $whitelist);
			
			$this->whitelist = string_to_tag_array($whitelist);
		}
		
		$blacklist = elgg_get_plugin_setting('blacklist', 'oembed');
		if (empty($blacklist)) {
			$this->blacklist = [];
		} else {
			$blacklist = str_ireplace(PHP_EOL, ',', $blacklist);
			
			$this->blacklist = string_to_tag_array($blacklist);
		}
	}
	
	/**
	 * Create a new processor
	 *
	 * @param string $text
	 *
	 * @return \ColdTrick\OEmbed\Process
	 */
	public static function create($text) {
		return new self($text);
	}
	
	/**
	 * Parse the text through oEmbed
	 *
	 * @return string
	 */
	public function parseText() {
		
		$ignoreTags = [
			'head',
			'link',
			'a',
			'script',
			'style',
			'code',
			'pre',
			'select',
			'textarea',
			'button',
			'iframe',
			'embed',
		];
		
        $chunks = preg_split('/(<.+?>)/is', $this->text, 0, PREG_SPLIT_DELIM_CAPTURE);
		
        $chunk_count = count($chunks);
        $openTag = null;
		for ($i = 0; $i < $chunk_count; $i++) {
        	
        	if (empty(trim($chunks[$i]))) {
        		continue;
        	}
        	
            if ($i % 2 === 0) { // even numbers are text
                // Only process this chunk if there are no unclosed $ignoreTags
                if (null === $openTag) {
                    $replacement = $this->replaceUrl($chunks[$i]);
                    if (!empty($replacement)) {
                    	$chunks[$i] = $replacement;
                    }
                }
            } else { // odd numbers are tags
                // Only process this tag if there are no unclosed $ignoreTags
                if (null === $openTag) {
                    // Check whether this tag is contained in $ignoreTags and is not self-closing
                    if (preg_match("`<(" . implode('|', $ignoreTags) . ").*(?<!/)>$`is", $chunks[$i], $matches)) {
                        $openTag = $matches[1];
                    }
                } else {
                    // Otherwise, check whether this is the closing tag for $openTag.
                    if (preg_match('`</\s*' . $openTag . '>`i', $chunks[$i], $matches)) {
                        $openTag = null;
                    }
                }
            }
        }
        
		// return new text
		return implode('', $chunks);
	}
	
	/**
	 * Replace an URL with embed code
	 *
	 * @param string $url the URL to replace
	 *
	 * @return void|string
	 */
	protected function replaceUrl($url) {
		
		if (empty($url)) {
			return;
		}
		
		if (!$this->validateURL($url)) {
			return;
		}
		
		$adapter = $this->getAdapter($url);
		if (empty($adapter)) {
			return;
		}
		
		return elgg_view('oembed/embed', [
			'url' => $url,
			'adapter' => $adapter,
		]);
	}
	
	/**
	 * Is this URL allowed for replacement. Checks the white- and blacklist
	 *
	 * @param string $url the URL to check
	 *
	 * @return bool
	 */
	protected function validateURL($url) {
		
		if (empty($url)) {
			return false;
		}
		
		if (!empty($this->whitelist)) {
			return $this->isWhitelisted($url);
		}
		
		if (!empty($this->blacklist)) {
			return !$this->isBlacklisted($url);
		}
		
		return true;
	}
	
	/**
	 * Is the URL domain on the whitelist
	 *
	 * @param string $url the URL to check
	 *
	 * @return bool
	 */
	protected function isWhitelisted($url) {
		
		if (empty($url)) {
			return false;
		}
		
		if (empty($this->whitelist)) {
			return false;
		}
		
		$url_object = Url::create($url);
		$domain = $url_object->getDomain();
		
		return in_array($domain, $this->whitelist);
	}
	
	/**
	 * Is the URL domain on the blacklist
	 *
	 * @param string $url the URL to check
	 *
	 * @return bool
	 */
	protected function isBlacklisted($url) {
		
		if (empty($url)) {
			return false;
		}
		
		if (empty($this->blacklist)) {
			return false;
		}
		
		$url_object = Url::create($url);
		$domain = $url_object->getDomain();
		
		return in_array($domain, $this->blacklist);
	}
	
	/**
	 * Get the embed adapter for an URL
	 *
	 * @param string $url the url to fetch
	 *
	 * @return false|Adapter
	 */
	protected function getAdapter($url) {
		
		if (empty($url)) {
			return false;
		}
		
		$adapter = $this->getAdapterFromCache($url);
		if (!is_null($adapter)) {
			// loaded from cache
			return $adapter;
		}
		
		try {
			$adapter = Embed::create($url);
		} catch (\Exception $e) {
			$adapter = null;
		}
		
		$this->cacheAdapter($url, $adapter);
		
		if (!($adapter instanceof Adapter)) {
			return false;
		}
		
		return $adapter;
	}
	
	/**
	 * Get an adapter from system cache
	 *
	 * @param string $url the url to get the adapter for
	 *
	 * @return void|false|Adapter
	 */
	protected function getAdapterFromCache($url) {
		
		if (empty($url)) {
			return false;
		}
		
		$crypto = elgg_build_hmac($url);
		$cache_name = 'oembed_' . $crypto->getToken();
		
		$cache = elgg_load_system_cache($cache_name);
		if (is_null($cache)) {
			// not is cache or cache is disabled
			return;
		}
		
		return unserialize($cache);
	}
	
	/**
	 * Save an adapter to system cache
	 *
	 * @param string  $url     the url for the adapter
	 * @param Adapter $adapter the adapter to save
	 *
	 * @return bool
	 */
	protected function cacheAdapter($url, Adapter $adapter = null) {
		
		if (empty($url)) {
			return false;
		}
		
		$crypto = elgg_build_hmac($url);
		$cache_name = 'oembed_' . $crypto->getToken();
		
		if (!($adapter instanceof Adapter)) {
			$adapter = false;
		}
		
		return elgg_save_system_cache($cache_name, serialize($adapter));
	}
}
