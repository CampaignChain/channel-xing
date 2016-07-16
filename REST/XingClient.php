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

use Symfony\Component\HttpFoundation\Session\Session;
use Guzzle\Http\Client;
use Guzzle\Plugin\Oauth\OauthPlugin;

class XingClient
{
    const RESOURCE_OWNER = 'Xing';
    const BASE_URL   = 'https://api.xing.com/v1';

    protected $container;
    
    protected $client;
    
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

    public function connect($appKey, $appSecret, $accessToken, $tokenSecret){
        try {
            $client = new Client(self::BASE_URL.'/');
            $oauth  = new OauthPlugin(array(
                'consumer_key'    => $appKey,
                'consumer_secret' => $appSecret,
                'token'           => $accessToken,
                'token_secret'    => $tokenSecret,
            ));
            return $client->addSubscriber($oauth);
        }
        catch (ClientErrorResponseException $e) {
            $request = $e->getRequest();
            $response = $e->getResponse();
            print_r($request);
            print_r($response);
        }
        catch (ServerErrorResponseException $e) {
            $request = $e->getRequest();
            $response = $e->getResponse();
            print_r($response);
        }
        catch (BadResponseException $e) {
            $request = $e->getRequest();
            $response = $e->getResponse();
            print_r($response);
        }
        catch(Exception $e){
          print_r($e->getMessage());
        }  
    }

}