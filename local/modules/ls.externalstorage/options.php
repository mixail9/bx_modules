<?
use \Ls\Externalstorage\BackupHelper;
define('CUR_MODULE_ID', 'ls.externalstorage');
CJSCore::Init(array('jquery', 'window'));
\Bitrix\Main\Loader::IncludeModule(CUR_MODULE_ID);

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/options.php");
$storageList = \Ls\Externalstorage\Factory::getStorageList();
foreach($storageList as $storage)
	$tabs[] = array('DIV' => $storage['id'], 'TAB' => $storage['name'], 'TITLE' => $storage['name']);
$tabControl = new CAdminTabControl("tabControl", $tabs);




function optionField(&$option, $storage)
{
	$id = rand(1000, 9999);
	switch($option['type'])
	{
		case 'text':
			print '<tr><td width="40%"><label for="' . $option['name'] . $id . '">' . $option['title'] . '</label></td>';
			print '<td width="60%"><input type="text" name="' . $storage . '[' . $option['name'] . ']" value="' . $option['value'] . '" id="' . $option['name'] . $id . '"></td></tr>';
			break;
		default:
			break;
	}
}



if($_POST['save'] && check_bitrix_sessid())
{
	foreach($storageList as &$storage)
	{
		$fileStorage = file_get_contents($storage['file']);
		$isFileChanged = false;
		foreach($_POST[$storage['id']] as $optionName => $optionValue)
		{
			$pattern = '/\$' . $optionName . '[\s]?=[\s]?[\'"](.*?)[\'|"];/sm';
			preg_match($pattern, $fileStorage, $oldValue);
			if($oldValue[1] != $optionValue)
				$isFileChanged = true;
			$storage['options'][$optionName]['value'] = $optionValue;
			$fileStorage = preg_replace($pattern, "\$$optionName = '$optionValue';", $fileStorage);
		}
		
		if($isFileChanged)
			file_put_contents($storage['file'], $fileStorage);
	}
}

$tabs = array();
foreach($storageList as $storage)
	$tabs[] = array('DIV' => 'tab_' . $storage['id'], 'TAB' => $storage['name'], 'TITLE' => $storage['name']);	
$tabs[] = array('DIV' => 'tab_common', 'TAB' => 'Бекапы', 'TITLE' => 'Бекапы');
$tabControl = new CAdminTabControl("tabControl", $tabs);


$storageChooseHtml = '<div id="local_to_remote_id"><select name="local_to_remote_id" id="local_to_remote_id_val">';
foreach($storageList as $storage)
	$storageChooseHtml .= '<option value="' . $storage['id'] . '">' . $storage['name'];
$storageChooseHtml .= '</select></div>';

//<link rel="stylesheet" href="/bitrix/css/main/bootstrap.css" />
?>
<script>
function chooseStorageDialog(backupId)
{
	if(backupList[backupId].storage != 'local')
		return false;
	backup = backupList[backupId].name;
	console.log('backup=', backup);
	var w = BX.showWait('local_to_remote_id');
	var dialogStorage = {
		title: 'choose storage',
		content: '<?=$storageChooseHtml?>',
		width: '300',
		height: '80',
		buttons: [
			BX.CDialog.prototype.btnClose, 
			{
				title: 'upload',
				id: 'upload_id',
				action: function() {

					if(backupList[backupId].raw_size > $('#tab_' + backupList[backupId].storage_id + '_size').data('max_size'))
						return false;
					console.log(backupList[backupId].raw_size, $('#tab_' + backupList[backupId].storage_id + '_size').data('max_size'));
					BX.ajax.get('<?=str_replace($_SERVER['DOCUMENT_ROOT'], '', __DIR__) . '/ajax.php'?>', {
						sessid: BX.bitrix_sessid(),
						storage: BX('local_to_remote_id_val').value,
						action: 'upload',
						backup: backup
					}, function(result) {
						BX.closeWait('local_to_remote_id', w);
						console.log(result);
					});
				}
			}
		]
	};
	(new BX.CDialog(dialogStorage)).Show();
}

function deleteDialog(backup, storage)
{
	console.log('deleteDialog backup=', backup);
	var w;
	var dialogStorage = {
		title: '',
		content: 'are you sure?',
		width: '300',
		height: '80',
		buttons: [
			BX.CDialog.prototype.btnClose, 
			{
				title: 'delete',
				id: 'delete_id',
				action: function() {
					w = BX.showWait('local_to_remote_id');
					BX.ajax.get('<?=str_replace($_SERVER['DOCUMENT_ROOT'], '', __DIR__) . '/ajax.php'?>', {
						sessid: BX.bitrix_sessid(),
						storage: storage,
						action: 'delete',
						backup: backup
					}, function(result) {
						BX.closeWait('local_to_remote_id', w);
						console.log(result);
					});
				}
			}
		]
	};
	(new BX.CDialog(dialogStorage)).Show();
}
</script>
<form method="post" action="<?echo $APPLICATION->GetCurPageParam()?>">
<?
print bitrix_sessid_post();
$tabControl->Begin();


foreach($storageList as $storage)
{
	$tabControl->BeginNextTab();
	foreach($storage['options'] as $option)
		optionField($option, $storage['id']);
		
	$size = $storage['state']['total_space'] - $storage['state']['used_space'];
	$maxSize = $size;
	BackupHelper::formatSize($size);
	print '<tr><td width="40%">free space<td><span id="tab_' . $storage['id'] . '_size" data-max_size="' . $maxSize . '">' . $size . '</span></tr>';
	print '<tr><td><td width="60%"><input type="submit" class="adm-btn-save" name="save" value="Применить"></tr>';
	//print_r($storage['options']);
}


$tabControl->BeginNextTab();

$backupList = \Ls\Externalstorage\BackupHelper::getLocalBackupList();
foreach($storageList as $storage)
{
	$remoteBackupList = BackupHelper::getRemoteBackupList($storage['obj']);
	foreach($remoteBackupList as $backup)
		$backupList[] = $backup;
}

$localListId = 'local_list';
$localList = new CAdminList();
$localList->AddHeaders(array(
	array('id' => 'name', 'content' => 'name', 'default' => true), 
	array('id' => 'storage', 'content' => 'storage', 'default' => true), 
	array('id' => 'size', 'content' => 'size', 'default' => true)
));
$id = 0;
foreach($backupList as $backupId => $backup)
{
	$row = &$localList->AddRow(++$id, $backup);
	$row->AddInputField("name", array("size"=>20));
	$row->AddViewField('name', $backup['name']);
	$row->AddActions(array(
		array('ICON' => 'insert', 'DEFAULT' => true, 'TEXT' => 'move to remote server', 'ACTION' => "chooseStorageDialog('{$backupId}');"),
		array('ICON' => 'delete', 'TEXT' => 'delete', 'ACTION' => "deleteDialog('{$backup['name']}', '{$backup['storage_id']}');")
	));
}

print '<div class="row"><div class="col-md-6 col-sm-6">';
$localList->DisplayList();
print '</div>';
print '<div class="col-md-6 col-sm-6"></div>';
	
print '</div>';



$tabControl->EndTab();
$tabControl->Buttons();
$tabControl->End();?>
<script>
	var backupList = <?=CUtil::PhpToJSObject($backupList);?>;
	console.log(backupList);
</script>

</form>

