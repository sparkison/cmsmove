<?php

namespace BMM\CMSMove\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use BMM\CMSMove\Config;

class FixPermissions extends Command
{
    /**
     * Class specific variables
     *
     * @var
     */
    private $classNamespace;
    private $environment;
    private $action = 'fixPerms';
    private $sudo = false;
    private $host;
    private $root;
    private $public;
    private $sshUser;
    private $sshKeyFile;
    private $sshPass;
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
        $this->setName('fixperms')
            ->setDescription('Attempts to fix the permissions of the selected environment')
            ->addArgument('environment', InputArgument::REQUIRED, 'The environment to set permissions on');
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
            die();
        }

        $configVariables = json_decode(file_get_contents($configFile));

        if (!array_key_exists('type', $configVariables)) {
            $io->error("Config file does not contain \"type\" variable.");
            die();
        }

        $className = ucfirst($configVariables->type);
        $classNamespace = 'BMM\CMSMove\Config\\' . $className . '\\Config';

        if (!class_exists($classNamespace)) {
            $io->error("Unable to find class for the specified CMS \"" . $classNamespace . "\"");
            die();
        }

        // Set the namespaced class
        $this->classNamespace = $classNamespace;

        // The environment is the first arg
        $this->environment = $input->getArgument('environment');
        $this->getVariables($configVariables, $io);
        $this->fixPermissions($input, $output, $configVariables, $io);

    }

    /**
     * Start it up!
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param Object $configVariables
     * @param SymfonyStyle $io
     */
    private function fixPermissions(InputInterface $input, OutputInterface $output, $configVariables, SymfonyStyle $io)
    {

        // Already checked if class exists and configured required variables
        // Fire it up!
        $config = new $this->classNamespace($input, $output, $io, $configVariables, $this->environment, $this->action, $this->host, $this->root, $this->public, $this->sshUser, $this->sshKeyFile, $this->sshPass, $this->sshPort, $this->database, $this->dbUser, $this->dbPass, $this->dbHost, $this->dbPort, $this->sudo);

        // Check if the desired method exists
        if (method_exists($this->classNamespace, $this->action)) {
            $config->{$this->action}();
        } else {
            $io->error("Unable to find method \"" . $this->action . "\" in the loaded config class \"" . $this->classNamespace . "\"");
            die();
        }

    }

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
            die();
        }

        // See if the environment exists first
        if (array_key_exists($this->environment, $environmentArgs)) {
            $environmentArgs = $environmentArgs->{$this->environment};
        } else {
            $io->error("Unable to locate the \"" . $this->environment . "\" key in your configured environments. Please check for proper formatting and try again.");
            die();
        }

        // See if the host is set
        if (array_key_exists('host', $environmentArgs)) {
            $this->host = $environmentArgs->host;
        } elseif ($this->environment !== 'local') {
            $io->error("Unable to locate the \"host\" variable in your environment. Please check for proper formatting and try again.");
            die();
        }

        // See if root is set
        if (array_key_exists('root', $environmentArgs)) {
            $this->root = $environmentArgs->root;
        } elseif ($this->environment !== 'local') {
            $io->error("Unable to locate the \"root\" variable in your environment. Please check for proper formatting and try again.");
            die();
        }

        // See if public is set
        if (array_key_exists('public', $environmentArgs)) {
            $this->public = $environmentArgs->public;
        } elseif ($this->environment !== 'local') {
            $io->error("Unable to locate the \"public\" variable in your environment. Please check for proper formatting and try again.");
            die();
        }

        // See if SSH user is set
        if (array_key_exists('user', $environmentArgs)) {
            $this->sshUser = $environmentArgs->user;
        } elseif ($this->environment !== 'local') {
            $io->error("Unable to locate the \"user\" variable in your environment. Please check for proper formatting and try again.");
            die();
        }

        // See if SSH key file is set
        if (array_key_exists('keyfile', $environmentArgs) && $environmentArgs->keyfile != "") {
            $this->sshKeyFile = $environmentArgs->keyfile;
        } else if (array_key_exists('password', $environmentArgs) && $environmentArgs->password != "") { // See if SSH password is set
            $this->sshPass = $environmentArgs->password;
        } elseif ($this->environment !== 'local') {
            $io->error("You must define either a SSH \"keyfile\" or SSH \"password\". We were unable to find either in your environment config. Please check for proper formatting and try again.");
            die();
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
        }

        // See if database user is set
        if (array_key_exists('dbUser', $environmentArgs)) {
            $this->dbUser = $environmentArgs->dbUser;
        }

        // See if database password is set
        if (array_key_exists('dbPass', $environmentArgs)) {
            $this->dbPass = $environmentArgs->dbPass;
        }

        // See if database host is set
        if (array_key_exists('dbHost', $environmentArgs)) {
            $this->dbHost = $environmentArgs->dbHost;
        }

        // See if database port is set
        if (array_key_exists('dbPort', $environmentArgs)) {
            $this->dbPort = $environmentArgs->dbPort;
        }

        // See if sudo set for environment
        if (array_key_exists('sudo', $environmentArgs)) {
            $sudo = strtolower($this->sudo);
            if ($sudo == 'true'
                || $sudo == 'yes'
                || $sudo == 'y'
            )
                $this->sudo = true;
        }


    }

}