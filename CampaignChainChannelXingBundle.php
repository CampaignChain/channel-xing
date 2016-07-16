<?php

namespace CampaignChain\Channel\XingBundle;

use CampaignChain\Channel\XingBundle\DependencyInjection\CampaignChainChannelXingExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CampaignChainChannelXingBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new CampaignChainChannelXingExtension();
    }
}
