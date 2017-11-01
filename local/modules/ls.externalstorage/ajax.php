<?php
include($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

if(!$GLOBALS['USER']->isAdmin() || !check_bitrix_sessid())
	die('ok');
	
use \Ls\Externalstorage\Factory,
	\Ls\Externalstorage\BackupHelper;


define('CUR_MODULE_ID', 'ls.externalstorage');
\Bitrix\Main\Loader::IncludeModule(CUR_MODULE_ID);


switch($_REQUEST['action'])
{
	case 'upload':
		$storage = Factory::getStorage($_REQUEST['storage']);
		$files = BackupHelper::chooseBackupFiles($_REQUEST['backup'], BackupHelper::getLocalBackupList(true));
		var_dump($storage->upload($files, array(), true, true));
		print_r($files);
		break;
	case 'download':
		break;
	case 'delete':
		$storage = Factory::getStorage($_REQUEST['storage']);
		$tmp = BackupHelper::getRemoteBackupList($storage, true);
		print 'BackupHelper::getRemoteBackupList ';
		print_r($tmp);
		$files = BackupHelper::chooseBackupFiles($_REQUEST['backup'], BackupHelper::getRemoteBackupList($storage, true));
		var_dump($storage->delete($files));
		print_r($files);
		break;
}

?>
