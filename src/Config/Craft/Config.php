<?php

namespace BMM\CMSMove\Config\Craft;

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

}