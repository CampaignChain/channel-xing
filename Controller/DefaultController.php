<?php

namespace CampaignChain\Channel\XingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('CampaignChainChannelXingBundle:Default:index.html.twig', array('name' => $name));
    }
}
