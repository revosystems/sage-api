<?php

namespace RevoSystems\SageApi;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Zttp\Zttp;
use Zttp\ZttpResponse;

class Api
{
    public $log      = [];
    public $auth;

    const PATCH_METHOD = 'patch';

    public function __construct(Auth $auth)
    {
        $this->auth         = $auth;
    }

    public function find($resource, $id)
    {
        $response = $this->call('get', $this->urlForResource("{$resource}/{$id}"), $this->auth->getAuthHeaders());
        return $response instanceof ZttpResponse ? $response->json() : null;
    }

    public function get($resource, $fields = ["Id", "Name"], $query = '')
    {
        return Zttp::withHeaders($this->auth->getAuthHeaders())
            ->get($this->urlForQueries() . "?q=SELECT+" . $this->getCollection($fields) . "+from+{$resource}+WHERE+isDeleted+=+false{$query}")
            ->json();
    }

    public function post($resource, $data)
    {
        $data     = $data instanceof Collection ? $data->toArray() : $data;
        $response = $this->call('post', $this->urlForResource($resource), $data);
        return $response instanceof ZttpResponse ? $response->json()["id"] : '';
    }

    public function patch($resource, $id, $data)
    {
        $data     = $data instanceof Collection ? $data->toArray() : $data;
        $response = $this->call(static::PATCH_METHOD, $this->urlForResource("{$resource}/{$id}"), $data);
        return $response instanceof ZttpResponse ? $response->json() : false;
    }

    public function delete($resource, $id)
    {
        return $this->call('delete', $this->urlForResource("{$resource}/{$id}"));
    }

    protected function call($method, $url, $data = null)
    {
        $response = Zttp::withHeaders($this->auth->getAuthHeaders())->$method($url, $data);
        $status   = $response->status();
        if ($status == Response::HTTP_UNAUTHORIZED) {
            $this->auth->refreshToken();
            return $this->call($method, $url, $data);
        } elseif ($status < Response::HTTP_OK || $status > Response::HTTP_NO_CONTENT) {
            $this->log("SAGE: Failed to {$method} resource with error {$status}: {$response->body()}");
            return false;
        }
        return $response;
    }

    protected function urlForResource($resource)
    {
        return "{$this->auth->instance_url}/services/data/v40.0/sobjects/{$resource}";
    }

    protected function urlForQueries()
    {
        return "{$this->auth->instance_url}/services/data/v40.0/query/";
    }

    protected function log($message)
    {
        array_push($this->log, $message);
    }

    protected function getCollection($fields)
    {
        return ($fields instanceof Collection ? $fields->keys() : collect($fields))->implode(',');
    }
}
