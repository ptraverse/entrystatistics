<?php

namespace Craft;

class EntrystatisticsPlugin extends BasePlugin
{
    public function getName()
    {
        return Craft::t('Entry Statistics');
    }

    public function getVersion()
    {
        return '0.0.1';
    }

    public function getDeveloper()
    {
        return 'Philippe Traverse';
    }

    public function getDeveloperUrl()
    {
        return 'http://ptraverse.github.io';
    }
}
