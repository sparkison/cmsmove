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
        $ssh = "";

        // Determine if using SSH password, or keyfile
        if ($this->sshKeyFile !== "") {
            $ssh = "-e 'ssh -i " . $this->sshKeyFile . "'";
        } else {
            $ssh = "--rsh=\"sshpass -p '" . $this->sshPass . "' ssh -o StrictHostKeyChecking=no\"";
        }

        // Inform user of command
        $title = ucfirst($this->action) . "ing templates...";
        $this->io->title($title);

        // Determine if push or pull
        if ($this->action === 'pull') {
            $command = "rsync {$ssh} --progress -rlpt --compress --omit-dir-times --delete {$this->sshUser}@{$this->host}:/tmp/ {$cwd}/test";
        } else if ($this->action === 'push') {
            $command = "rsync {$ssh} --progress -rlpt --compress --omit-dir-times --delete {$cwd}/test/ {$this->sshUser}@{$this->host}:/tmp";
        }

        if (isset($command)) {
            exec($command, $output, $exit_code);
        }

        if (isset($output) && !empty($output)) {
            foreach ($output as $line) {
                $this->io->writeln($line);
            }
        }

        if(isset($exit_code) && $exit_code == 0) {
            $this->io->success(ucfirst($this->action) . " command executed successfully!");
        } else {
            $this->io->error("There was an error executing the " . ucfirst($this->action) . " command. Please check your config and try again");
        }

    }

    /**
     * Sync the database
     */
    public function database()
    {
        //        $ssh = new Net_SSH2('example.com');
//        if ($ssh->login('root', 'password')) {
//            $this->io->success('Password Login Success!!');
//        }
//
//        $key = new Crypt_RSA();
//        $key->loadKey(file_get_contents('/Users/ME/.ssh/id_rsa'));
//
//        // echo $ssh->exec('pwd');
//        // echo $ssh->getLog();
//
//        if ($ssh->login('root', $key)) {
//            $this->io->success('Login with keyfile success!!');
//        }
    }

}