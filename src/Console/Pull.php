<?php

namespace BMM\CMSMove\Console;

use ReflectionProperty;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use BMM\CMSMove\Config;

class Pull extends Command
{
    /**
     * Class specific variables
     *
     * @var
     */
    private $classNamespace;
    private $environment;
    private $destination;
    private $host;
    private $directory;
    private $sshUser;
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
        $this->setName('pull')
            ->setDescription('Pull files from remote to local')
            ->addArgument('environment', InputArgument::REQUIRED)
            ->addArgument('directory', InputArgument::REQUIRED);
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
        $this->destination = $input->getArgument('directory');
        $this->getVariables($configVariables, $io);

    }

    /**
     * Find the variables to instantiate the class
     *
     * @param $configArgs
     * @param $io
     */
    private function getVariables($configArgs, &$io)
    {

        $environmentArgs = null;

        // Grab environments if set
        if(array_key_exists('environments', $configArgs)) {
            $environmentArgs = $configArgs->environments;
        } else {
            $io->error("Unable to locate the \"environments\" key in the config file. Please check for proper formatting and try again.");
            return;
        }

        // See if the environment exists first
        if(array_key_exists($this->environment, $environmentArgs)) {
            $environmentArgs = $environmentArgs->{$this->environment};
        } else {
            $io->error("Unable to locate the \"". $this->environment ."\" key in your configured environments. Please check for proper formatting and try again.");
            return;
        }

        print_r($environmentArgs);

        // See if the host is set
        if(array_key_exists('', $environmentArgs)) {
            $this->host = "";
        } else {

        }

        // See if directory is set
        if(array_key_exists('', $environmentArgs)) {
            $this->directory = "";
        } else {

        }

        // See if SSH user is set
        if(array_key_exists('', $environmentArgs)) {
            $this->sshUser = "";
        } else {

        }

        // See if SSH password is set
        if(array_key_exists('', $environmentArgs)) {
            $this->sshPass = "";
        } else {

        }

        // See if SSH port is set
        if(array_key_exists('', $environmentArgs)) {
            $this->sshPort = "";
        } else {
            $this->sshPort = "22";
        }

        // See if database name is set
        if(array_key_exists('', $environmentArgs)) {
            $this->database = "";
        } else {

        }

        // See if database user is set
        if(array_key_exists('', $environmentArgs)) {
            $this->dbUser = "";
        } else {

        }

        // See if database password is set
        if(array_key_exists('', $environmentArgs)) {
            $this->dbPass = "";
        } else {

        }

        // See if database host is set
        if(array_key_exists('', $environmentArgs)) {
            $this->dbHost = "";
        } else {
            $this->dbHost = "localhost";
        }

        // See if database port is set
        if(array_key_exists('', $environmentArgs)) {
            $this->dbPort = "";
        } else {
            $this->dbPort = "3306";
        }

    }

}