<?php

namespace ColdTrick\OEmbed;

use Elgg\DefaultPluginBootstrap;

class Bootstrap extends DefaultPluginBootstrap {
	
	/**
	 * {@inheritDoc}
	 */
	public function init() {
		
		// plugin hooks
		$hooks = $this->elgg()->hooks;
		$hooks->registerHandler('view_vars', 'output/longtext', __NAMESPACE__ . '\Longtext::process', 9999);
	}
}
