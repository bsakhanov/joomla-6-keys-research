<?php

/**
 * @package  Lab
 * @license  GNU General Public License version 2 or later
 */


namespace Lab\Plugin\Task\Wmlab\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status as TaskStatus;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Event\SubscriberInterface;

final class Wmlab extends CMSPlugin implements SubscriberInterface
{
    use TaskPluginTrait;

    protected const TASKS_MAP = [
        'plg_task_wmlab_hello' => [
            'langConstPrefix' => 'PLG_TASK_WMLAB_HELLO',
            'method'          => 'hello',
        ],
    ];

    protected $autoloadLanguage = true;

    public static function getSubscribedEvents(): array
    {
        return [
            'onTaskOptionsList' => 'advertiseRoutines',
            'onExecuteTask'     => 'standardRoutineHandler',
        ];
    }

    private function hello(ExecuteTaskEvent $event): int
    {
        $this->logTask('wmlab hello task started');
        file_put_contents(JPATH_ROOT . '/tmp/wmlab-task.txt', date('c') . "\n", FILE_APPEND);

        return TaskStatus::OK;
    }
}
