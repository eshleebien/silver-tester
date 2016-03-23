<?php

require __DIR__ . '/../vendor/autoload.php';

abstract class TestCase extends PHPUnit_Framework_TestCase
{
    protected $data = [
        'client_id' => '',
        'client_secret' => '',
        'redirect_uri' => '',
    ];

    protected $credentials = [
        'email_or_username' => '',
        'password' => '',
    ];

    protected $base_url;

    public function __construct()
    {
        $dotenv = new Dotenv();
        $dotenv->load(__DIR__ . '/../');

        $this->data['client_id'] = getenv('CLIENT_ID');
        $this->data['client_secret'] = getenv('CLIENT_SECRET');
        $this->data['redirect_uri'] = getenv('REDIRECT_URI');

        $this->credentials['email_or_username'] = getenv('FREEDOM_USERNAME');
        $this->credentials['password'] = getenv('FREEDOM_PASSWORD');

        $this->base_url = getenv('FREEDOM_URL');
    }
}
