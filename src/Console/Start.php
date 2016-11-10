<?php

namespace BMM\CMSMove\Console;

use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Start extends Command
{
    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('config')
            ->setDescription('Create project configuration file')
            ->addArgument('framework', InputArgument::REQUIRED);
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
        $framework = $input->getArgument('framework');
        $configPath = __DIR__ . '/../ConfigGenerators/' . $framework;
        $configClass = $configPath . '/config.php';

        if(!file_exists($configClass)) {
            throw new InvalidArgumentException("Could not find configuration class for `$framework`");
        }

        $configFile = $configPath . '/config.json';

        if (!file_exists($configFile)) {
            throw new InvalidArgumentException("No starter config file found for `$framework`. Please ensure there is a config.json file with configuration defaults to continue.");
        }

        copy($configFile, getcwd() . '/cmsmove-' . $framework . '.json');

        $output->writeln('<comment>Config file created for ' . $framework . '!</comment>');
    }
}