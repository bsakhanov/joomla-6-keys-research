<?php
// Лабораторный сценарий стенда Joomla 6.1.4. Кладётся в каталог cli/ рядом с joomla.php и запускается от пользователя веб-сервера.
// Проверяет: метки при сохранении из кода, похожие материалы по общим меткам, чтение настраиваемых полей пользователя классами с пространствами имён. Номера категории и меток — со стенда.

define('_JEXEC', 1);
define('JPATH_BASE', dirname(__DIR__));
define('JPATH_COMPONENT', JPATH_BASE . '/administrator/components/com_content');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
$container = Factory::getContainer();
$container->alias('session','session.cli')->alias('JSession','session.cli')->alias(\Joomla\CMS\Session\Session::class,'session.cli')->alias(\Joomla\Session\Session::class,'session.cli')->alias(\Joomla\Session\SessionInterface::class,'session.cli');
$app = $container->get(\Joomla\Console\Application::class);
Factory::$application = $app;
$app->createExtensionNamespaceMap();
$db = $container->get(DatabaseInterface::class);
$adminId = (int) $db->setQuery($db->createQuery()->select($db->quoteName('id'))->from($db->quoteName('#__users'))->where($db->quoteName('username').' = '.$db->quote('admin')))->loadResult();
$admin = $container->get(UserFactoryInterface::class)->loadUserById($adminId);
$app->loadIdentity($admin);

function saveArticle($app,string $t,string $a,array $tags):int{ try{
  $m=$app->bootComponent('com_content')->getMVCFactory()->createModel('Article','Administrator',['ignore_request'=>true]);
  $ok=$m->save(['id'=>0,'title'=>$t,'alias'=>$a,'articletext'=>'<p>x</p>','catid'=>8,'state'=>1,'access'=>1,'language'=>'*','tags'=>$tags]);
  return $ok?(int)$m->getState('article.id'):-1;}catch(\Throwable $e){echo "   ошибка: ",get_class($e),': ',substr($e->getMessage(),0,120),"\n";return -2;} }
function tagCount($db,int $id):int{$q=$db->createQuery()->select('COUNT(*)')->from($db->quoteName('#__contentitem_tag_map'))->where($db->quoteName('content_item_id').' = :id')->where($db->quoteName('type_alias').' = '.$db->quote('com_content.article'))->bind(':id',$id,ParameterType::INTEGER);return (int)$db->setQuery($q)->loadResult();}

echo "== A. сохранение статьи с метками из скрипта\n";
$a=saveArticle($app,'Проба без behaviour','proba-bez',[2,3]); echo "   без importPlugin('behaviour'): статья #$a, меток: ", ($a>0?tagCount($db,$a):'-'), "\n";
PluginHelper::importPlugin('behaviour');
$b=saveArticle($app,'Проба с behaviour','proba-s',[2,3,4]); echo "   с importPlugin('behaviour'): статья #$b, меток: ", ($b>0?tagCount($db,$b):'-'), "\n";

echo "== B. похожие материалы по общим меткам\n";
$cur = $b>0 ? $b : 3;
$q=$db->createQuery();
$q->select([$db->quoteName('c.id'),$db->quoteName('c.title'),'COUNT('.$db->quoteName('m2.tag_id').') AS '.$db->quoteName('shared')])
  ->from($db->quoteName('#__contentitem_tag_map','m1'))
  ->join('INNER',$db->quoteName('#__contentitem_tag_map','m2').' ON '.$db->quoteName('m2.tag_id').' = '.$db->quoteName('m1.tag_id').' AND '.$db->quoteName('m2.type_alias').' = '.$db->quoteName('m1.type_alias'))
  ->join('INNER',$db->quoteName('#__content','c').' ON '.$db->quoteName('c.id').' = '.$db->quoteName('m2.content_item_id'))
  ->where($db->quoteName('m1.content_item_id').' = :cur')->where($db->quoteName('m1.type_alias').' = '.$db->quote('com_content.article'))
  ->where($db->quoteName('m2.content_item_id').' <> :cur2')->where($db->quoteName('c.state').' = 1')
  ->group([$db->quoteName('c.id'),$db->quoteName('c.title'),$db->quoteName('c.created')])
  ->order([$db->quoteName('shared').' DESC',$db->quoteName('c.created').' DESC'])
  ->bind(':cur',$cur,ParameterType::INTEGER)->bind(':cur2',$cur,ParameterType::INTEGER);
$db->setQuery($q,0,10);
echo "   для #$cur:\n"; foreach($db->loadObjectList() as $r) echo "   #{$r->id} «{$r->title}» — общих меток: {$r->shared}\n";
echo "   маршрут: ", \Joomla\Component\Content\Site\Helper\RouteHelper::getArticleRoute($cur.':proba', 8, '*'), "\n";

echo "== C. поля пользователя: FieldsHelper (замена фрагмента из Joomla 3)\n";
$db->setQuery("INSERT INTO ".$db->quoteName('#__fields')." (asset_id,context,group_id,title,name,label,default_value,type,note,description,state,required,only_use_in_subform,checked_out_time,ordering,params,fieldparams,language,created_time,created_user_id,modified_time,modified_by,access) VALUES (0,'com_users.user',0,'Биография','bio','Биография','','textarea','','',1,0,0,NULL,0,'{\"display\":\"2\",\"showlabel\":\"1\"}','{\"rows\":\"3\",\"cols\":\"30\",\"maxlength\":\"\",\"filter\":\"\"}','*',NOW(),$adminId,NOW(),0,1)")->execute();
$fid=(int)$db->insertid();
$db->setQuery("INSERT INTO ".$db->quoteName('#__fields_values')." (field_id,item_id,value) VALUES ($fid,'$adminId','Редактор отдела экономики')")->execute();
$u = $container->get(UserFactoryInterface::class)->loadUserById($adminId);
$fields = FieldsHelper::getFields('com_users.user', $u, true);
foreach ($fields as $f) echo "   поле «{$f->title}» ({$f->name}) = ", strip_tags((string)$f->value), "\n";
echo "   тип результата: ", get_debug_type($fields), ", элементов: ", count($fields), "\n";
