<?php

/**
 * @package  Lab
 * @license  GNU General Public License version 2 or later
 */


namespace Lab\Plugin\System\Wmlab\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Event\Application\AfterRouteEvent;
use Joomla\CMS\Event\Application\BeforeCompileHeadEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\Event\SubscriberInterface;

final class Wmlab extends CMSPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'onBeforeCompileHead' => 'attachAssets',
            'onAfterRoute'        => 'probe',
        ];
    }

    public function attachAssets(BeforeCompileHeadEvent $event): void
    {
        $app = $this->getApplication();

        if (!$app->isClient('site')) {
            return;
        }

        $wa = $app->getDocument()->getWebAssetManager();

        // короткая форма «расширение/файл»: папку css или js Joomla подставляет сама
        $wa->registerAndUseStyle('wmlab.style', 'plg_system_wmlab/wmlab.css', ['version' => 'auto']);
        $wa->registerAndUseScript('wmlab.script', 'plg_system_wmlab/wmlab.js', ['version' => 'auto'], ['defer' => true], ['core']);
        // полная форма от корня сайта
        $wa->registerAndUseStyle('wmlab.style.full', 'media/plg_system_wmlab/css/wmlab.css', ['version' => 'auto']);
        // ошибочная форма из первой пробы — для сравнения
        $wa->registerAndUseStyle('wmlab.style.wrong', 'plg_system_wmlab/css/wmlab.css', ['version' => 'auto']);
        $wa->addInlineScript('document.documentElement.dataset.wmlab = "1";', ['position' => 'after'], [], ['wmlab.script']);
    }

    public function probe(AfterRouteEvent $event): void
    {
        $app  = $this->getApplication();
        $mode = $app->getInput()->getCmd('wmlab');

        if (!$app->isClient('site') || $mode === '') {
            return;
        }

        if ($mode === 'redirect') {
            $app->enqueueMessage('Сообщение после редиректа', 'message');
            $app->redirect(Uri::root() . 'index.php?wmlab=url', 303);
        }

        if ($mode === 'html') {
            \Joomla\CMS\HTML\HTMLHelper::_('stylesheet', 'plg_system_wmlab/wmlab.css', ['relative' => true, 'version' => 'auto']);
        }

        if ($mode === 'url') {
            $app->enqueueMessage('URL: ' . Uri::getInstance()->toString(), 'notice');
        }

        if ($mode === 'bad') {
            // J3-стиль: второй аргумент — строка сообщения
            $app->redirect(Uri::root(), 'Сообщение', 'message');
        }
    }
}
