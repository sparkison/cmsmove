<?php

namespace BMM\CMSMove\Config;

/**
 * @documentation Abstract class to define the constructor and variables that will be common among all CMS
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

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

use phpseclib\Net\SCP as Net_SCP;
use phpseclib\Net\SSH2 as Net_SSH2;
use phpseclib\Crypt\RSA as Crypt_RSA;

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
     * Determines whether to run commands as sudo user or not
     *
     * @var
     */
    protected $sudo;

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

    public function __construct($input, $output, $io, $configVars, $environment, $action, $host, $root, $public, $sshUser, $sshKeyFile, $sshPass, $sshPort = 22, $database, $dbUser, $dbPass, $dbHost = 'localhost', $dbPort = 3306, $sudo = false)
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
        $this->sudo = $sudo;

        /**
         * Add some style to the command line!
         */
        $style = new OutputFormatterStyle('magenta');
        $this->output->getFormatter()->setStyle('remote', $style);

        $style = new OutputFormatterStyle('blue');
        $this->output->getFormatter()->setStyle('local', $style);

        $style = new OutputFormatterStyle('green');
        $this->output->getFormatter()->setStyle('command', $style);

    }

    /**
     * Sync the user defined "custom" directories
     * Will prompt user for the directory they wish to sync
     */
    public function custom()
    {

        /* Get the custom directory array */
        $customDirs = $this->configVars->mappings->custom;

        /* Get the object vars */
        $customDirVars = get_object_vars($customDirs);
        $choices = [];
        foreach ($customDirVars as $var => $object) {
            $choices[] = $var;
        }

        /* prompt user for the desired custom directory to sync */
        $helper = new QuestionHelper();
        $question = new ChoiceQuestion('<comment>Please select the custom directory to ' . $this->action . ':</comment> ', $choices, '');
        $question->setErrorMessage('Directory %s is invalid.');

        $customDir = $helper->ask($this->input, $this->output, $question);

        if (property_exists($customDirs, $customDir)) {

            /* see if we're pushing a folder or file */
            $file = false;
            if (property_exists($customDirs->{$customDir}, 'file')) {
                // File is set
                $file_setting = strtolower($customDirs->{$customDir}->file);
                if ($file_setting == 'true'
                    || $file_setting == 'yes'
                    || $file_setting == 'y'
                )
                    $file = true;
            }

            /* What are we syncing */
            $syncing = $file ? 'file' : 'directory';

            /* Entered a valid custom directory, let's sync it! */
            $title = ucfirst($this->action) . "ing the custom $syncing \"$customDir\"...";
            $this->io->title($title);

            /*
             * Need to determine if this is a public or root level item
             * Each custom directory requires two keys
             * 1. type - either "root" or "public"
             * 2. directory - the directory we're pushing/pulling
             */
            $baseDir = $customDirs->{$customDir}->type;
            if ($baseDir === 'public') {
                $localDir = $this->configVars->mappings->www . "/" . $customDirs->{$customDir}->directory;
                $remoteDir = $this->root . "/" . $this->public . "/" . $customDirs->{$customDir}->directory;
            } else if($baseDir === 'root') {
                /*
                 * If root, locally start from the current working directory (will be an absolute path to the project root)
                 * Remotely, will be the absolute path to the root directory set within the environment config variables
                 */
                $localDir = $customDirs->{$customDir}->directory;
                $remoteDir = $this->root . "/" . $customDirs->{$customDir}->directory;
            } else {
                $this->io->error("You don't have a proper \"type\" set in your custom directory config. Please ensure \"type\" is set to either \"public\" or \"root\" and try again");
                die();
            }

            $this->syncIt($localDir, $remoteDir, "custom", false, $file);

        } else {
            /* Didn't find the custom directory entered, inform the user and show the directory so they know */
            $this->io->error("Unable to find a key for the directory you entered: \"$customDir\"");
            die();
        }

    } // END custom() function

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

        /* Start a progress bar

        Disabled for now, not very useful, and clutters the output

        $progress = new ProgressBar($this->output, 80);
        $progress->start();

        */

        $dbType = 'mysql';
        $dbExtensions = '.sql';
        if(property_exists($this->configVars, 'db_type')) {
            $dbType = $this->configVars->db_type;
            if($dbType === 'psql')
                $dbExtensions = '.psql';
        }

        /* Create a timestamp for uniqueness */
        $timestamp = date('Y.m.d_H.i.s');

        /* Set variables for the local and remote database dumps for easy reference */
        $localDbDump = $backupDir . "/local_db_" . $timestamp . $dbExtensions;
        $localToRemote = "/tmp/local_db_" . $timestamp . $dbExtensions;

        $remoteDbDump = "/tmp/" . $this->environment . "_db_" . $timestamp . $dbExtensions;
        $remoteToLocal = $backupDir . "/" . $this->environment . "_db_" . $timestamp . $dbExtensions;

        /* Get the local DB info */
        $localDb = $this->configVars->environments->local;

        /*************************    Make local and remote backups    *************************/

        /* Step 1. make copy of local database */
        if($dbType === 'psql')
            $command = "pg_dump -Fc -c --exclude-schema=topology --exclude-table=spatial_ref_sys --no-acl --no-owner --file=$localDbDump --dbname=postgres://{$localDb->dbUser}:{$localDb->dbPass}@{$localDb->dbHost}:{$localDb->dbPort}/{$localDb->db}";
        else
            $command = "mysqldump --opt --add-drop-table --skip-comments --no-create-db --host={$localDb->dbHost} --user={$localDb->dbUser} --password='{$localDb->dbPass}' --port={$localDb->dbPort} --databases {$localDb->db} --result-file=$localDbDump";

        /* Execute the command */
        $this->exec($command, false);

        /* Step 2. connect to remote host and make copy of database */
        $ssh = new Net_SSH2($this->host, $this->sshPort);
        /* Enable quite mode to prevent printing of "stdin: is not a tty" line in the output stream */
        $ssh->enableQuietMode();
        if (!empty($this->sshKeyFile)) {
            $key = new Crypt_RSA();
            $key->loadKey(file_get_contents($this->sshKeyFile));
            if (!$ssh->login($this->sshUser, $key, Net_SSH2::LOG_SIMPLE)) {
                $this->io->error('Unable to login into remote host using ssh key');
                die();
            }
        } else {
            if (!$ssh->login($this->sshUser, $this->sshPass, Net_SSH2::LOG_SIMPLE)) {
                $this->io->error('Unable to login into remote host using password');
                die();
            }
        }

        /* Output the ssh log, if anything */
        if(!empty($ssh->message_log))
            $this->io->note($ssh->message_log);

        /* If here, connected successfully to remote host! */
        if($dbType === 'psql')
            $command = "pg_dump -Fc -c --exclude-schema=topology --exclude-table=spatial_ref_sys --no-acl --no-owner --file=$remoteDbDump --dbname=postgres://{$this->dbUser}:{$this->dbPass}@{$this->dbHost}:{$this->dbPort}/{$this->database}";
        else
            $command = "mysqldump --opt --add-drop-table --skip-comments --no-create-db --host={$this->dbHost} --user={$this->dbUser} --password='{$this->dbPass}' --port={$this->dbPort} --databases {$this->database} --result-file=$remoteDbDump";

        $this->io->text("<remote>Executing remote command:</remote> " . $command);
        $this->io->text($ssh->exec($command));

        /* Compress the remote DB */
        $command = "gzip -f --best $remoteDbDump";
        $this->io->text("<remote>Executing remote command:</remote> " . $command);
        $this->io->text($ssh->exec($command));

        /* Step 3. copy the remote dump down to local */
        $this->io->text("<remote>Executing remote command:</remote> scp -p {$this->sshPort} {$this->sshUser}@{$this->host}:{$remoteDbDump}.gz {$remoteToLocal}.gz");

        $scp = new Net_SCP($ssh);
        if (!$scp->get($remoteDbDump . '.gz', $remoteToLocal . '.gz')) {
            $this->io->error('Unable to download remote database dump');
            die();
        }

        /* Now that we've download the remote DB, delete it from the remote host */
        $command = "rm {$remoteDbDump}.gz";
        $this->io->text("<remote>Executing remote command:</remote> " . $command);
        $this->io->text($ssh->exec($command));

        /*************************    Determine what we're doing here    *************************/

        /* Step 5. see if we're issuing a push or a pull */
        if ($this->action === 'pull') {
            /* Compress the local dump for safe-keeping */
            $command = "gzip -f --best $localDbDump";
            $this->exec($command, false);

            /* Decompress the remote DB dump */
            $command = "gzip -d -q {$remoteToLocal}.gz";
            $this->exec($command, false);

            /* Adapt the remote database dump, and import it */
            $this->adaptDump($remoteToLocal);

            /* Import it */
            if($dbType === 'psql')
                $command = "pg_restore -Fc -c --dbname=postgres://{$localDb->dbUser}:{$localDb->dbPass}@{$localDb->dbHost}:{$localDb->dbPort}/{$localDb->db} $remoteToLocal";
            else
                $command = "mysql --host={$localDb->dbHost} --user={$localDb->dbUser} --password='{$localDb->dbPass}' --port={$localDb->dbPort} --database={$localDb->db} --force --execute=\"SET autocommit=0;SOURCE $remoteToLocal;COMMIT\"";

            $this->exec($command, false);

            /* Remove the remote copy (since we imported it, no need to keep it, we have the original as a backup) */
            $command = "rm $remoteToLocal";
            $this->exec($command, false);
        } else {
            /* Compress the remote DB for safe-keeping */
            $command = "gzip -f --best $remoteToLocal";
            $this->exec($command, false);

            /* Adapt the local database dump, and upload it */
            $this->adaptDump($localDbDump);

            /* Compress it! */
            $command = "gzip -f --best $localDbDump";
            $this->exec($command, false);

            /* Since the SCP output is silent, inform user of command */
            $this->io->text("<remote>Executing remote command:</remote> scp -p {$this->sshPort} {$localDbDump}.gz {$this->sshUser}@{$this->host}:{$localToRemote}.gz");

            /* Copy to the remote host */
            if (!$scp->put($localToRemote . '.gz', $localDbDump . '.gz', Net_SCP::SOURCE_LOCAL_FILE)) {
                $this->io->error('Unable to upload local database dump to remote host');
                die();
            }

            /* Decompress the dumpfile */
            $command = "gzip -d -q {$localToRemote}.gz";
            $this->io->text("<remote>Executing remote command:</remote> " . $command);
            $this->io->text($ssh->exec($command));

            /* Import the database on the remote host */
            if($dbType === 'psql')
                $command = "pg_restore -Fc -c --dbname=postgres://{$this->dbUser}:{$this->dbPass}@{$this->dbHost}:{$this->dbPort}/{$this->database} $localToRemote";
            else
                $command = "mysql --host={$this->dbHost} --user={$this->dbUser} --password='{$this->dbPass}' --port={$this->dbPort} --database={$this->database} --force --execute=\"SET autocommit=0;SOURCE $localToRemote;COMMIT\"";

            $this->io->text("<remote>Executing remote command:</remote> " . $command);
            $this->io->text($ssh->exec($command));

            /* Remove the local dump from staging since we've already imported it */
            $command = "rm $localToRemote";
            $this->io->text("<remote>Executing remote command:</remote> " . $command);
            $this->io->text($ssh->exec($command));

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
     * @param bool $output_title
     * @param bool $file
     */
    public function syncIt($local, $remote, $syncing = "", $output_title = true, $file = false)
    {
        $cwd = getcwd();
        $ssh = "";

        /**
         * Remove any trailing slashes from source/dest as it could mess up the sync
         */
        $local = ltrim(rtrim($local, "/"), "/");
        $remote = ltrim(rtrim($remote, "/"), "/");

        /**
         * Determine if using SSH password, or keyfile
         */
        if (!empty($this->sshKeyFile)) {
            $ssh = "-e 'ssh -p $this->sshPort -i " . $this->sshKeyFile . "'";
        } else {
            $ssh = "--rsh=\"sshpass -p '" . $this->sshPass . "' ssh -p $this->sshPort -o StrictHostKeyChecking=no\"";
        }

        /**
         * Inform user of command we're about to perform
         */
        if ($output_title) {
            $title = ucfirst($this->action) . "ing $syncing...";
            $this->io->title($title);
        }

        // Adjust flags for files vs directories
        $flags = $file ? '' : '--omit-dir-times --delete ';

        // Determine if push or pull
        if ($this->action === 'pull') {
            // See if syncing a file or folder
            if($file) {
                $sync = "{$this->sshUser}@{$this->host}:/$remote $cwd/$local";
                $syncArgs = "-lpt";
            } else {
                $sync = "{$this->sshUser}@{$this->host}:/$remote/ $cwd/$local";
                $syncArgs = "-rlpt";
            }
            $command = "rsync {$ssh} --progress $syncArgs --compress $flags--exclude-from={$cwd}/rsync.ignore $sync";
        } else if ($this->action === 'push') {
            // See if syncing a file or folder
            if($file) {
                $sync = "$cwd/$local {$this->sshUser}@{$this->host}:/$remote";
                $syncArgs = "-lpt";
            } else {
                $sync = "$cwd/$local/ {$this->sshUser}@{$this->host}:/$remote";
                $syncArgs = "-rlpt";
            }
            $command = "rsync {$ssh} --progress $syncArgs --compress $flags--exclude-from={$cwd}/rsync.ignore $sync";
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
            $this->io->text("<local>Executing local command:</local> " . $command);
            system($command, $exit_code);
        }

        /**
         * If exec returned an error code, show the user there was an error
         * Else, show a success message
         */
        if (isset($exit_code) && $exit_code !== FALSE) {
            if ($success_msg)
                $this->io->success(ucfirst($this->action) . " command executed successfully!");
        } else {
            $this->io->error("There was an error executing the " . ucfirst($this->action) . " command. Please check your config and try again");
            die();
        }

    } // END exec() function

    /**
     * Adapt the SQL dump file
     *
     * @param $file
     */
    public function adaptDump($file, $chunk_size = 4096)
    {
        $contents = array();
        // Read the file in chunks to prevent out of memory errors
        try
        {
            $handle = fopen($file, "r");
            while (!feof($handle))
            {
                $chunk = fread($handle, $chunk_size);
                $contents_arr = explode("\n", $chunk);
                foreach ($contents_arr as $line) {
                    if ( !((substr($line, 0, 2) === "--") || (substr($line, 0, 3) === "USE")) ) {
                        $contents[] = $line;
                    }
                }
            }
            fclose($handle);
        }
        catch(\Exception $e)
        {
            $this->io->error('Error processing database file: ' . $e->getMessage());
            exit();
        }
        file_put_contents($file, $contents);
    } // END adaptDump() function

    /**
     * Take a serialised array and unserialise it replacing elements as needed and
     * unserialising any subordinate arrays and performing the replace on those too.
     *
     * @param string $from       String we're looking to replace.
     * @param string $to         What we want it to be replaced with
     * @param mixed  $data       Used to pass any subordinate arrays back to in.
     * @param bool   $serialised Does the array passed via $data need serialising.
     *
     * @return array	The original array with all elements replaced as needed.
     */
    public function recursiveUnSerializeReplace($from = '', $to = '', $data = '', $serialised = false)
    {
        // some unserialised data cannot be re-serialised eg. SimpleXMLElements
        try {

            if (is_string($data) && ($unserialized = @unserialize($data)) !== false) {
                $data = $this->recursiveUnSerializeReplace($from, $to, $unserialized, true);
            } elseif (is_array($data)) {
                $_tmp = [];
                foreach ($data as $key => $value) {
                    $_tmp[$key] = $this->recursiveUnSerializeReplace($from, $to, $value, false);
                }
                $data = $_tmp;
                unset($_tmp);
            }
            elseif (is_object($data)) {
                $_tmp = $data;
                $props = get_object_vars($data);
                foreach ($props as $key => $value) {
                    $_tmp->$key = $this->recursiveUnSerializeReplace($from, $to, $value, false);
                }

                $data = $_tmp;
                unset($_tmp);
            } else {
                if (is_string($data)) {
                    $data = str_replace($from, $to, $data, $count);
                }
            }

            if ($serialised) {
                return serialize($data);
            }
        } catch (\Exception $e) {
            $this->io->error('Error issuing search/replace on database: ' . $e->getMessage());
        }

        return $data;
    }

    /**
     * Attempts to fix the permissions on the given environment
     *
     * This will set all files to 0644 and all directories to 0755
     * If different permissions are required, please override the method in the config class
     */
    public function fixPerms()
    {
        // Default to non-sudo user
        $sudo = $this->sudo ? 'sudo ' : '';

        /* Need to determine the environment to fix permissions on */
        if ($this->environment == 'local') {
            $cwd = getcwd();

            // First, set permissions for the app folder
            $command = "cd {$cwd}/{$this->configVars->mappings->app} && {$sudo}find . -type f -exec chmod 644 {} \\;";
            $this->exec($command, false);
            $command = "cd {$cwd}/{$this->configVars->mappings->app} && {$sudo}find . -type d -exec chmod 755 {} \\;";
            /*
             * Checking if www directory set, if so we're not done
             */
            if ($this->configVars->mappings->www !== '')
                $this->exec($command, false);
            else
                $this->exec($command, true);

            // Then the www folder, if set
            if ($this->configVars->mappings->www !== '') {
                $command = "cd {$cwd}/{$this->configVars->mappings->www} && {$sudo}find . -type f -exec chmod 644 {} \\;";
                $this->exec($command, false);
                $command = "cd {$cwd}/{$this->configVars->mappings->www} && {$sudo}find . -type d -exec chmod 755 {} \\;";
                $this->exec($command, true);
            }
        } else {
            /*
             * Setting permissions for remote host, need to establish connection first
             */
            $ssh = new Net_SSH2($this->host, $this->sshPort);
            $ssh->enableQuietMode();
            if (!empty($this->sshKeyFile)) {
                $key = new Crypt_RSA();
                $key->loadKey(file_get_contents($this->sshKeyFile));
                if (!$ssh->login($this->sshUser, $key, Net_SSH2::LOG_SIMPLE)) {
                    $this->io->error('Unable to login into remote host using ssh key');
                    die();
                }
            } else {
                if (!$ssh->login($this->sshUser, $this->sshPass, Net_SSH2::LOG_SIMPLE)) {
                    $this->io->error('Unable to login into remote host using password');
                    die();
                }
            }

            /*
             * Connection established, let's set those permissions!
             * Just need one more check to see if root and public are separate, or the same
             * (we need to know if app is above public, within the root directory)
             */
            if ($this->configVars->environments->{$this->environment}->public === '') {
                // Public not set, so we're doing it all from root!
                $command = "cd /{$this->configVars->environments->{$this->environment}->root} && {$sudo}find . -type f -exec chmod 644 {} \\;";
                $this->io->text("<remote>Executing remote command:</remote> " . $command);
                $this->io->text($ssh->exec($command));

                $command = "cd /{$this->configVars->environments->{$this->environment}->root} && {$sudo}find . -type d -exec chmod 755 {} \\;";
                $this->io->text("<remote>Executing remote command:</remote> " . $command);

                $this->io->text($ssh->exec($command));
            } else {
                // Set app permissions
                $command = "cd /{$this->configVars->environments->{$this->environment}->root}/{$this->configVars->mappings->app} && {$sudo}find . -type f -exec chmod 644 {} \\;";
                $this->io->text("<remote>Executing remote command:</remote> " . $command);
                $this->io->text($ssh->exec($command));

                $command = "cd /{$this->configVars->environments->{$this->environment}->root}/{$this->configVars->mappings->app} && {$sudo}find . -type d -exec chmod 755 {} \\;";
                $this->io->text("<remote>Executing remote command:</remote> " . $command);
                $this->io->text($ssh->exec($command));

                // Set public permissions
                $command = "cd /{$this->configVars->environments->{$this->environment}->root}/{$this->{$this->environment}->public} && {$sudo}find . -type f -exec chmod 644 {} \\;";
                $this->io->text("<remote>Executing remote command:</remote> " . $command);
                $this->io->text($ssh->exec($command));

                $command = "cd /{$this->configVars->environments->{$this->environment}->root}/{$this->{$this->environment}->public} && {$sudo}find . -type d -exec chmod 755 {} \\;";
                $this->io->text("<remote>Executing remote command:</remote> " . $command);
                $this->io->text($ssh->exec($command));
            }

            // All done!
            $this->io->success(ucfirst($this->action) . " command executed successfully!");
        }

    } // END fixPerms() function

    /**
     * Placeholder for other config classes to implement
     *
     * @param string $migrate_options
     * @return mixed
     */
    public function migrate($migrate_options)
    {
        $this->io->text('<info>The migrate method has no default implementation. Add this as an override method into the framework config class to utilize.</info>');
    }


    /****************************************
     * END helper functions
     ****************************************/

}