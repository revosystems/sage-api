<?php

namespace RevoSystems\SageApi;

use Illuminate\Http\Response;
use RevoSystems\SageApi\Exceptions\WrongSageAccessTokenException;
use Zttp\Zttp;

class Auth
{
    protected $sageAuthUrl = "";
    protected $client_id;
    protected $client_secret;

    public $access_token;
    public $refresh_token;
    public $instance_url;

    public function __construct($sageAuthUrl, $client_id, $client_secret)
    {
        $this->sageAuthUrl      = $sageAuthUrl;
        $this->client_id        = $client_id;
        $this->client_secret    = $client_secret;
    }

    public function loginBasic($username, $password, $securityToken)
    {
        return $this->parseResponse(Zttp::asFormParams()->post("{$this->sageAuthUrl}/token", [
            "grant_type"    => 'password',
            "client_id"     => $this->client_id,
            "client_secret" => $this->client_secret,
            "username"      => $username,
            "password"      => $password . $securityToken,
        ]));
    }

    public function oAuth2Login($redirect_uri)
    {
        header("Location: {$this->sageAuthUrl}/authorize?response_type=code&client_id={$this->client_id}&redirect_uri={$redirect_uri}");
        exit();
    }

    public function loginCallback($redirect_uri, $code)
    {
        return $this->parseResponse(Zttp::asFormParams()->post("{$this->sageAuthUrl}/token", [
            "grant_type"    => "authorization_code",
            "client_id"     => $this->client_id,
            "client_secret" => $this->client_secret,
            "redirect_uri"  => $redirect_uri,
            "code"          => $code
        ]));
    }

    public function setAuthTokens($access_token, $instance_url, $refresh_token = "")
    {
        $this->access_token     = $access_token;
        $this->instance_url     = $instance_url;
        $this->refresh_token    = $refresh_token;
        return $this;
    }

    private function parseResponse($response)
    {
        if ($response->status() != Response::HTTP_OK) {
            throw new WrongSageAccessTokenException();
        }
        $response = $response->json();
        return $this->setAuthTokens($response["access_token"], $response["instance_url"], $response["refresh_token"] ?? "");
    }

    public function getAuthHeaders()
    {
        return [
            "Authorization" => "Bearer {$this->access_token}", "Content-Type" => "application/json"
        ];
    }

    public function refreshToken()
    {
        return $this->parseResponse(Zttp::asFormParams()->post("{$this->sageAuthUrl}/token", [
            "grant_type"    => 'refresh_token',
            "client_id"     => $this->client_id,
            "client_secret" => $this->client_secret,
            "refresh_token" => $this->refresh_token,
        ]));
    }
}
