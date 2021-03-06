<?php
/**
 *
 *
 *
 * Allow to easily connect an user
 *
 * @author Maxime Picaud
 * @since 21 août 2010
 */
class sfMelodyUser extends sfGuardSecurityUser
{
  protected $tokens;

  /**
   *
   * @param string $service
   * @param boolean $force
   *
   * connect to a service. if already connected redirect to the callback
   *
   * @author Maxime Picaud
   * @since 21 août 2010
   */
  public function connect($service, $force = false)
  {
    $oauth = sfMelody::getInstance($service);

    if(!$this->isConnected($service) || $force)
    {
      $this->removeTokens($service, Token::STATUS_REQUEST);
      $this->removeTokens($service, Token::STATUS_ACCESS);

      $this->setAttribute('callback_'.$service, $oauth->getCallback());
      $oauth->setCallback('@melody_access?service='.$service);
      $oauth->connect($this);
    }
    else
    {
      $oauth->getController()->redirect($oauth->getCallback());
    }
  }

  /**
   *
   * @param string $service
   *
   * check if the user is connected to the service
   *
   * @return boolean
   *
   * @author Maxime Picaud
   * @since 21 août 2010
   */
  public function isConnected($service)
  {
    $token = $this->getToken($service, Token::STATUS_ACCESS);

    return !is_null($token) && $token->isValidToken();
  }

  /**
   *
   * @param string $service
   * @param string $status
   * @param boolean $remove_in_session
   *
   * get a token from a service
   *
   * @author Maxime Picaud
   * @since 21 août 2010
   */
  public function getToken($service, $status = Token::STATUS_ACCESS, $in_sesssion = false, $remove_in_session = false)
  {
    $tokens = $this->getTokens();
    if(is_array($tokens) && count($tokens) > 0 && !$in_sesssion)
    {
      if(!is_null($status))
      {
        return isset($tokens[$status][$service])?$tokens[$status][$service]:null;
      }
      else
      {
        foreach(sfMelody::getTokenStatuses() as $status)
        {
          if(isset($tokens[$status][$service]))
          {
            return $tokens[$status][$service];
          }
        }
      }

      return null;
    }
    else
    {
      return $this->getSessionToken($service, $status, $remove_in_session);
    }
  }

  /**
   *
   * @param string $service
   * @param string $status
   * @param boolean $remove
   *
   * get token from the session
   *
   * @author Maxime Picaud
   * @since 21 août 2010
   */
  protected function getSessionToken($service, $status, $remove = false)
  {
    $token = $this->getAttribute($service.'_'.$status.'_token');

    if($remove && $token)
    {
      $this->removeTokens($service, $status);
    }

    if($token)
    {
      return unserialize($token);
    }
    else
    {
      return null;
    }
  }

  /**
   *
   *
   *
   * getTokens store in the database
   *
   * TODO in the session too
   *
   * @author Maxime Picaud
   * @since 21 août 2010
   */
  public function getTokens()
  {

    if(is_null($this->tokens) && $this->isAuthenticated())
    {
      $callable = array(sfMelody::getTokenOperationByOrm(), 'findByUserId');

      $tokens = call_user_func($callable, $this->getGuardUser()->getId());

      $this->tokens = array();
      foreach($tokens as $token)
      {
        $this->tokens[$token->getStatus()][$token->getName()] = $token;
      }
    }

    return $this->tokens;
  }

  /**
   *
   * @param string $service
   * @param string $status
   *
   * removeTokens from database or session
   *
   * @author Maxime Picaud
   * @since 21 août 2010
   */
  public function removeTokens($service, $status = Token::STATUS_ACCESS, $in_session = false)
  {

    if(!$this->isAuthenticated() || Token::STATUS_REQUEST == $status || $in_session)
    {
      $this->getAttributeHolder()->remove($service.'_'.$status.'_token');
    }

    if($this->isAuthenticated() && !$in_session)
    {
      sfMelody::deleteTokens($service, $this->getGuardUser(), $status);
    }
  }

  /**
   *
   * @param string $service
   * @param array $config
   * @param $in_session
   *
   * get a melody
   *
   * @author Maxime Picaud
   * @since 21 août 2010
   */
  public function getMelody($service, $config = array(), $in_session = null)
  {
    $token = null;
    if(is_bool($in_session))
    {
      $token = $this->getToken($service, Token::STATUS_ACCESS, $in_session);
    }
    else
    {
      $token = $this->getToken($service, Token::STATUS_ACCESS);
    }

    $config = array_merge(array('token' => $token), $config);

    return sfMelody::getInstance($service, $config);
  }
}