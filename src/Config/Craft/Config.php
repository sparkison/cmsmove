<?php

namespace BMM\CMSMove\Config\Craft;

use ZipArchive;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use BMM\CMSMove\Config\Config as BaseConfig;

class Config extends BaseConfig
{

    /**
     * Syncs the templates
     */
    public function templates()
    {
        $cwd = getcwd();
        $command = "";

        // Determine if using SSH password, or keyfile
        $ssh = "";

        // Determine if push or pull
        if ($this->action === 'pull') {
            $command = "rsync --progress -rlpt --compress --omit-dir-times --delete --exclude-from='{$cwd}/rsync.ignore' ";
        } else if ($this->action === 'push') {
            $command = "rsync --progress -rlpt --compress --omit-dir-times --delete --exclude-from='{$cwd}/rsync.ignore' ";
        }
    }

}