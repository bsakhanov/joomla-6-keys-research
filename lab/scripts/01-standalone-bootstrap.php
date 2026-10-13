<?php
// Лабораторный сценарий стенда Joomla 6.1.4. Кладётся в каталог cli/ рядом с joomla.php и запускается от пользователя веб-сервера.
// Проверяет: загрузка Joomla вне веб-контекста; метки при сохранении материала без плагинов поведения и с ними; запрос похожих материалов. Номера категории и меток — со стенда.

// Лабораторный скрипт: проверка начальной загрузки Joomla 6 вне веб-контекста
define('_JEXEC', 1);
define('JPATH_BASE', dirname(__DIR__));
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')
    ->alias(\Joomla\Session\SessionInterface::class, 'session.cli');
$app = $container->get(\Joomla\CMS\Application\ConsoleApplication::class);
Factory::$application = $app;

$app->createExtensionNamespaceMap();
echo "1. ArticleTable после createExtensionNamespaceMap(): ", (class_exists('Joomla\\Component\\Content\\Administrator\\Table\\ArticleTable') ? 'найден' : 'НЕ найден'), "\n";

$db = $container->get(DatabaseInterface::class);
$adminId = (int) $db->setQuery($db->createQuery()->select($db->quoteName('id'))->from($db->quoteName('#__users'))->where($db->quoteName('username') . ' = ' . $db->quote('admin')))->loadResult();
$app->loadIdentity($container->get(UserFactoryInterface::class)->loadUserById($adminId));

function saveArticle($app, string $title, string $alias, array $tags): int {
    $model = $app->bootComponent('com_content')->getMVCFactory()->createModel('Article', 'Administrator', ['ignore_request' => true]);
    $ok = $model->save(['id' => 0, 'title' => $title, 'alias' => $alias, 'articletext' => '<p>Текст</p>', 'catid' => 8, 'state' => 1, 'access' => 1, 'language' => '*', 'tags' => $tags]);
    return $ok ? (int) $model->getState('article.id') : 0;
}
function tagCount(DatabaseInterface $db, int $id): int {
    $q = $db->createQuery()->select('COUNT(*)')->from($db->quoteName('#__contentitem_tag_map'))
        ->where($db->quoteName('content_item_id') . ' = :id')->where($db->quoteName('type_alias') . ' = ' . $db->quote('com_content.article'))->bind(':id', $id, ParameterType::INTEGER);
    return (int) $db->setQuery($q)->loadResult();
}

$a = saveArticle($app, 'Без плагина behaviour', 'bez-behaviour', [2, 3]);
echo "3. без importPlugin('behaviour'): статья #$a, меток в карте: ", tagCount($db, $a), "\n";

PluginHelper::importPlugin('behaviour');
$b = saveArticle($app, 'С плагином behaviour', 's-behaviour', [2, 3, 4]);
echo "4. после importPlugin('behaviour'): статья #$b, меток в карте: ", tagCount($db, $b), "\n";

// «похожие материалы» по общим меткам
$q = $db->createQuery();
$q->select([$db->quoteName('c.id'), $db->quoteName('c.title'), 'COUNT(' . $db->quoteName('m2.tag_id') . ') AS ' . $db->quoteName('shared')])
  ->from($db->quoteName('#__contentitem_tag_map', 'm1'))
  ->join('INNER', $db->quoteName('#__contentitem_tag_map', 'm2') . ' ON ' . $db->quoteName('m2.tag_id') . ' = ' . $db->quoteName('m1.tag_id')
      . ' AND ' . $db->quoteName('m2.type_alias') . ' = ' . $db->quoteName('m1.type_alias'))
  ->join('INNER', $db->quoteName('#__content', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('m2.content_item_id'))
  ->where($db->quoteName('m1.content_item_id') . ' = :cur')
  ->where($db->quoteName('m1.type_alias') . ' = ' . $db->quote('com_content.article'))
  ->where($db->quoteName('m2.content_item_id') . ' <> :cur2')
  ->where($db->quoteName('c.state') . ' = 1')
  ->group([$db->quoteName('c.id'), $db->quoteName('c.title'), $db->quoteName('c.created')])
  ->order([$db->quoteName('shared') . ' DESC', $db->quoteName('c.created') . ' DESC'])
  ->bind(':cur', $b, ParameterType::INTEGER)->bind(':cur2', $b, ParameterType::INTEGER);
$db->setQuery($q, 0, 10);
echo "5. похожие на #$b:\n";
foreach ($db->loadObjectList() as $r) { echo "   #{$r->id} «{$r->title}» — общих меток: {$r->shared}\n"; }

// маршрут к материалу
$slug = $b . ':s-behaviour';
echo "6. RouteHelper: ", \Joomla\Component\Content\Site\Helper\RouteHelper::getArticleRoute($slug, 8, '*'), "\n";
