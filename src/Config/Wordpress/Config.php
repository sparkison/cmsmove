<?php

namespace BMM\CMSMove\Config\Wordpress;

use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Helper\QuestionHelper;

use BMM\CMSMove\Config\Config as BaseConfig;

class Config extends BaseConfig
{

    /**
     * Sync the templates
     */
    public function templates()
    {
        /* Get the template directory from the config file */
        $templateDir = $this->configVars->mappings->templates;

        /* Get the remote directory */
        $remoteDir = $this->root . "/" . $templateDir;

        /* Sync it! */
        $this->syncIt($templateDir, $remoteDir, "templates");

    } // END templates() function

    /**
     * Sync the plugins
     * Need to sync both the plugin files and the plugin themes
     */
    public function plugins()
    {
        /* Get the plugins directory from the config file */
        $pluginDir = $this->configVars->mappings->plugins->files;

        /* Get the remote directory */
        $remoteDirApp = $this->root . "/" . $pluginDir;

        /* Sync it the plugins */
        $this->syncIt($pluginDir, $remoteDirApp, "plugins");

    } // END plugins() function

    /**
     * Sync the uploads directory
     */
    public function uploads()
    {
        /* Get the config directory from the config file */
        $uploadsDir = $this->configVars->mappings->uploads;

        /* Get the remote directory */
        $remoteDir = $this->root . "/" . $uploadsDir;

        /* Sync it! */
        $this->syncIt($uploadsDir, $remoteDir, "uploads");

    } // END config() function

    /**
     * Sync the WordPress core
     */
    public function core()
    {

        // Get all the folders to sync
        $folders = [
            'wp-content/upgrade' => 'wp-content/upgrade',
            'wp-admin' => 'wp-admin'
        ];
        // Get all the files to sync
        $files = [
            'wp-content/index.php' => 'wp-content/index.php',
            'wp-content/db.php' => 'wp-content/db.php'
        ];

        foreach ($folders as $source => $destination) {
            /* Sync it! */
            $this->syncIt($source, $destination, "core");
        }
        foreach ($files as $source => $destination) {
            /* Sync it! */
            $this->syncIt($source, $destination, "core", true, true);
        }

    } // END core() function

    /**
     * Sync the everything
     */
    public function all()
    {
        /* Get the public directory from the config file */
        $publicDir = "/";

        /* Get the remote directory */
        $remoteDir = $this->root . "/";

        /* Sync it! */
        $this->syncIt($publicDir, $remoteDir, "all");

    } // END all() function

    /**
     * Override the adapt dump function so we can change the remote/local urls as needed
     *
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
                        $contents[] = $this->replaceDbHostName($line);
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
     * Replaces the remote/local host string in the DB as needed
     *
     * @param $string
     * @return mixed
     */
    private function replaceDbHostName($string)
    {
        // First, let's get the local and remote host since we're going to need them
        if(! (property_exists($this->configVars->environments->local, 'vhost') || property_exists($this->configVars->environments->{$this->environment}, 'vhost')) ) {
            $this->io->error("Error getting vhost for local and remote destinations. Please ensure each environment has the \"vhost\" key set");
            die();
        }
        $localHost = $this->configVars->environments->local->vhost;
        $remoteHost = $this->configVars->environments->{$this->environment}->vhost;

        // Second, determine what we're doing
        if ($this->action === 'pull') {
            // We're pulling the remote db into local, replace host accordingly
            // $string = str_replace($remoteHost, $localHost, $string);
            $string = $this->recursiveUnSerializeReplace($remoteHost, $localHost, $string);
        } else {
            // We're pushing the local database to remote, replace host accordingly
            // $string = str_replace($localHost, $remoteHost, $string);
            $string = $this->recursiveUnSerializeReplace($localHost, $remoteHost, $string);
        }
        return $string;
    }

}