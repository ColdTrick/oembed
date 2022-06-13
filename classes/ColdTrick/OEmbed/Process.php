<?php

namespace ColdTrick\OEmbed;

use Embed\Http\Url;
use Embed\Embed;
use Embed\Extractor;
use Embed\OEmbed;

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
	 * Create a new oEmbed processor
	 *
	 * @param string $text the text to parse
	 */
	public function __construct(string $text) {
		
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
		
		$oembed = $this->getOEmbed($url);
		if (empty($oembed)) {
			return;
		}
		
		return elgg_view('oembed/embed', [
			'url' => $url,
			'oembed' => $oembed,
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
	 * @return false|array
	 */
	protected function getOEmbed(string $url) {
		
		if (empty($url)) {
			return false;
		}
		
		$oembed = $this->getOEmbedFromCache($url);
		if (!is_null($oembed)) {
			// loaded from cache
			return $oembed;
		}
		
		try {
			$embed = new Embed();
			
			// set custom adapters
			$factory = $embed->getExtractorFactory();
			$factory->addAdapter('microsoftstream.com', \ColdTrick\OEmbed\Adapters\MicrosoftStream\Extractor::class);
			
			// extract oembed data
			$oembed = $embed->get($url)->getOEmbed()->all();
		} catch (\Exception $e) {
			$oembed = [];
			elgg_log($e->getMessage());
		}
		
		$this->cacheOEmbed($url, $oembed);

		return is_array($oembed) ? $oembed : false;
	}
	
	/**
	 * Get an adapter from system cache
	 *
	 * @param string $url the url to get the adapter for
	 *
	 * @return void|false|OEmbed
	 */
	protected function getOEmbedFromCache(string $url) {
		if (empty($url)) {
			return false;
		}
		
		$crypto = elgg_build_hmac($url);
		$cache_name = 'oembed_' . $crypto->getToken();
		
		return elgg_load_system_cache($cache_name);
	}
	
	/**
	 * Save oembed data to system cache
	 *
	 * @param string $url    the url for the adapter
	 * @param array  $oembed the oembed data to save
	 *
	 * @return bool
	 */
	protected function cacheOEmbed(string $url, array $oembed) {
		if (empty($url)) {
			return false;
		}
		
		$crypto = elgg_build_hmac($url);
		$cache_name = 'oembed_' . $crypto->getToken();
		        		
		return elgg_save_system_cache($cache_name, $oembed);
	}
}
