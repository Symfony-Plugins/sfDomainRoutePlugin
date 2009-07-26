<?php

/*
 * This file is part of the sfDomainRoute package.
 * (c) 2009 Tal Ater <tal@talater.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * sfDomainRoute.
 *
 * Based on original code by Fabien Potencier
 *
 * @package    sfDomainRoute
 * @author     Tal Ater <tal@talater.com>
 */

class sfDomainRoute extends sfRequestRoute
{
  /**
   * Returns true if the URL matches this route, false otherwise.
   *
   * @param   string    $url        The URL
   * @param   array     $context    The context
   *
   * @return array      An array of parameters
   */
  public function matchesUrl($url, $context = array())
  {
    //If method requirement not set, make sure this also works with POST (sfRequestRoute limits it to GET and HEAD)
    if (!isset($this->requirements['sf_method']))
    {
      $this->requirements['sf_method'] = array('get', 'head', 'post');
    }

    if (false === $retval = parent::matchesUrl($url, $context))
    {
      return false;
    }

    //check host requirements
    $hostRequirements = $this->getHostRequirements();
    if (!empty($hostRequirements) && !in_array($context['host'], $hostRequirements))
    {
      return false;
    }

    //get subdomain
    $subDomain = $this->getSubdomain($context);
    if (!empty($subDomain)) {
      $retval['subdomain'] = $subDomain;
    }
    return $retval;
  }

  /**
   * Returns true if the parameters matches this route, false otherwise.
   *
   * @param   mixed     $params     The parameters
   * @param   array     $context    The context
   *
   * @return  Boolean               true if the parameters matches this route, false otherwise.
   */
  public function matchesParameters($params, $context = array())
  {
    unset($params['subdomain']);
    return parent::matchesParameters($params, $context);
  }

  /**
   * Generates a URL from the given parameters.
   *
   * @param   mixed     $params     The parameter values
   * @param   array     $context    The context
   * @param   Boolean   $absolute   Whether to generate an absolute URL
   *
   * @return  string    The generated URL
   */
  public function generate($params, $context = array(), $absolute = false)
  {
    $hostRequirements = $this->getHostRequirements();
    $subdomain = isset($params['subdomain']) ? $params['subdomain'] : null;
    unset($params['subdomain']);
    $url = parent::generate($params, $context, false);
    if ($subdomain && $subdomain != $this->getSubdomain($context))
    {
      return $this->getProtocol($context).$this->getHostForSubdomain($context, $subdomain).$url;
    } elseif (!empty($hostRequirements) && !in_array($context['host'], $hostRequirements)) {
      return $this->getProtocol($context).$hostRequirements[0].$url;
    } else {
      return parent::generate($params, $context, $absolute);
    }
  }

  /**
   * Generates a host for the given subdomain.
   *
   * @param   array     $context    The context
   * @param   string    $subdomain  subdomain name
   *
   * @return  string    The generated host
   *
   * @todo    Currently only supports sub.domain.tld style domains. Modify to support sub.domain.xx.xx
   */
  protected function getHostForSubdomain($context, $subdomain)
  {
    $parts = explode('.', $context['host']);
    $partCount = count($parts);
    if ($partCount>=3)
    {
      $parts[0] = $subdomain;
    }
    $host = implode('.', $parts);
    if ($partCount<3) {
      $host = $subdomain.'.'.$host;
    }
    return $host;
  }

  /**
   * Gets the subdomain from the host.
   *
   * @param   array     $context    The context
   *
   * @return  string    The subdomain part of the host (or null)
   */
  protected function getSubdomain($context)
  {
    $parts = explode('.', $context['host']);
    $partCount = count($parts);
    if ($partCount<3)
    {
      return '';
    } else {
      return $parts[0];
    }
  }

  protected function getHostRequirements()
  {
    if (isset($this->requirements['sf_host']))
    {
      $hostRequirements = $this->requirements['sf_host'];
      if (!is_array($hostRequirements))
      {
        $hostRequirements = array($hostRequirements);
      }
    } else {
      $hostRequirements = array();
    }
    return $hostRequirements;
  }

  protected function getProtocol($context = array())
  {
    return 'http'.(isset($context['is_secure']) && $context['is_secure'] ? 's' : '').'://';
  }

}
?>