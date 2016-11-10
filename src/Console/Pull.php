<?php

namespace BMM\CMSMove\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use BMM\CMSMove\ConfigGenerators;

class Pull extends Command
{
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
        if(!file_exists($configFile)) {
            $io->error("No config file found. Please run the \"config\" command first.");
            return;
        }

        new ConfigGenerators\Config\Config($configFile);

    }
}