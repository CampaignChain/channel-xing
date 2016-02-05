<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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