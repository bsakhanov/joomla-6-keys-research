<?php

/**
 * @package  Lab
 * @license  GNU General Public License version 2 or later
 */


namespace Lab\Plugin\Console\Wmlab\Command;

\defined('_JEXEC') or die;

use Joomla\CMS\Version;
use Joomla\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class HelloCommand extends AbstractCommand
{
    protected static $defaultName = 'wmlab:hello';

    protected function doExecute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->success(\sprintf('Привет, %s! Joomla %s', $input->getOption('name'), (new Version())->getShortVersion()));

        return 0;
    }

    protected function configure(): void
    {
        $this->setDescription('Пример команды из плагина группы console');
        $this->addOption('name', null, InputOption::VALUE_OPTIONAL, 'Имя', 'мир');
    }
}
