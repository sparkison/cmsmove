<?php

namespace BMM\CMSMove\Config\Craft;

use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Helper\QuestionHelper;
use phpseclib\Net\SCP as Net_SCP;
use phpseclib\Net\SSH2 as Net_SSH2;
use phpseclib\Crypt\RSA as Crypt_RSA;
use BMM\CMSMove\Config\Config as BaseConfig;

class Config extends BaseConfig
{

    /**
     * Sync the templates
     */
    public function templates()
    {
        /* Get the template directory from the config file */
        $templateDir = $this->configVars->mappings->app . "/" . $this->configVars->mappings->templates;

        /* Get the remote directory */
        $remoteDir = $this->root . "/" . $templateDir;

        /* Sync it! */
        $this->syncIt($templateDir, $remoteDir, "templates");

    } // END templates() function

    /**
     * Sync the plugins
     */
    public function plugins()
    {
        /* Get the plugins directory from the config file */
        $pluginDir = $this->configVars->mappings->app . "/" . $this->configVars->mappings->plugins;

        /* Get the remote directory */
        $remoteDir = $this->root . "/" . $pluginDir;

        /* Sync it! */
        $this->syncIt($pluginDir, $remoteDir, "plugins");

    } // END plugins() function

    /**
     * Sync the config directory
     */
    public function config()
    {
        /* Get the config directory from the config file */
        $configDir = $this->configVars->mappings->app . "/" . $this->configVars->mappings->config;

        /* Get the remote directory */
        $remoteDir = $this->root . "/" . $configDir;

        /* Sync it! */
        $this->syncIt($configDir, $remoteDir, "config");

    } // END config() function

    /**
     * Sync the app directory
     */
    public function app()
    {
        /* Get the app directory from the config file */
        $appDir = $this->configVars->mappings->app;

        /* Get the remote directory */
        $remoteDir = $this->root . "/" . $appDir;

        /* Sync it! */
        $this->syncIt($appDir, $remoteDir, "app");

    } // END app() function

    /**
     * Sync the www directory
     */
    public function www()
    {
        /* Get the public directory from the config file */
        $publicDir = $this->configVars->mappings->www;

        /* Get the remote directory */
        $remoteDir = $this->root . "/" . $this->public;

        /* Sync it! */
        $this->syncIt($publicDir, $remoteDir, "public");

    } // END www() function

    /**
     * Sync the user defined "custom" directories
     * Will prompt user for the directory they wish to sync
     */
    public function custom()
    {

        /* Get the custom directory array */
        $customDirs = $this->configVars->mappings->custom;

        /* prompt user for the desired custom directory to sync */
        $helper = new QuestionHelper();

        $question = new Question('<comment>Please enter the name of the custom directory:</comment> ', '');
        $customDir = $helper->ask($this->input, $this->output, $question);

        if (property_exists($customDirs, $customDir)) {

            /* Entered a valid custom directory, let's sync it! */
            $title = ucfirst($this->action) . "ing the custom directory: \"$customDir\"...";
            $this->io->title($title);

            /*
                Get the remote/local directory details
                Assuming a full path here
                Will start from the current working directory locally, and the root directory remotely
            */
            $localDir = $customDirs->{$customDir};
            $remoteDir = $this->root . "/" . $localDir;
            $this->syncIt($localDir, $remoteDir, "custom");

        } else {
            /* Didn't find the custom directory entered, inform the user and show the directory so they know */
            $this->io->error("Unable to find a key for the directory you entered: \"$customDir\"");
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

        /* If here, connected successfully to remote host! */
        $command = "mysqldump --opt --add-drop-table --skip-comments --no-create-db --host={$this->dbHost} --user={$this->dbUser} --password={$this->dbPass} --port={$this->dbPort} --databases {$this->database} --result-file=$remoteDb";
        $this->io->text("<info>Executing remote command:</info> " . $command);
        $ssh->exec($command);

        /* Copy the remote dump down to local and remove from remote */
        $scp = new Net_SCP($ssh);
        if (!$scp->get($remoteDb, $remoteToLocal)) {
            $this->io->error('Unable to download remote database dump');
        }

        /* Remove the remote database dump */
        $command = "rm " . $remoteDb;
        $this->io->text("<info>Executing remote command:</info> " . $command);
        $ssh->exec($command);

        /*************************    Determine what we're doing here    *************************/

        /* See if we're issuing a push or a pull */
        if ($this->action === 'pull') {
            $this->io->note('Getting ready to import the remote database');

            /* Adapt the remote database dump, and import it */
            $this->adaptDump($remoteToLocal);

            /* Import it */
            $command = "mysql --host={$localDb->dbHost} --user={$localDb->dbUser} --password={$localDb->dbPass} --port={$localDb->dbPort} --database={$localDb->db} < $remoteToLocal";
            $this->exec($command, false);

            /* Remove the remote copy (since we imported it, no need to keep it, we have the original as a backup) */
            $command = "rm $remoteToLocal";
            $this->exec($command, false);

            /* All done here! */

        } else {
            /* Copy the local database to the remote host */
            $this->io->note('Getting ready to push the local database to the remote host for import');

            /* Adapt the local database dump, and upload it */
            $this->adaptDump($localDbDump);

            /* Copy to the remote host */
            if (!$scp->put($localToRemote, $localDbDump)) {
                $this->io->error('Unable to upload local database dump to remote host');
            }

            /* Import the database on the remote host */
            $command = "mysql --host={$this->dbHost} --user={$this->dbUser} --password={$this->dbPass} --port={$this->dbPort} --database={$this->database} < $localToRemote";
            $this->io->text("<info>Executing remote command:</info> " . $command);
            $ssh->exec($command);

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
     * Use rsync to push/pull using the select source
     *
     * @param $local
     * @param $remote
     * @param string $syncing
     */
    private function syncIt($local, $remote, $syncing = "")
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
    private function exec($command, $success_msg = true)
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
    private function adaptDump($file)
    {

        $contents = file_get_contents($file);
        $contents_arr = explode("\n", $contents);
        $contents = array();
        foreach ($contents_arr as $line) {
            if ((substr($line, 0, 2) !== "--") && (substr($line, 0, 3) !== "USE")) {
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