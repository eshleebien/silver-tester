<?php

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Psr\Http\ResponseInterface;

class OauthTest extends TestCase
{
    protected $client;
    private $cookie_jar;

    public function setUp()
    {
        $this->cookie_jar = new CookieJar;

        $selector = 'input._token';
        $this->client = new Client(
            [
                'base_uri' => $this->base_url,
                'cookies' => $this->cookie_jar,
                'allow_redirects' => true,
                'verify' => false,
            ]
        );

        $response = $this->client->get('/login', ['cookies' => $this->cookie_jar]);

        $csrf = getFormToken($response->getBody()->read(1000));
        $this->credentials['_token'] = $csrf;

        $response = $this->client->post(
            '/login', [
                'form_params' => $this->credentials,
                'cookies' => $this->cookie_jar
            ]
        );
    }

    public function testAuthUrl()
    {
        $param = [
            'response_type' => 'code',
            'type' => 'existing'
        ];

        $params = array_merge($param, $this->data);
        unset($params['client_secret']);

        $response = $this->client->get(
            '/oauth/authorize', [
                'query' => $params,
                'cookies' => $this->cookie_jar
            ]
        );

        $this->assertEquals(200, $response->getStatusCode());

        $content = $response->getBody()->read(5000);
        $csrf = getFormToken($content);

        $params['approve'] = 'ACCEPT';
        $params['_token'] = $csrf;
        $params['state'] = '';

        $response = $this->client->post(
            '/oauth/authorize', [
                'cookies' => $this->cookie_jar,
                'form_params' => $params,
                'http_errors' => false,
                'allow_redirects' => false

            ]
        );

        $callback_uri = $response->getHeaders()['Location'][0];
        $this->assertContains('code=', $callback_uri);
        $this->assertContains($params['redirect_uri'], $callback_uri);

        $code = getCodeFromUri($callback_uri);

        return $code;
    }

    /**
     * @depends testAuthUrl
     */
    public function testAccessTokenGeneration($code)
    {
        $params = $this->data;
        $params['grant_type'] = 'authorization_code';
        $params['code'] = $code;

        $response = $this->client->post(
            '/oauth/access_token', [
                'cookies' => $this->cookie_jar,
                'form_params' => $params,
                'http_errors' => false,
                'allow_redirects' => false
            ]
        );

        $body = $response->getBody()->read(1000);
        $token = json_decode($body, true);

        $this->assertContains('refresh_token', $body);
        $this->assertContains('token_type', $body);
        $this->assertContains('expires', $body);
        $this->assertContains('expires_in', $body);
        $this->assertArrayHasKey('access_token', $token);

        return $token;
    }

    /**
     * @depends testAccessTokenGeneration
     */
    public function testAccessTokenUsage($token)
    {
        $params = [
            'access_token' => $token['access_token']
        ];

        $response = $this->client->get(
            '/v1/api/user', [
                'query' => $params
            ]
        );

        $body = $response->getBody()->read(1000);
        $user = json_decode($body, true);

        $this->assertArrayHasKey('first_name', $user);
        $this->assertArrayHasKey('email', $user);

    }
}
