<?php

namespace BMM\CMSMove\Config;

/**
 * Abstract class to define the constructor and variables that will be common among all CMS
 * Methods are the "actions" that can be taken with a "push" or a "pull"
 *
 * Be sure to extend this class for any custom CMS integration. The bootstrap config should also be included
 * defining the basic structure of the CMS. This file can be digested by your Config class for further customization
 * of actions to take.
 *
 * Each config.json file must have the fields defined within this class.
 *
 * Basic idea is to create a method for each action:
 *
 *      "cmsmove push/pull <environment> <action>"
 *
 * Will also have access to the common "execute' variables:
 *
 *      InputInterface $input
 *      OutputInterface $output
 *      SymfonyStyle $io
 *
 */

use ZipArchive;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Config
{
    /**
     * Symphony InputInterface
     *
     * @var
     */
    protected $input;

    /**
     * Symphony OutputInterface
     *
     * @var
     */
    protected $output;

    /**
     * Symphony console input/output
     *
     * @var
     */
    protected $io;

    /**
     * The config file contents
     * Access keys using Object syntax
     *
     * @var
     */
    protected $configVars;

    /**
     * The environment (staging, production, etc.)
     *
     * @var
     */
    protected $environment;

    /**
     * Whether this is a push or a pull action
     *
     * @var
     */
    protected $action;

    /**
     * The remote hostname or ip address
     *
     * @var
     */
    protected $host;

    /**
     * The "root" directory (e.g. "/home/host_name")
     *
     * @var
     */
    protected $root;

    /**
     * The "public" directory (e.g. "public_html" or "www")
     * This will be appended to the "root" variable to get the full path
     *      e.g. $root . "/" . $public => "/home/host_name/public_html"
     *
     * @var
     */
    protected $public;

    /**
     * The host username
     *
     * @var
     */
    protected $sshUser;

    /**
     * The host password (default to use ssh keys, needed if no keys present on your machine for the specified host)
     *
     * @var
     */
    protected $sshPass;

    /**
     * Must provide either password or keyfile!
     * Cannot leave both blank.
     *
     * Will default to using keyfile if present
     *
     * @var
     */
    protected $sshKeyFile;

    /**
     * SSH port to use to connect (defaults to 22)
     *
     * @var
     */
    protected $sshPort;

    /**
     * The remote database name
     *
     * @var
     */
    protected $database;

    /**
     * The user for the remote database
     *
     * @var
     */
    protected $dbUser;

    /**
     * The password for the remote database
     *
     * @var
     */
    protected $dbPass;

    /**
     * The hostname for the remote database (e.g. "localhost" or "127.0.0.1"), defaults to "localhost"
     *
     * @var
     */
    protected $dbHost;

    /**
     * The port for the remote database (defaults to 3306)
     *
     * @var
     */
    protected $dbPort;

    public function __construct($input, $output, $io, $configVars, $environment, $action, $host, $root, $public, $sshUser, $sshKeyFile, $sshPass, $sshPort = 22, $database, $dbUser, $dbPass, $dbHost = 'localhost', $dbPort = 3306)
    {
        $this->input = $input;
        $this->output = $output;
        $this->io = $io;
        $this->configVars = $configVars;
        $this->environment = $environment;
        $this->action = $action;
        $this->host = $host;
        $this->root = $root;
        $this->public = $public;
        $this->sshUser = $sshUser;
        $this->sshKeyFile = $sshKeyFile;
        $this->sshPass = $sshPass;
        $this->sshPort = $sshPort;
        $this->database = $database;
        $this->dbUser = $dbUser;
        $this->dbPass = $dbPass;
        $this->dbHost = $dbHost;
        $this->dbPort = $dbPort;
    }

}