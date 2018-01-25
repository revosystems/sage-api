<?php

namespace RevoSystems\SageApi;

use Illuminate\Http\Response;
use RevoSystems\SageApi\Exceptions\WrongSageAccessTokenException;
use Zttp\Zttp;

class Auth implements OAuth
{
    public $access_token;
    public $refresh_token;
    public $instance_url;

    protected $authUrl  = "https://login.salesforce.com/services/oauth2/authorize";
    protected $tokenUrl = "https://login.salesforce.com/services/oauth2/token";

    private $client_id;
    private $client_secret;

    public function __construct($client_id, $client_secret)
    {
        $this->client_id        = $client_id;
        $this->client_secret    = $client_secret;
    }

    public function loginBasic($username, $password, $securityToken)
    {
        return $this->parseResponse(Zttp::asFormParams()->post($this->tokenUrl, [
            "grant_type"    => 'password',
            "client_id"     => $this->client_id,
            "client_secret" => $this->client_secret,
            "username"      => $username,
            "password"      => $password . $securityToken,
        ]));
    }

    public function oAuth2Login($redirect_uri)
    {
        header("Location: {$this->authUrl}?response_type=code&client_id={$this->client_id}&redirect_uri={$redirect_uri}");
        exit();
    }

    public function refreshToken()
    {
        return $this->parseResponse(Zttp::asFormParams()->post($this->tokenUrl, [
            "grant_type"    => 'refresh_token',
            "client_id"     => $this->client_id,
            "client_secret" => $this->client_secret,
            "refresh_token" => $this->refresh_token,
        ]));
    }

    public function loginCallback($redirect_uri, $code)
    {
        return $this->parseResponse(Zttp::asFormParams()->post($this->tokenUrl, [
            "grant_type"    => "authorization_code",
            "client_id"     => $this->client_id,
            "client_secret" => $this->client_secret,
            "redirect_uri"  => $redirect_uri,
            "code"          => $code
        ]));
    }

    public function setAuthKeys($basics, $extras = [])
    {
        $this->access_token     = $basics["access_token"];
        $this->refresh_token    = $basics["refresh_token"] ?? "";
        collect($extras)->each(function ($extra, $key) {
            $this->$key = $extra;
        });
        return $this;
    }

    public function getAuthHeaders()
    {
        return [
            "Authorization" => "Bearer {$this->access_token}", "Content-Type" => "application/json"
        ];
    }

    protected function parseResponse($response)
    {
        if ($response->status() != Response::HTTP_OK) {
            throw new WrongSageAccessTokenException();
        }
        return $this->setAuthKeys($response->json(), array_except($response->json(), ["client_id", "client_secret"]));
    }
}
