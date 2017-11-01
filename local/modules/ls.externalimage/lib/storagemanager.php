<?
namespace Ls\Externalimage;


/**
*  manage шьфпуы storages  and its options
*/
class StorageManager
{
	public static function getStorageList()
	{
		$result = array(array('id' => 'local', 'name' => 'Локально'));
		$event = new \Bitrix\Main\Event(CUR_MODULE_ID, "OnBuildStorageList", array());
		$event->send();
		foreach($event->getResults() as $evenResult)
		{
			if($evenResult->getResultType() == \Bitrix\Main\EventResult::SUCCESS)
			{
				$r = $evenResult->getParameters();
				if(!$r['id'])
					continue;
				$result[] = $r;
			}
		}
		
		return $result;
	}
}

?>
