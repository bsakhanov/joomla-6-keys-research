<?php
// Лабораторный сценарий стенда Joomla 6.1.4. Кладётся в каталог cli/ рядом с joomla.php и запускается от пользователя веб-сервера.
// Проверяет: вызов getTagItemsQuery с типом содержимого в виде номера, массива и алиаса. Номера категории и меток — со стенда.

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
$th = new TagsHelper();
foreach ([['номер типа 1 (как в рецепте jpath)', 1], ['массив [1]', [1]], ['алиас строкой', 'com_content.article'], ['массив алиасов', ['com_content.article']], ['null (все типы)', null]] as [$label, $arg]) {
    try { $q = $th->getTagItemsQuery([3], $arg); $n = count($db->setQuery($q)->loadObjectList()); echo str_pad($label, 36), '→ ', $n, " строк\n"; }
    catch (\Throwable $e) { echo str_pad($label, 36), '→ ', get_class($e), ': ', substr($e->getMessage(), 0, 60), "\n"; }
}
