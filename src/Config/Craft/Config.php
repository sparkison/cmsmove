<?php

namespace BMM\CMSMove\Config\Craft;

use ZipArchive;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use BMM\CMSMove\Config\Config as BaseConfig;

class Config extends BaseConfig
{

    public function templates()
    {
        echo 'sync the templates!!';
    }

}