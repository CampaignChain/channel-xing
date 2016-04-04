<?php

namespace CampaignChain\Channel\XingBundle\Controller;

use CampaignChain\CoreBundle\Entity\Location;
use CampaignChain\Location\XingBundle\Entity\XingUser;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class XingController extends Controller
{
    const RESOURCE_OWNER = 'Xing';
    
    const LOCATION_BUNDLE = 'campaignchain/location-xing';
    
    const LOCATION_MODULE = 'campaignchain-xing';
    
    private $applicationInfo = array(
        'key_labels' => array('key', 'Consumer key'),
        'secret_labels' => array('secret', 'Consumer secret'),
        'config_url' => 'https://dev.xing.com/applications/dashboard',
        'parameters' => array(),
        'wrapper' => array(
            'class'=>'Hybrid_Providers_XING',
            'path' => 'vendor/hybridauth/hybridauth/additional-providers/hybridauth-xing/Providers/XING.php'
        ),        
    );
    
    public function createAction()
    {
        $oauthApp = $this->get('campaignchain.security.authentication.client.oauth.application');
        $application = $oauthApp->getApplication(self::RESOURCE_OWNER);
        if(!$application){
            return $oauthApp->newApplicationTpl(self::RESOURCE_OWNER, $this->applicationInfo);
        }
        else {
            return $this->render(
                'CampaignChainChannelXingBundle:Create:index.html.twig',
                array(
                    'page_title' => 'Connect with Xing',
                    'app_id' => $application->getKey(),
                )
            );
        }
    }
    public function loginAction(Request $request){
        $oauth = $this->get('campaignchain.security.authentication.client.oauth.authentication');
        $status = $oauth->authenticate(self::RESOURCE_OWNER, $this->applicationInfo);
        $profile = $oauth->getProfile();

        if($status){
            try {
                $repository = $this->getDoctrine()->getManager();
                $repository->getConnection()->beginTransaction();
                $wizard = $this->get('campaignchain.core.channel.wizard');
                $wizard->setName($profile->displayName);
                // Get the location module.
                $locationService = $this->get('campaignchain.core.location');
                $locationModule = $locationService->getLocationModule(self::LOCATION_BUNDLE, self::LOCATION_MODULE);
                $location = new Location();
                $location->setIdentifier($profile->identifier);
                $location->setName($profile->displayName);
                $location->setUrl($profile->profileURL);
                $location->setImage($profile->photoURL);
                $location->setLocationModule($locationModule);
                $wizard->addLocation($location->getIdentifier(), $location);
                $channel = $wizard->persist();
                $wizard->end();
                $oauth->setLocation($channel->getLocations()[0]);
                $user = new XingUser();
                $user->setLocation($channel->getLocations()[0]);
                $user->setIdentifier($profile->identifier);
                $user->setDisplayName($profile->displayName);
                $user->setFirstName($profile->firstName);
                $user->setLastName($profile->lastName);
                if (isset($profile->emailVerified)) {
                  $user->setEmail($profile->emailVerified);
                } else {
                  $user->setEmail($profile->email);                
                }
                $user->setProfileImageUrl($profile->photoURL);
                $repository->persist($user);
                $repository->flush();
                $repository->getConnection()->commit();
                $this->get('session')->getFlashBag()->add(
                    'success',
                    'The Xing location <a href="#">'.$profile->displayName.'</a> was connected successfully.'
                );
            } catch (\Exception $e) {
                $repository->getConnection()->rollback();
                throw $e;
            }
        } else {
            $this->get('session')->getFlashBag()->add(
                'warning',
                'A location has already been connected for this Xing account.'
            );
        }
        return $this->render(
            'CampaignChainChannelXingBundle:Create:login.html.twig',
            array(
                'redirect' => $this->generateUrl('campaignchain_core_channel')
            )
        );
    }

}