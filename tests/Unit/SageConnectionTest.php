<?php

namespace Tests\Unit;

use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;
use RevoSystems\SageApi\Auth;
use RevoSystems\SageApi\Api;

class SageConnectionTest extends TestCase
{
    protected $api;

    public function setUp()
    {
        parent::setUp();
        $this->loadEnv();
        $this->api = $this->getSageApi();
    }

    public function getSageApi()
    {
        if (! $this->api) {
            $this->api = new Api(new Auth(getenv('CLIENT_ID'), getenv('CLIENT_SECRET')));
        }
        return $this->api;
    }

    protected function sageLogin()
    {
        return $this->api->auth->loginBasic(getenv('TEST_SAGE_USERNAME'), getenv('TEST_SAGE_PASSWORD'), getenv('TEST_SAGE_SECURITY_TOKEN'));
    }

    /**
     * @return array
     */
    private function loadEnv()
    {
        return (new Dotenv(__DIR__, "../../.env"))->load();
    }

    /** @test */
    public function can_get_oauth_access_token_from_sage_live()
    {
        $this->api->auth->loginBasic(getenv('TEST_SAGE_USERNAME'), getenv('TEST_SAGE_PASSWORD'), getenv('TEST_SAGE_SECURITY_TOKEN'));
        $this->assertNotNull($this->api->auth->access_token);
    }
}
