<?php
namespace ColdTrick\OEmbed\Adapters\MicrosoftStream;

use Embed\Extractor as Base;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Embed\Http\Crawler;

class Extractor extends Base {
	
	public function __construct(UriInterface $uri, RequestInterface $request, ResponseInterface $response, Crawler $crawler) {
        parent::__construct($uri, $request, $response, $crawler);

        $this->oembed = new OEmbed($this);
    }
}
