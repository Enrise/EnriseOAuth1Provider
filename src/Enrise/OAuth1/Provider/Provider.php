<?php
/**
 * Enrise OAuth1Provider  (http://enrise.com/)
 *
 * @link      https://github.com/Enrise/EnriseOAuth1Provider for the canonical source repository
 * @copyright Copyright (c) 2012 Dolf Schimmel - Freeaqingme (dolfschimmel@gmail.com)
 * @copyright Copyright (c) 2012 Enrise (www.enrise.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Enrise\OAuth1\Provider;

use Enrise\OAuth1\Request as OAuthRequest;
use Zend\Http\Request as HttpRequest;

class Provider
{
    protected $timeBandwidth = 300;

    protected $curTime;

    public function validate(OAuthRequest $request, $secret)
    {
        if (!$this->checkNonce($request)) {
            throw new \RuntimeException('Invalid nonce supplied');
        }

        if (!$this->checkTimestamp($request)) {
            throw new \RuntimeException('Invalid timestamp supplied');
        }

        $method = $request->getSignatureMethod();
        if (!($request->getSignature() == $method->buildSignature($request, $secret, null))) {
            throw new \RuntimeException('Invalid signature supplied');
        }
    }

    public function parseHttpRequest(HttpRequest $httpRequest)
    {
        $header = $httpRequest->getHeader('Authorization');
        if ($header) {
            $values = $this->parseHeaderValues($header);
        } else {
            $values = $httpRequest->getQuery()->toArray();
        }

        $values = array_merge($values, array('url' => $httpRequest->getUri(),
                                             'request_method' => $httpRequest->getMethod(),
                                             'query_string' => $httpRequest->getQuery()));

        $oauthRequest = new OAuthRequest(); //@todo use SM ?
        $hydrator = new OAuthRequest\Hydrator(); //@todo use SM ?

        $hydrator->hydrate($values, $oauthRequest);
        return $oauthRequest;
    }

    protected function checkTimestamp(OAuthRequest $request) {
        if (!$request->getTimestamp() || $request->getTimestamp() < 1352671376) {
            throw new \RuntimeException('Illegal timestamp supplied');
        }

        return abs($this->getCurrentTime() - $request->getTimestamp()) < $this->getTimeBandwidth();
    }

    protected function checkNonce(OAuthRequest $request) {
        if (!$request->getNonce()) {
            throw new \Exception('Illegal Nonce supplied');
        }

        //@todo nonce check callback
        return true;
    }

    /**
     * @param $timeBandwidth in seconds
     */
    public function setTimeBandwidth($timeBandwidth)
    {
        $this->timeBandwidth = (int) $timeBandwidth;
    }

    public function getTimeBandwidth()
    {
        return $this->timeBandwidth;
    }

    /**
     * Used for unit tests only
     * @param $curTime
     */
    public function setCurrentTime($curTime)
    {
        $this->curTime = $curTime;
    }

    public function getCurrentTime()
    {
        if ($this->curTime) {
            return $this->curTime;
        }

        return time();
    }


}
