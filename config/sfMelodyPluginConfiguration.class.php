<?php

/*
 * This file is part of the sfMelodyPlugin package.
 * (c) Gordon Franke <info@nevalon.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * sfMelodyPlugin configuration.
 * 
 * @package    sfMelodyPlugin
 * @subpackage config
 * @author     Gordon Franke <info@nevalon.de>
 */
class sfMelodyPluginConfiguration extends sfPluginConfiguration
{
  /**
   * @see sfPluginConfiguration
   */
  public function initialize()
  {
    if (sfConfig::get('app_melody_routes_register', true) && in_array('sfMelody', sfConfig::get('sf_enabled_modules', array())))
    {
      $this->dispatcher->connect('routing.load_configuration', array('sfMelodyRouting', 'listenToRoutingLoadConfigurationEvent'));
    }
  }
}
