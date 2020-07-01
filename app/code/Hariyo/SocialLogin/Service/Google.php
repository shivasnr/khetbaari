<?php
namespace Hariyo\SocialLogin\Service;

use OAuth\Common\Consumer\CredentialsInterface;
use OAuth\Common\Http\Client\ClientInterface;
use OAuth\Common\Http\Uri\UriInterface;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\OAuth2\Token\StdOAuth2Token;
use OAuth\Common\Http\Exception\TokenResponseException;
use OAuth\OAuth2\Service\Exception\InvalidAccessTypeException;
use OAuth\Common\Http\Uri\Uri;

class Google
{
    protected $accessType = 'online';

    protected $httpClientFactory;

    const CLIENT_ID = '234401028663-830ivd9r0mntkudgromc11oed5ngncn7.apps.googleusercontent.com';
    const CLIENT_SECRET = 'x_lH8Uu3k53ASfVSBGZGrdT0';  # Read from a file or environmental variable in a real app
    const SCOPE = 'https://www.googleapis.com/auth/userinfo.profile';
    const REDIRECT_URI = 'http://localhost/khetbari';

    public function __construct(
        \Magento\Framework\HTTP\PhpEnvironment\Request $httpClientFactory
    ) {
        $this->httpClientFactory = $httpClientFactory;
    }

    public function setAccessType($accessType)
    {
        if (!in_array($accessType, array('online', 'offline'), true)) {
            throw new InvalidAccessTypeException('Invalid accessType, expected either online or offline');
        }
        $this->accessType = $accessType;
    }

    /**
     * {@inheritdoc}
     */
    protected function parseAccessTokenResponse($responseBody)
    {
        $data = json_decode($responseBody, true);

        if (null === $data || !is_array($data)) {
            throw new TokenResponseException('Unable to parse response.');
        } elseif (isset($data['error'])) {
            throw new TokenResponseException('Error in retrieving token: "' . $data['error'] . '"');
        }

        $token = new StdOAuth2Token();
        $token->setAccessToken($data['access_token']);
        $token->setLifetime($data['expires_in']);

        if (isset($data['refresh_token'])) {
            $token->setRefreshToken($data['refresh_token']);
            unset($data['refresh_token']);
        }

        unset($data['access_token']);
        unset($data['expires_in']);

        $token->setExtraParams($data);

        return $token;
    }

    /**
     * {@inheritdoc}
     */
    public function request($path, $method = 'GET', $body = null, array $extraHeaders = array())
    {
        $path = 'https://accounts.google.com/o/oauth2/v2/auth?response_type=code';
        
        $bodyParams = array(
            'client_id'     => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'redirect_uri'  => self::REDIRECT_URI,
            'grant_type'    => 'authorization_code',
        );

        return parent::request($path, $method, $body, $extraHeaders);
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthorizationEndpoint()
    {
        return new Uri('https://accounts.google.com/o/oauth2/auth?access_type=' . $this->accessType);
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenEndpoint()
    {
        return new Uri('https://accounts.google.com/o/oauth2/token');
    }

   
}