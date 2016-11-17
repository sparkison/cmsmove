<?php

namespace BMM\CMSMove\Config\Ee;

use ZipArchive;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
        $this->syncIt($templateDir, $remoteDir);

    } // END templates() function

    /**
     * Sync the plugins
     * Need to sync both the plugin files and the plugin themes
     */
    public function plugins()
    {
        /* Get the plugins directory from the config file */
        $pluginDir = $this->configVars->mappings->app . "/" . $this->configVars->mappings->plugins->files;
        $pluginThemeDir = $this->configVars->mappings->www . "/" . $this->configVars->mappings->plugins->templates;

        /* Get the remote directory */
        $remoteDirApp = $this->root . "/" . $pluginDir;
        $remoteDirPublic = $this->root . "/" . $this->public . "/" . $this->configVars->mappings->plugins->templates;

        /* Sync it the plugins */
        $this->syncIt($pluginDir, $remoteDirApp);
        $this->syncIt($pluginThemeDir, $remoteDirPublic);

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
        $this->syncIt($configDir, $remoteDir);

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
        $this->syncIt($appDir, $remoteDir);

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
        $this->syncIt($publicDir, $remoteDir);

    } // END www() function

    /**
     * Sync the user defined "custom" directories
     * Will prompt user for the directory they wish to sync
     */
    public function custom()
    {
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
        $remoteDb = "/tmp/" . $this->environment . "_db_" . $timestamp . ".sql";

        /* Get the local DB info */
        $localDb = $this->configVars->environments->local;

        /*************************    Make local and remote backups    *************************/

        /* Step 1. make copy of local database */
        $command = "mysqldump --opt --add-drop-table --skip-comments --no-create-db --user={$localDb->dbUser} --password={$localDb->dbPass} --port={$localDb->dbPort} --databases {$localDb->db} --result-file=$localDbDump";

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
        $command = "mysqldump --opt --add-drop-table --skip-comments --no-create-db --user={$this->dbUser} --password={$this->dbPass} --port={$this->dbPort} --databases {$this->database} --result-file=$remoteDb";
        $this->io->text("Executing remote command: " . $command);
        $this->io->text($ssh->exec($command));

        /* Copy the remote dump down to local and remove from remote */
        $scp = new Net_SCP($ssh);
        if (!$scp->get($remoteDb, $backupDir . "/" . $this->environment . "_db_" . $timestamp . ".sql"))
        {
            $this->io->error('Unable to download remote database dump');
        }

        /* Remove the remote database dump */
        $command = "rm " . $remoteDb;
        $this->io->text("Executing remote command: " . $command);
        $this->io->text($ssh->exec($command));

        $this->io->success("Completed {$this->action}ing database!");

        // echo $ssh->getLog();

    } // END database() function


    /****************************************
     * BEGIN Sync helper functions
     ****************************************/

    /**
     * Use rsync to push/pull using the select source
     *
     * @param $source
     * @param $dest
     */
    private function syncIt($source, $dest)
    {
        $cwd = getcwd();
        $ssh = "";

        /**
         * Remove any trailing slashes from source/dest as it could mess up the sync
         */
        $source = rtrim($source, "/");
        $dest = rtrim($dest, "/");

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
        $title = ucfirst($this->action) . "ing templates...";
        $this->io->title($title);

        // Determine if push or pull
        if ($this->action === 'pull') {
            $command = "rsync {$ssh} --progress -rlpt --compress --omit-dir-times --delete {$this->sshUser}@{$this->host}:/{$dest}/ {$cwd}/{$source}";
        } else if ($this->action === 'push') {
            $command = "rsync {$ssh} --progress -rlpt --compress --omit-dir-times --delete {$cwd}/{$source}/ {$this->sshUser}@{$this->host}:/{$dest}";
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
            $this->io->text("Executing command: " . $command);
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

    } // END exec() fucntion

    /****************************************
     * END Sync helper functions
     ****************************************/

}
