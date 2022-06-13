<?php

namespace ColdTrick\OEmbed\Adapters\MicrosoftStream;

use Embed\OEmbed as Base;
use Psr\Http\Message\UriInterface;

class OEmbed extends Base {
	const ENDPOINT = 'https://web.microsoftstream.com/oembed';

    protected function detectEndpoint(): ?UriInterface {
		$uri = $this->extractor->getUri();
		$queryParameters = $this->getOembedQueryParameters((string) $uri);

		return $this->extractor->getCrawler()->createUri(self::ENDPOINT)->withQuery(http_build_query($queryParameters));
    }
}
