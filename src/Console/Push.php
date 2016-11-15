<?php

namespace BMM\CMSMove\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use BMM\CMSMove\Config;

class Push extends Command
{
    /**
     * Class specific variables
     *
     * @var
     */
    private $classNamespace;
    private $environment;
    private $destination;
    private $action = 'push';
    private $host;
    private $directory;
    private $sshUser;
    private $sshPass;
    private $sshKeyFile;
    private $sshPort;
    private $database;
    private $dbUser;
    private $dbPass;
    private $dbHost;
    private $dbPort;

    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('push')
            ->setDescription('Push files from local to remote')
            ->addArgument('environment', InputArgument::REQUIRED, 'The environment to push to')
            ->addArgument('destination', InputArgument::REQUIRED, 'The directory/file to sync');
    }

    /**
     * Execute the command.
     *
     * @param  InputInterface $input
     * @param  OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $io = new SymfonyStyle($input, $output);
        $configFile = getcwd() . '/moveConfig.json';
        if (!file_exists($configFile)) {
            $io->error("No config file found. Please run the \"config\" command first.");
            return;
        }

        $configVariables = json_decode(file_get_contents($configFile));

        if (!array_key_exists('type', $configVariables)) {
            $io->error("Config file does not contain \"type\" variable.");
            return;
        }

        $className = ucfirst($configVariables->type);
        $classNamespace = 'BMM\CMSMove\Config\\' . $className . '\\Config';

        if (!class_exists($classNamespace)) {
            $io->error("Unable to find class for the specified CMS \"" . $classNamespace . "\"");
            return;
        }

        // Set the namespaced class
        $this->classNamespace = $classNamespace;

        // The environment is the first arg
        $this->environment = $input->getArgument('environment');
        $this->destination = $input->getArgument('destination');
        $this->getVariables($configVariables, $io);
        $this->doPush($configVariables, $io);

    }

    /**
     * Start it up!
     *
     * @param $configVariables
     * @param $io
     */
    private function doPush($configVariables, $io)
    {

        // Already checked if class exists and configured required variables
        // Fire it up!
        $config = new $this->classNamespace($io, $configVariables, $this->environment, $this->action, $this->host, $this->directory, $this->sshUser, $this->sshKeyFile, $this->sshPass, $this->sshPort, $this->database, $this->dbUser, $this->dbPass, $this->dbHost, $this->dbPort);

        // Check if the desired method exists
        if (method_exists($this->classNamespace, $this->destination)) {
            $config->{$this->destination}();
        } else {
            $io->error("Unable to find method \"" . $this->destination . "\" in the loaded config class \"" . $this->classNamespace . "\"");
            return;
        }

    }

    /**
     * Find the variables to instantiate the class
     *
     * @param $configArgs
     * @param $io
     */
    /**
     * Find the variables to instantiate the class
     *
     * @param $configArgs
     * @param $io
     */
    private function getVariables($configArgs, $io)
    {

        // Default environment args
        $environmentArgs = null;

        // Grab environments if set
        if (array_key_exists('environments', $configArgs)) {
            $environmentArgs = $configArgs->environments;
        } else {
            $io->error("Unable to locate the \"environments\" key in the config file. Please check for proper formatting and try again.");
            return;
        }

        // See if the environment exists first
        if (array_key_exists($this->environment, $environmentArgs)) {
            $environmentArgs = $environmentArgs->{$this->environment};
        } else {
            $io->error("Unable to locate the \"" . $this->environment . "\" key in your configured environments. Please check for proper formatting and try again.");
            return;
        }

        // See if the host is set
        if (array_key_exists('host', $environmentArgs)) {
            $this->host = $environmentArgs->host;
        } else {
            $io->error("Unable to locate the \"host\" variable in your environment. Please check for proper formatting and try again.");
            return;
        }

        // See if directory is set
        if (array_key_exists('root', $environmentArgs)) {
            $this->directory = $environmentArgs->root;
        } else {
            $io->error("Unable to locate the \"root\" variable in your environment. Please check for proper formatting and try again.");
            return;
        }

        // See if SSH user is set
        if (array_key_exists('user', $environmentArgs)) {
            $this->sshUser = $environmentArgs->user;
        } else {
            $io->error("Unable to locate the \"user\" variable in your environment. Please check for proper formatting and try again.");
            return;
        }

        // See if SSH key file is set
        if (array_key_exists('keyfile', $environmentArgs)) {
            $this->sshKeyFile = $environmentArgs->keyfile;
        } else if (array_key_exists('password', $environmentArgs)) { // See if SSH password is set
            $this->sshPass = $environmentArgs->password;
        } else {
            $io->error("You must define either a SSH \"keyfile\" or SSH \"password\". We were unable to find either in your environment config. Please check for proper formatting and try again.");
            return;
        }

        // See if SSH port is set
        if (array_key_exists('port', $environmentArgs)) {
            $this->sshPort = $environmentArgs->port;
        } else {
            $this->sshPort = "22";
        }

        // See if database name is set
        if (array_key_exists('db', $environmentArgs)) {
            $this->database = $environmentArgs->db;
        } else {
            $io->error("Unable to locate the \"db\" variable in your environment. Please check for proper formatting and try again.");
            return;
        }

        // See if database user is set
        if (array_key_exists('dbUser', $environmentArgs)) {
            $this->dbUser = $environmentArgs->dbUser;
        } else {
            $io->error("Unable to locate the \"dbUser\" variable in your environment. Please check for proper formatting and try again.");
            return;
        }

        // See if database password is set
        if (array_key_exists('dbPass', $environmentArgs)) {
            $this->dbPass = $environmentArgs->dbPass;
        } else {
            $io->error("Unable to locate the \"dbPass\" variable in your environment. Please check for proper formatting and try again.");
            return;
        }

        // See if database host is set
        if (array_key_exists('dbHost', $environmentArgs)) {
            $this->dbHost = $environmentArgs->dbHost;
        } else {
            $this->dbHost = "localhost";
        }

        // See if database port is set
        if (array_key_exists('dbPort', $environmentArgs)) {
            $this->dbPort = $environmentArgs->dbPort;
        } else {
            $this->dbPort = "3306";
        }

    }

}