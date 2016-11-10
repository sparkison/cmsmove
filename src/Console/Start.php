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
        $configPath = __DIR__ . '/../ConfigGenerator/' . $framework . '/config.json';

        if (!file_exists($configPath)) {
            throw new InvalidArgumentException("Could not find configuration data for `$framework`");
        }

        copy($configPath, getcwd() . '/cmsmove-' . $framework . '.json');

        $output->writeln('<comment>Config file created!</comment>');
    }
}