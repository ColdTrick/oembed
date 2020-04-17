<?php
namespace Embed\Providers\OEmbed;

use Embed\Http\Url;
use Embed\Adapters\Adapter;

class Microsoftstream extends EndPoint {
	
	protected static $pattern = 'web.microsoftstream.com/video/*';
	
    public static function create(Adapter $adapter) {
        $response = $adapter->getResponse();

        if ($response->getStartingUrl()->match(static::$pattern)) {
            return new static($response);
        }
    }
	
	public function getEndPoint() {
		$url = Url::create('https://web.microsoftstream.com/oembed');

		return $url->withAddedQueryParameters([
			'url' => (string) $this->response->getStartingUrl(),
		]);
	}
}
