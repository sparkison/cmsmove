<?php

namespace BMM\CMSMove\Config\Craft;

use ZipArchive;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use phpseclib\Net\SSH2 as Net_SSH2;
use phpseclib\Crypt\RSA as Crypt_RSA;

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
        if ($this->sshKeyFile !== "") {

        } else {

        }

        $ssh = new Net_SSH2('example.com');
        if ($ssh->login('root', 'password')) {
            $this->io->success('Password Login Success!!');
        }

        $key = new Crypt_RSA();
        $key->loadKey(file_get_contents('/Users/ME/.ssh/id_rsa'));

        // echo $ssh->exec('pwd');
        // echo $ssh->getLog();

        if ($ssh->login('root', $key)) {
            $this->io->success('Login with keyfile success!!');
        }


        // Determine if push or pull
        if ($this->action === 'pull') {
            $command = "rsync --progress -rlpt --compress --omit-dir-times --delete --exclude-from='{$cwd}/rsync.ignore' ";
        } else if ($this->action === 'push') {
            $command = "rsync --progress -rlpt --compress --omit-dir-times --delete --exclude-from='{$cwd}/rsync.ignore' ";
        }
    }

}