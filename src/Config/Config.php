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

    /**
     * Sync the database
     */
    public function database()
    {
        /* Get the backup folder */
        $backupDir = getcwd() . '/db_backups';

        /* See if a folder exists for the database dumps, if not, create it */
        if (!file_exists($backupDir)) {
            mkdir($backupDir);
        }

        /* Inform user of command we're about to perform */
        $title = ucfirst($this->action) . "ing database...";
        $this->io->title($title);

        /* Start a progress bar */
        $progress = new ProgressBar($this->output, 80);
        $progress->start();

        /* Create a timestamp for uniqueness */
        $timestamp = date('Y.m.d_H.i.s');

        /* Set variables for the local and remote database dumps for easy reference */
        $localDbDump = $backupDir . "/local_db_" . $timestamp . ".sql";
        $localToRemote = "/tmp/local_db_" . $timestamp . ".sql";

        $remoteDb = "/tmp/" . $this->environment . "_db_" . $timestamp . ".sql";
        $remoteToLocal = $backupDir . "/" . $this->environment . "_db_" . $timestamp . ".sql";

        /* Get the local DB info */
        $localDb = $this->configVars->environments->local;

        /*************************    Make local and remote backups    *************************/

        /* Step 1. make copy of local database */
        $command = "mysqldump --opt --add-drop-table --skip-comments --no-create-db --host={$localDb->dbHost} --user={$localDb->dbUser} --password={$localDb->dbPass} --port={$localDb->dbPort} --databases {$localDb->db} --result-file=$localDbDump";

        /* Execute the command */
        $this->exec($command, false);

        /* Increment progress bar */
        $progress->advance(5);

        /* Step 2. connect to remote host and make copy of database */
        $ssh = new Net_SSH2($this->host);
        /* Enable quite mode to prevent printing of "stdin: is not a tty" line in the output stream */
        $ssh->enableQuietMode();
        if (!empty($this->sshKeyFile)) {
            $key = new Crypt_RSA();
            $key->loadKey(file_get_contents($this->sshKeyFile));
            if (!$ssh->login('root', $key)) {
                $this->io->error('Unable to login into remote host using ssh key');
            }
        } else {
            if (!$ssh->login($this->sshUser, $this->sshPass)) {
                $this->io->error('Unable to login into remote host using password');
            }
        }

        /* Increment progress bar */
        $progress->advance(10);

        /* If here, connected successfully to remote host! */
        $command = "mysqldump --opt --add-drop-table --skip-comments --no-create-db --host={$this->dbHost} --user={$this->dbUser} --password={$this->dbPass} --port={$this->dbPort} --databases {$this->database} --result-file=$remoteDb";
        $this->io->text("<info>Executing remote command:</info> " . $command);
        $ssh->exec($command);

        /* Increment progress bar */
        $progress->advance(10);

        /* Copy the remote dump down to local and remove from remote */
        $scp = new Net_SCP($ssh);
        if (!$scp->get($remoteDb, $remoteToLocal)) {
            $this->io->error('Unable to download remote database dump');
        }

        /* Increment progress bar */
        $progress->advance(5);

        /* Remove the remote database dump */
        $command = "rm " . $remoteDb;
        $this->io->text("<info>Executing remote command:</info> " . $command);
        $ssh->exec($command);

        /* Increment progress bar */
        $progress->advance(5);

        /*************************    Determine what we're doing here    *************************/

        /* See if we're issuing a push or a pull */
        if ($this->action === 'pull') {
            $this->io->text('<info>Getting ready to import the remote database</info>');

            /* Adapt the remote database dump, and import it */
            $this->adaptDump($remoteToLocal);

            /* Increment progress bar */
            $progress->advance(5);

            /* Import it */
            $command = "mysql --host={$localDb->dbHost} --user={$localDb->dbUser} --password={$localDb->dbPass} --port={$localDb->dbPort} --database={$localDb->db} < $remoteToLocal";
            $this->exec($command, false);

            /* Increment progress bar */
            $progress->advance(20);

            /* Remove the remote copy (since we imported it, no need to keep it, we have the original as a backup) */
            $command = "rm $remoteToLocal";
            $this->exec($command, false);

            /* End the progress bar */
            $progress->finish();

            /* All done here! */

        } else {
            /* Copy the local database to the remote host */
            $this->io->text('<info>Getting ready to push the local database to the remote host for import</info>');

            /* Adapt the local database dump, and upload it */
            $this->adaptDump($localDbDump);

            /* Increment progress bar */
            $progress->advance(5);

            /* Copy to the remote host */
            if (!$scp->put($localToRemote, $localDbDump)) {
                $this->io->error('Unable to upload local database dump to remote host');
            }

            /* Increment progress bar */
            $progress->advance(10);

            /* Import the database on the remote host */
            $command = "mysql --host={$this->dbHost} --user={$this->dbUser} --password={$this->dbPass} --port={$this->dbPort} --database={$this->database} < $localToRemote";
            $this->io->text("<info>Executing remote command:</info> " . $command);
            $ssh->exec($command);

            /* End the progress bar */
            $progress->finish();

            /* Remove the local copy (since we imported it, no need to keep it, we have the original as a backup) */
            $command = "rm $localDbDump";
            $this->exec($command, false);
        }

        /*************************    All done!    *************************/

        $this->io->success("Completed {$this->action}ing database!");

    } // END database() function

    /****************************************
     * BEGIN helper functions
     ****************************************/

    /**
     * Use rsync to push/pull using the selected local and remote directories
     *
     * @param $local
     * @param $remote
     * @param string $syncing
     */
    public function syncIt($local, $remote, $syncing = "")
    {
        $cwd = getcwd();
        $ssh = "";

        /**
         * Remove any trailing slashes from source/dest as it could mess up the sync
         */
        $local = rtrim($local, "/");
        $remote = rtrim($remote, "/");

        /**
         * Determine if using SSH password, or keyfile
         */
        if (!empty($this->sshKeyFile)) {
            $ssh = "-e 'ssh -i " . $this->sshKeyFile . "'";
        } else {
            $ssh = "--rsh=\"sshpass -p '" . $this->sshPass . "' ssh -o StrictHostKeyChecking=no\"";
        }

        /**
         * Inform user of command we're about to perform
         */
        $title = ucfirst($this->action) . "ing $syncing...";
        $this->io->title($title);

        // Determine if push or pull
        if ($this->action === 'pull') {
            $command = "rsync {$ssh} --progress -rlpt --compress --omit-dir-times --delete {$this->sshUser}@{$this->host}:/{$remote}/ {$cwd}/{$local}";
        } else if ($this->action === 'push') {
            $command = "rsync {$ssh} --progress -rlpt --compress --omit-dir-times --delete {$cwd}/{$local}/ {$this->sshUser}@{$this->host}:/{$remote}";
        }

        /**
         * Exec the command
         */
        $this->exec($command);

    } // END syncIt() function

    /**
     * Issue the exec command and show output
     * Will also show errors if any received
     *
     * @param $command
     * @param bool $success_msg
     */
    public function exec($command, $success_msg = true)
    {
        /**
         * Make sure we have a command set
         * Inform the user of the full command that is about to be executed
         * Execute it!
         */
        if (isset($command)) {
            $this->io->text("<info>Executing local command:</info> " . $command);
            exec($command, $output, $exit_code);
        }

        /**
         * If exec returned any output, display it for the user
         */
        if (isset($output) && !empty($output)) {
            foreach ($output as $line) {
                $this->io->writeln($line);
            }
        }

        /**
         * If exec returned an error code, show the user there was an error
         * Else, show a success message
         */
        if (isset($exit_code) && $exit_code == 0) {
            if ($success_msg)
                $this->io->success(ucfirst($this->action) . " command executed successfully!");
        } else {
            $this->io->error("There was an error executing the " . ucfirst($this->action) . " command. Please check your config and try again");
        }

    } // END exec() function

    /**
     * Adapt the SQL dump file
     *
     * @param $file
     */
    public function adaptDump($file)
    {

        $contents = file_get_contents($file);
        $contents_arr = explode("\n", $contents);
        $contents = array();
        foreach ($contents_arr as $line) {
            if ( !((substr($line, 0, 2) === "--") || (substr($line, 0, 3) === "USE")) ) {
                $contents[] = $line;
            }
        }
        $contents = implode("\n", $contents);
        file_put_contents($file, $contents);

    } // END adaptDump() function

    /****************************************
     * END Sync helper functions
     ****************************************/

}