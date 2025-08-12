<?php

namespace ColdTrick\OEmbed;

use Embed\Embed;
use Embed\OEmbed;

/**
 * Process html to detect oEmbed URLs
 */
class Process {
	
	/**
	 * @var array whitelisted domains
	 */
	protected array $whitelist = [];
	
	/**
	 * @var array blacklisted domains
	 */
	protected array $blacklist = [];
	
	/**
	 * Create a new oEmbed processor
	 *
	 * @param string $text      the text to parse
	 * @param array  $view_vars view vars
	 */
	public function __construct(protected string $text, protected array $view_vars = []) {
		$whitelist = elgg_get_plugin_setting('whitelist', 'oembed');
		if (!empty($whitelist)) {
			$whitelist = str_ireplace(PHP_EOL, ',', $whitelist);
			
			$this->whitelist = elgg_string_to_array($whitelist);
		}
		
		$blacklist = elgg_get_plugin_setting('blacklist', 'oembed');
		if (!empty($blacklist)) {
			$blacklist = str_ireplace(PHP_EOL, ',', $blacklist);
			
			$this->blacklist = elgg_string_to_array($blacklist);
		}
	}
	
	/**
	 * Create a new processor
	 *
	 * @param string $text      the text to parse
	 * @param array  $view_vars view vars
	 *
	 * @return \ColdTrick\OEmbed\Process
	 */
	public static function create(string $text, array $view_vars = []): static {
		return new static($text, $view_vars);
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
	public function parseText(): string {
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
				if ($openTag === null) {
					$replacement = $this->replaceText($chunks[$i]);
					if (!empty($replacement)) {
						$chunks[$i] = $replacement;
					}
				}
			} else { // odd numbers are tags
				// Only process this tag if there are no unclosed $ignoreTags
				if ($openTag === null) {
					// Check whether this tag is contained in $ignoreTags and is not self-closing
					if (preg_match('`<(' . implode('|', $ignoreTags) . ').*(?<!/)>$`is', $chunks[$i], $matches)) {
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
	protected function replaceText(string $text): string {
		if (empty($text)) {
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
			if (preg_match('~^https?://~', $match[0]) === 0) {
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
	 * Replace a URL with embed code
	 *
	 * @param string $url the URL to replace
	 *
	 * @return null|string
	 */
	protected function replaceUrl(string $url): ?string {
		if (!$this->validateURL($url)) {
			return null;
		}
		
		$url = elgg_trigger_event_results('replace_url', 'oembed', ['url' => $url], $url);
		
		$oembed = $this->getOEmbed($url);
		if (empty($oembed)) {
			return null;
		}
		
		$vars = $this->view_vars;
		$vars['url'] = $url;
		$vars['oembed'] = $oembed;
		
		return elgg_view('oembed/embed', $vars);
	}
	
	/**
	 * Is this URL allowed for replacement. Checks the white- and blacklist
	 *
	 * @param string $url the URL to check
	 *
	 * @return bool
	 */
	protected function validateURL(string $url): bool {
		if (empty($url)) {
			return false;
		}
		
		if (!empty($this->whitelist)) {
			return in_array(Url::getDomain($url), $this->whitelist);
		}
		
		if (!empty($this->blacklist)) {
			return !in_array(Url::getDomain($url), $this->blacklist);
		}
		
		return true;
	}
	
	/**
	 * Get the embed adapter for a URL
	 *
	 * @param string $url the url to fetch
	 *
	 * @return null|array
	 */
	protected function getOEmbed(string $url): ?array {
		if (empty($url)) {
			return null;
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
		
		return $oembed;
	}
	
	/**
	 * Get an adapter from system cache
	 *
	 * @param string $url the url to get the adapter for
	 *
	 * @return null|array
	 */
	protected function getOEmbedFromCache(string $url): ?array {
		if (empty($url)) {
			return null;
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
	protected function cacheOEmbed(string $url, array $oembed): bool {
		if (empty($url)) {
			return false;
		}
		
		$crypto = elgg_build_hmac($url);
		$cache_name = 'oembed_' . $crypto->getToken();
		
		return elgg_save_system_cache($cache_name, $oembed);
	}
}
