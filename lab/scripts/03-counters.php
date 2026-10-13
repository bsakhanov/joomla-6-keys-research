<?php
// Лабораторный сценарий стенда Joomla 6.1.4. Кладётся в каталог cli/ рядом с joomla.php и запускается от пользователя веб-сервера.
// Проверяет: счётчик материалов категории и счётчики материалов по меткам одним запросом. Номера категории и меток — со стенда.

define('_JEXEC', 1);
define('JPATH_BASE', dirname(__DIR__));
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
use Joomla\CMS\Factory;
use Joomla\CMS\Categories\Categories;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\Database\DatabaseInterface;
$c = Factory::getContainer();
$c->alias('session','session.cli')->alias('JSession','session.cli')->alias(\Joomla\CMS\Session\Session::class,'session.cli')->alias(\Joomla\Session\Session::class,'session.cli')->alias(\Joomla\Session\SessionInterface::class,'session.cli');
$app = $c->get(\Joomla\Console\Application::class); Factory::$application = $app; $app->createExtensionNamespaceMap();
$db = $c->get(DatabaseInterface::class);
// 1. счётчик у категории (рецепт jpath)
$cat = Categories::getInstance('Content', ['countItems' => 1])->get(8);
echo "категория 8: numitems = ", var_export($cat->numitems, true), "\n";
$real = (int) $db->setQuery("SELECT COUNT(*) FROM #__content WHERE catid = 8 AND state = 1")->loadResult();
echo "прямой подсчёт опубликованных в категории 8: $real\n";
// 2. счётчик у метки: готовый помощник и один группирующий запрос
$tagId = 3;
try {
    $q = TagsHelper::getTagItemsQuery([$tagId], ['com_content.article']);
    $items = $db->setQuery($q)->loadObjectList();
    echo "TagsHelper::getTagItemsQuery([$tagId], ['com_content.article']): ", count($items), " строк\n";
} catch (\Throwable $e) { echo "TagsHelper: ", get_class($e), ': ', substr($e->getMessage(), 0, 120), "\n"; }
$q2 = $db->createQuery()->select([$db->quoteName('m.tag_id'), 'COUNT(*) AS ' . $db->quoteName('n')])
    ->from($db->quoteName('#__contentitem_tag_map', 'm'))
    ->join('INNER', $db->quoteName('#__content', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('m.content_item_id'))
    ->where($db->quoteName('m.type_alias') . ' = ' . $db->quote('com_content.article'))
    ->where($db->quoteName('c.state') . ' = 1')
    ->group($db->quoteName('m.tag_id'));
foreach ($db->setQuery($q2)->loadObjectList() as $r) { echo "  метка {$r->tag_id}: {$r->n}\n"; }
echo "content_types, com_content.article: id = ", (int) $db->setQuery("SELECT type_id FROM #__content_types WHERE type_alias = 'com_content.article'")->loadResult(), "\n";
