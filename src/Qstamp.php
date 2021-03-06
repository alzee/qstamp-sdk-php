<?php

namespace Alzee\Qstamp;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Dotenv\Dotenv;

class Qstamp
{
    private $UUID;
    private $TOKEN;

    public function __construct($UUID, $TOKEN = null)
    {
        $dotenv = new Dotenv();
        # Some Apache config have trailing slashes
        $dotenv->loadEnv(realpath($_SERVER['DOCUMENT_ROOT']) . '/../.env');

        $this->UUID = $UUID;
        $this->TOKEN = $TOKEN;
    }

    public function getToken($key, $secret)
    {
        $api = "/auth/tToken";
        $query = "?key=$key&secret=$secret";
        $response = $this->request($api . $query);
        return json_decode($response->getContent())->data;
    }

    public function pushApplication($applicationId, $uid, $totalCount = 3, $needCount=0)
    {
        $api = "/application/push";
        $body = [
            'applicationId' => $applicationId,
            'userId' => $uid,
            'totalCount' => $totalCount,
            // 'needCount' => $needCount,
            'uuid' => $this->UUID
        ];
        $response = $this->request($api, $body);
    }

    public function changeMode($mode)
    {
        $api = "/device/model";
        $body = [
            'model' => $mode,
            'uuid' => $this->UUID
        ];
        $response = $this->request($api, $body);
    }

    public function listFingerprints()
    {
        $api = "/finger/list";
        $body = [
            'uuid' => $this->UUID
        ];
        return $this->request($api, $body);
    }

    public function addFingerprint($uid, $username)
    {
        $api = "/finger/add";
        $body = [
            'userId' => $uid,
            'username' => $username,
            'uuid' => $this->UUID
        ];
        $response = $this->request($api, $body);
    }

    public function delFingerprint($uid)
    {
        $api = "/finger/del";
        $body = [
            'userId' => $uid,
            'uuid' => $this->UUID
        ];
        $response = $this->request($api, $body);
    }

    public function idUse($uid, $username)
    {
        $api = "/device/idUse";
        $body = [
            'userId' => $uid,
            'username' => $username,
            'uuid' => $this->UUID
        ];
        $response = $this->request($api, $body);
    }

    public function setSleepTime($min = 30)
    {
        $api="/device/sleep";
        $body = [
            'sleep' => $min,
            'uuid' => $this->UUID
        ];
        $response = $this->request($api, $body);
    }

    public function records()
    {
        $api = "/record/list";
        $body = [
            'uuid' => $this->UUID
        ];
        return $this->request($api, $body);
    }

    public function getUid($username = null)
    {
        $resp = $this->listFingerprints();
        $data = json_decode($resp->getContent(), true)['data'];
        // dump($data);
        if (isset($username)) {
            if (isset($data['list'])) {
                $i = array_search($username, array_column($data['list'], 'fingerUsername'));
                $uid = $data['list'][$i]['fingerUserId'];
            } else {
                $uid = 0;
            }
        } else {
            $uid = (int)$data['total'] + 1;
        }
        return $uid;
    }

    public function getUsername($uid)
    {
        $resp = $this->listFingerprints();
        $data = json_decode($resp->getContent(), true)['data'];
        // dump($data);
        if (isset($data['list'])) {
            $i = array_search($uid, array_column($data['list'], 'fingerUserId'));
            $username = $data['list'][$i]['fingerUsername'];
        } else {
            $username = '';
        }
        return $username;
    }

    public function request($api, $body = null, $method = 'GET')
    {
        $API_URL = $_ENV['QSTAMP_API_URL'];
        $httpClient = HttpClient::create();
        $headers = ["tToken: $this->TOKEN"];

        if (is_null($body)) {
            $payload = [];
        } else {
            $method = 'POST';
            $payload = [
                'headers' => $headers,
                'body' => $body
            ];
        }

        $response = $httpClient->request(
            $method,
            $API_URL . $api,
            $payload
        );

        // $content = $response->getContent();
        return $response;
    }

    public function applicationIdFromWecom($spNo)
    {
        return (substr($spNo, 0, 4) - 2021) . substr($spNo, 4);
    }

    public function applicationIdToWecom($applicationId)
    {
        return (substr($applicationId, 0, 1) + 2021) . substr($applicationId, 1);
    }
}
