<?php
// Лабораторная команда стенда Joomla 6.1.4: создаёт материал и записывает значение настраиваемого поля.
// Номера категории (8) и поля (2) и логин «admin» взяты со стенда — для своего сайта их заменяют.

namespace Lab\Plugin\Console\Wmlab\Command;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ArticleCommand extends AbstractCommand
{
    protected static $defaultName = 'wmlab:article';

    protected function doExecute(InputInterface $input, OutputInterface $output): int
    {
        $io  = new SymfonyStyle($input, $output);
        $app = Factory::getApplication();
        $app->loadIdentity(Factory::getContainer()->get(UserFactoryInterface::class)->loadUserByUsername('admin'));
        if (!\defined('JPATH_COMPONENT')) { \define('JPATH_COMPONENT', JPATH_ADMINISTRATOR . '/components/com_content'); }
        $io->writeln('JPATH_COMPONENT определена: ' . (\defined('JPATH_COMPONENT') ? 'да' : 'нет'));

        try {
            $model   = $app->bootComponent('com_content')->getMVCFactory()->createModel('Article', 'Administrator', ['ignore_request' => true]);
            $article = ['id' => 0, 'title' => 'Статья из консольной команды', 'alias' => '', 'introtext' => '<p>Текст</p>', 'catid' => 8, 'state' => 0, 'language' => '*', 'access' => 1];
            $form    = $model->getForm($article, false);
            if (!$form) { $io->error('форма: ' . $model->getError()); return 1; }
            if (!$model->validate($form, $article)) { $io->error('валидация: ' . $model->getError()); return 1; }
            $app->getInput()->set('task', 'save');
            if (!$model->save($article)) { $io->error('сохранение: ' . $model->getError()); return 1; }
            $id = (int) $model->getState('article.id');
            $io->writeln("статья #$id создана, алиас: " . $model->getItem($id)->alias);
            $fm = $app->bootComponent('com_fields')->getMVCFactory()->createModel('Field', 'Administrator', ['ignore_request' => true]);
            $field = $fm->getItem(2);
            $io->writeln('getItem(2): ' . (\is_object($field) ? ('объект, id=' . ($field->id ?? 'нет') . ', context=' . ($field->context ?? 'нет')) : var_export($field, true)));
            $can = \is_object($field) && \Joomla\Component\Fields\Administrator\Helper\FieldsHelper::canEditFieldValue($field);
            $io->writeln('canEditFieldValue: ' . var_export($can, true) . '; identity id=' . $app->getIdentity()->id . ', root=' . var_export($app->getIdentity()->authorise('core.admin'), true));
            $admin = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserByUsername('admin');
            Factory::getSession()->set('user', $admin);   // canEditFieldValue() смотрит на Factory::getUser()
            $io->writeln('canEditFieldValue после правки сессии: ' . var_export(\Joomla\Component\Fields\Administrator\Helper\FieldsHelper::canEditFieldValue($field), true));
            $ok = $fm->setFieldValue(2, $id, 'Интерфакс');
            $io->writeln('setFieldValue вернул: ' . var_export($ok, true) . '; ошибка модели: ' . var_export($fm->getError(), true));
        } catch (\Throwable $e) {
            $io->error(\get_class($e) . ': ' . $e->getMessage());
            return 1;
        }
        return 0;
    }
}
