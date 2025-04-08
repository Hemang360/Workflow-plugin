<?php

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\Workflow\CategoryAssign\Extension\CategoryAssign;

return new class implements ServiceProviderInterface
{
    public function register(Container $container)
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $dispatcher = $container->get(DispatcherInterface::class);
                $plugin     = new CategoryAssign(
                    $dispatcher,
                    (array) PluginHelper::getPlugin('workflow', 'categoryassign')
                );
                $plugin->setApplication(Factory::getApplication());
                $plugin->setDatabase($container->get('DatabaseDriver'));

                return $plugin;
            }
        );
    }
}; 