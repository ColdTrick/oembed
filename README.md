# oEmbed

![Elgg 4.3](https://img.shields.io/badge/Elgg-4.3-green.svg)
[![Build Status](https://scrutinizer-ci.com/g/ColdTrick/oembed/badges/build.png?b=master)](https://scrutinizer-ci.com/g/ColdTrick/oembed/build-status/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ColdTrick/oembed/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/ColdTrick/oembed/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/coldtrick/oembed/v/stable.svg)](https://packagist.org/packages/coldtrick/oembed)
[![License](https://poser.pugx.org/coldtrick/oembed/license.svg)](https://packagist.org/packages/coldtrick/oembed)

Provides oEmbed support for URLs in text

## Features

- This plugin tries to replace URLs in `output/longtext` with embed code. For example if you paste a YouTube URL it will be 
replaced by the embed code for that URL.
- Nothing is done to the original text, so if the plugin is disabled all original links are still present.
- In order to control which URLs should be replace there is a plugin setting for a whitelist and a blacklist. If the whitelist is set only those 
URLs will be replace and the blacklist is ignored. If only the blacklist is set, every URL except those on the blacklist will be replaced.

## Caching

The results of the oEmbed requests is cached in system cache. This is to increase performance and reusability. If the cache 
is flushed requests will be made again.

## Developers

### Prevent oEmbed

In order to prevent the use of oEmbed on your use of `output/longtext` set a var `'oembed' => false`.

### Modifying valid URL

Before an oEmbed adapter is created based on a valid URL a hook is triggered. This allows last minute changes to the URL (eg. adding
vaidation tokens).
The hook is `replace_url`, `oembed`. The return value should be an URL. in the `$params` you get the original url under `url`.

### Output views

To change the output of the oEmbed code the different types have their own view `oembed/type/{$oembed_type}`.
