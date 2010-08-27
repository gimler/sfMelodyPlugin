<?php

/**
 *
 * @package    sfMelodyPlugin
 * @subpackage module
 * @author     Maxime Picaud
 */
class BasesfMelodyActions extends sfActions
{
  public function executeAccess(sfWebRequest $request)
  {
    $service = $request->getParameter('service');
    $token = $this->getUser()->getToken($service, Token::STATUS_REQUEST, true);

    $oauth = sfMelody::getInstance($service, array('token' => $token));

    if($oauth->getVersion() == 1)
    {
      $code = $request->getParameter('oauth_verifier');
    }
    else
    {
      $code = $request->getParameter('code');
    }

    // save final callback for redirect
    $callback = $oauth->getCallback();

    // for oauth 2 the same redirect_uri
    $oauth->setCallback('@melody_access?service='.$service);

    $access_token = $oauth->getAccessToken($code);

    if($this->getUser()->isAuthenticated())
    {
      $access_token->setUser($this->getUser()->getGuardUser());
    }
    else
    {
      // we looking for an existing token
      $old_token = sfMelody::execute('findOneByNameAndIdentifier', array($service, $oauth->getIdentifier()));

      if($old_token)
      {
        $access_token->setUserId($old_token->getUserId());

        $old_token->delete();
      }
      else
      {
        $create_user = sfConfig::get('app_melody_create_user', true);

        // if provider config has create_user value override global config
        $config = sfConfig::get('app_melody_'.$service);
        if (isset($config['create_user']))
        {
          $create_user = $config['create_user'];
        }

        if($create_user)
        {
          $username = sfInflector::classify($service).'_'.$oauth->getIdentifier();
          // create a new user
          $user = new sfGuardUser();
          $user->setUsername($username);

          $user->save();

          $access_token->setUser($user);

          // logged in the new user
          $this->getUser()->signin($user, sfConfig::get('app_melody_remember_user', true));
        }
      }
    }

    if($this->getUser()->isAuthenticated())
    {
      if($access_token->isValidToken())
      {
        $access_token->save();
      }
    }
    else
    {
      $this->getUser()->setAttribute($service.'_'.Token::STATUS_ACCESS.'_token', serialize($access_token));
    }

    $this->getUser()->removeTokens($service, Token::STATUS_REQUEST, true);

    $this->redirect($callback);
  }
}