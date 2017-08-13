<?php
/*
 * Copyright 2016 CampaignChain, Inc. <info@campaignchain.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace CampaignChain\Channel\XingBundle\REST;

use CampaignChain\CoreBundle\Exception\ExternalApiException;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Subscriber\Oauth\Oauth1;

class XingClient
{
    const RESOURCE_OWNER = 'Xing';
    const BASE_URL   = 'https://api.xing.com/v1/';

    protected $container;

    /** @var  Client */
    protected $client;

    protected $headers;
    
    public function setContainer($container)
    {
        $this->container = $container;
    }
    
    public function connectByActivity($activity){
        return $this->connectByLocation($activity->getLocation());
    }
    
    public function connectByLocation($location){
        $oauthApp = $this->container->get('campaignchain.security.authentication.client.oauth.application');
        $application = $oauthApp->getApplication(self::RESOURCE_OWNER);
        
        $oauthToken = $this->container->get('campaignchain.security.authentication.client.oauth.token');
        $token = $oauthToken->getToken($location);
        return $this->connect($application->getKey(), $application->getSecret(), $token->getAccessToken(), $token->getTokenSecret());
    }

    public function connect($appKey, $appSecret, $accessToken, $tokenSecret)
    {
        try {
            $stack = HandlerStack::create();

            $oauth = new Oauth1(
                [
                    'consumer_key'      => $appKey,
                    'consumer_secret'   => $appSecret,
                    'token'             => $accessToken,
                    'token_secret'      => $tokenSecret,
                ]
            );

            $stack->push($oauth);

            $this->client = new Client([
                'base_uri' => self::BASE_URL,
                'handler' => $stack,
                'auth' => 'oauth'
            ]);

            return $this;
        } catch (\Exception $e) {
            throw new ExternalApiException($e->getMessage(), $e->getCode());
        }
    }

    private function request($method, $uri, $body = array())
    {
        try {
            $res = $this->client->request($method, $uri, $body);
            $this->headers = $res->getHeaders();
            return json_decode($res->getBody(), true);
        } catch(\Exception $e){
            throw new ExternalApiException($e->getMessage(), $e->getCode());
        }
    }

    public function postStatusMessage($id, $msg)
    {
        $this->request(
            'POST',
            'users/' . $id . '/status_message',
            array('query' => array('id' => $id, 'message' => $msg))
        );

        $messageEndpoint = $this->headers['Location'][0];
        $messageId = basename($messageEndpoint);

        $response['id'] = $messageId;
        $response['url'] = 'https://www.xing.com/feedy/stories/' . strtok($messageId, '_');

        return $response;
    }

    public function getStatusActivities($id)
    {
        return $this->request('GET', 'activities/' . $id);
    }
}