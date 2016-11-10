<?php

namespace BMM\CMSMove\Config;

use ZipArchive;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Config
{
    
    /**
     * The environment (staging, production, etc.)
     *
     * @var
     */
    protected $environment;

    /**
     * The remote hostname or ip address
     *
     * @var
     */
    protected $host;

    /**
     * The "home" directory (e.g. /home/host_name/public_html)
     *
     * @var
     */
    protected $directory;

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

    public function __construct($environment, $host, $directory, $sshUser, $sshPass, $sshPort = 22, $database, $dbUser, $dbPass, $dbHost = 'localhost', $dbPort = 3306)
    {
        $this->environment = $environment;
        $this->host = $host;
        $this->directory = $directory;
        $this->sshUser = $sshUser;
        $this->sshPass = $sshPass;
        $this->sshPort = $sshPort;
        $this->database = $database;
        $this->dbUser = $dbUser;
        $this->dbPass = $dbPass;
        $this->dbHost = $dbHost;
        $this->dbPort = $dbPort;
    }

}