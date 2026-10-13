<?php
// Лабораторный сценарий стенда Joomla 6.1.4. Кладётся в каталог cli/ рядом с joomla.php и запускается от пользователя веб-сервера.
// Проверяет: список материалов через модель сайта, свойство input приложения, категории через компонент, помощник меток. Номера категории и меток — со стенда.

define('_JEXEC', 1);
define('JPATH_BASE', dirname(__DIR__));
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\Database\DatabaseInterface;
$c = Factory::getContainer();
$c->alias('session','session.cli')->alias('JSession','session.cli')->alias(\Joomla\CMS\Session\Session::class,'session.cli')->alias(\Joomla\Session\Session::class,'session.cli')->alias(\Joomla\Session\SessionInterface::class,'session.cli');
$app = $c->get(\Joomla\Console\Application::class); Factory::$application = $app; $app->createExtensionNamespaceMap();
$db = $c->get(DatabaseInterface::class);
// (b) пример jpath: список материалов через модель сайта
try {
    $model = $app->bootComponent('com_content')->getMVCFactory()->createModel('Articles', 'Site', ['ignore_request' => true]);
    $model->setState('filter.published', 1); $model->setState('filter.category_id', 8); $model->setState('list.limit', 5);
    $items = $model->getItems();
    echo "Articles/Site: ", count($items), " материалов; slug первого: ", var_export($items[0]->slug ?? null, true), "; catslug: ", var_export($items[0]->catslug ?? null, true), "\n";
    echo "маршрут: ", \Joomla\Component\Content\Site\Helper\RouteHelper::getArticleRoute($items[0]->slug ?? ($items[0]->id . ':' . $items[0]->alias), $items[0]->catid, $items[0]->language), "\n";
} catch (\Throwable $e) { echo "Articles/Site: ", get_class($e), ': ', substr($e->getMessage(), 0, 140), "\n"; }
// (c) $app->input
try { $in = $app->input; echo "\$app->input: ", get_class($in), "\n"; } catch (\Throwable $e) { echo "\$app->input: ", get_class($e), ': ', substr($e->getMessage(), 0, 100), "\n"; }
echo "\$app->getInput(): ", get_class($app->getInput()), "\n";
// (g) категория через компонент
try { echo "getCategory(): numitems = ", $app->bootComponent('com_content')->getCategory(['countItems' => true])->get(8)->numitems, "\n"; } catch (\Throwable $e) { echo "getCategory(): ", get_class($e), ': ', substr($e->getMessage(), 0, 100), "\n"; }
// (h) TagsHelper на экземпляре, параметры по умолчанию
try { $q = (new TagsHelper())->getTagItemsQuery([3], ['com_content.article']); echo "TagsHelper (экземпляр, по умолчанию): ", count($db->setQuery($q)->loadObjectList()), " материалов у метки 3\n"; } catch (\Throwable $e) { echo "TagsHelper: ", get_class($e), ': ', substr($e->getMessage(), 0, 100), "\n"; }
