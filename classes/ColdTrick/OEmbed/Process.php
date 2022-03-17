<?php

namespace ColdTrick\OEmbed;

use Embed\Http\CurlDispatcher;
use Embed\Http\Url;
use Embed\Adapters\Adapter;
use Embed\Embed;

class Process {
	
	/**
	 * @var string the text to process
	 */
	protected $text;
	
	/**
	 * @var array whitelisted domains
	 */
	protected $whitelist;
	
	/**
	 * @var array blacklisted domains
	 */
	protected $blacklist;
	
	/**
	 * @var CurlDispatcher
	 */
	protected $curldispatcher;
	
	/**
	 * Create a new oEmbed processor
	 *
	 * @param string $text the text to parse
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct($text) {
		
		if (!is_string($text)) {
			throw new \InvalidArgumentException(__METHOD__ . ' needs the text argument to be a string: ' . gettype($text) . ' given');
		}
		
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
	 * @param string $text the text to parse
	 *
	 * @return \ColdTrick\OEmbed\Process
	 */
	public static function create($text) {
		return new static($text);
	}
	
	/**
	 * Parse the text through oEmbed
	 *
	 * Code for getting the correct parts is taken from Linkify
	 *
	 * @see https://github.com/misd-service-development/php-linkify
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
                    $replacement = $this->replaceText($chunks[$i]);
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
	 * Replace oembed urls in the given text
	 *
	 * @param string $text the text to check for URLs
	 *
	 * @return string
	 */
	protected function replaceText($text) {
		
		if (empty($text) || !is_string($text)) {
			return $text;
		}
		
		$pattern = '~(?xi)
              (?:
                (https?://)                        # scheme://
                |                                  #   or
                www\d{0,3}\.                       # "www.", "www1.", "www2." ... "www999."
                |                                  #   or
                www\-                              # "www-"
                |                                  #   or
                [a-z0-9.\-]+\.[a-z]{2,4}(?=/)      # looks like domain name followed by a slash
              )
              (?:                                  # Zero or more:
                [^\s()<>]+                         # Run of non-space, non-()<>
                |                                  #   or
                \(([^\s()<>]+|(\([^\s()<>]+\)))*\) # balanced parens, up to 2 levels
              )*
              (?:                                  # End with:
                \(([^\s()<>]+|(\([^\s()<>]+\)))*\) # balanced parens, up to 2 levels
                |                                  #   or
                [^\s`!\-()\[\]{};:\'".,<>?«»“”‘’]  # not a space or one of these punct chars
              )
        ~';

        $callback = function ($match) {
            $pattern = "~^https?://~";
			
            if (0 === preg_match($pattern, $match[0])) {
                $match[0] = 'http://' . $match[0];
            }
            
            $replacement = $this->replaceUrl($match[0]);
            if (empty($replacement)) {
            	// nothing was replaced
            	return $match[0];
            }
            
            return $replacement;
        };

        return preg_replace_callback($pattern, $callback, $text);
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
		
		$url = elgg_trigger_plugin_hook('replace_url', 'oembed', ['url' => $url], $url);
		
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
			$dispatcher = $this->getCurlDispatcher();
			
			$adapter = Embed::create($url, [], $dispatcher);
		} catch (\Exception $e) {
			$adapter = null;
			elgg_log($e->getMessage());
		}
		
		$this->cacheAdapter($url, $adapter);
		
		if (!$adapter instanceof Adapter) {
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
		
		if ($adapter instanceof Adapter) {
			// the htmlContent DOM Document in the Response is not serializable, this is removed silently in PHP < 8.0 but crashes if on PHP 8+
			$response = $adapter->getResponse();
			
			$reflectionClass = new \ReflectionClass($response);
			$reflectionProperty = $reflectionClass->getProperty('htmlContent');
			$reflectionProperty->setAccessible(true);
			$reflectionProperty->setValue($response, null); // removes the htmlContent data
		} else {
			$adapter = false;
		}
        		
		return elgg_save_system_cache($cache_name, serialize($adapter));
	}
	
	/**
	 * Get a special cURL dispatcher with proxy support
	 *
	 * @return void|CurlDispatcher
	 */
	protected function getCurlDispatcher() {
		
		if (isset($this->curldispatcher)) {
			if (empty($this->curldispatcher)) {
				return;
			}
			
			return $this->curldispatcher;
		}
		
		$proxy_config = elgg_get_config('proxy');
		if (empty($proxy_config) || !is_array($proxy_config)) {
			return;
		}
		
		$host = elgg_extract('host', $proxy_config);
		if (empty($host)) {
			return;
		}
		
		$curl_settings = [
			CURLOPT_PROXY => $host,
		];
		
		$port = (int) elgg_extract('port', $proxy_config);
		if (($port > 0) && ($port <= 65536)) {
			$curl_settings[CURLOPT_PROXYPORT] = $port;
		}
		
		if (!(bool) elgg_extract('verify_ssl', $proxy_config, true)) {
			$curl_settings[CURLOPT_SSL_VERIFYHOST] = false;
		}
		
		$username = elgg_extract('username', $proxy_config);
		$password = elgg_extract('passowrd', $proxy_config);
		if (!empty($username) && !empty($password)) {
			$curl_settings[CURLOPT_PROXYUSERPWD] = "{$username}:{$password}";
		}
		
		$this->curldispatcher = new CurlDispatcher($curl_settings);
		return $this->curldispatcher;
	}
}
