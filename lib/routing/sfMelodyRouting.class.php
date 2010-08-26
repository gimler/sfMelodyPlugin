<?php

/*
 * This file is part of the sfMelodyPlugin package.
 * (c) Gordon Franke <info@nevalon.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package    sfMelodyPlugin
 * @subpackage routing
 * @author     Gordon Franke <info@nevalon.de>
 */
class sfMelodyRouting
{
  /**
   * Listens to the routing.load_configuration event.
   *
   * @param sfEvent An sfEvent instance
   * @static
   */
  static public function listenToRoutingLoadConfigurationEvent(sfEvent $event)
  {
    $r = $event->getSubject();

    // preprend our routes
    $r->prependRoute('melody_access', new sfRoute('/access/:service', array('module' => 'sfMelody', 'action' => 'access')));
  }
}
