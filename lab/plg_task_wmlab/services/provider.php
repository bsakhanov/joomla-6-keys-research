<?php

/**
 * @package  Lab
 * @license  GNU General Public License version 2 or later
 */


\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Lab\Plugin\Task\Wmlab\Extension\Wmlab;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            $container->lazy(Wmlab::class, function (Container $container) {
                $plugin = new Wmlab(
                    $container->get(DispatcherInterface::class),
                    (array) PluginHelper::getPlugin('task', 'wmlab')
                );
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            })
        );
    }
};
