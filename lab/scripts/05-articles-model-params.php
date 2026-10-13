<?php
// Лабораторный сценарий стенда Joomla 6.1.4. Кладётся в каталог cli/ рядом с joomla.php и запускается от пользователя веб-сервера.
// Проверяет: модель списка материалов с состоянием params; наличие поля slug; маршрут к материалу. Номера категории и меток — со стенда.

define('_JEXEC', 1);
define('JPATH_BASE', dirname(__DIR__));
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\Database\DatabaseInterface;
$c = Factory::getContainer();
$c->alias('session','session.cli')->alias('JSession','session.cli')->alias(\Joomla\CMS\Session\Session::class,'session.cli')->alias(\Joomla\Session\Session::class,'session.cli')->alias(\Joomla\Session\SessionInterface::class,'session.cli');
$app = $c->get(\Joomla\Console\Application::class); Factory::$application = $app; $app->createExtensionNamespaceMap();
$db = $c->get(DatabaseInterface::class);
try {
    $model = $app->bootComponent('com_content')->getMVCFactory()->createModel('Articles', 'Site', ['ignore_request' => true]);
    $model->setState('params', ComponentHelper::getParams('com_content'));
    $model->setState('filter.published', 1); $model->setState('filter.category_id', 8); $model->setState('list.limit', 5);
    $items = $model->getItems();
    echo "Articles/Site с состоянием params: ", count($items), " материалов; slug первого: ", var_export($items[0]->slug ?? null, true), "; catslug: ", var_export($items[0]->catslug ?? null, true), "\n";
    echo "маршрут: ", \Joomla\Component\Content\Site\Helper\RouteHelper::getArticleRoute($items[0]->slug, $items[0]->catid, $items[0]->language), "\n";
} catch (\Throwable $e) { echo "Articles/Site: ", get_class($e), ': ', substr($e->getMessage(), 0, 140), ' @ ', basename($e->getFile()), ':', $e->getLine(), "\n"; }
try { $th = new TagsHelper(); $q = $th->getTagItemsQuery([3], ['com_content.article']); echo "TagsHelper OK\n"; }
catch (\Throwable $e) { echo "TagsHelper: ", get_class($e), ': ', $e->getMessage(), ' @ ', basename($e->getFile()), ':', $e->getLine(), "\n"; foreach (array_slice($e->getTrace(), 0, 3) as $t) { echo "   ", ($t['class'] ?? ''), '::', $t['function'] ?? '', ' ', basename($t['file'] ?? ''), ':', $t['line'] ?? '', "\n"; } }
