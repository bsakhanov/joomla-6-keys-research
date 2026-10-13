<?php

/**
 * @package  Lab
 * @license  GNU General Public License version 2 or later
 */


namespace Lab\Plugin\Console\Wmlab\Extension;

\defined('_JEXEC') or die;

use Joomla\Application\ApplicationEvents;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Lab\Plugin\Console\Wmlab\Command\ArticleCommand;
use Lab\Plugin\Console\Wmlab\Command\HelloCommand;

final class Wmlab extends CMSPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [ApplicationEvents::BEFORE_EXECUTE => 'registerCommands'];
    }

    public function registerCommands(): void
    {
        $this->getApplication()->addCommand(new HelloCommand());
        $this->getApplication()->addCommand(new ArticleCommand());
    }
}
