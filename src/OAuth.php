<?php

namespace RevoSystems\SageApi;

interface OAuth
{
    public function loginBasic($username, $password, $securityToken);
    public function oAuth2Login($redirect_uri);
    public function refreshToken();
    public function loginCallback($redirect_uri, $code);
    public function setAuthKeys($basics, $extras = []);
}
