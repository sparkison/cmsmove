<?php

namespace BMM\CMSMove\ConfigGenerators\Config;

use ZipArchive;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use BMM\CMSMove\ConfigGenerators\Config as BaseConfig;

class Config extends BaseConfig
{

    public function __construct($configFile)
    {
        parent::__construct($configFile);
    }

}