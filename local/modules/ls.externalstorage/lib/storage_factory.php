<?php
namespace Ls\Externalstorage;

class Factory
{

	protected function __construct()
	{
	}
	
	
	protected static function getStorageClsssFile($storageId)
	{
		return __DIR__ . '/z_' . toLower($storageId) . '.php';
	}
	

	public static function getStorage($storageId)
	{
		$className = static::getStorageStatic($storageId);
		$tmp = new $className();
		$tmp->authorize();
		return $tmp;
	}
	

	public static function getStorageStatic($storageId)
	{
		if(!$storageId || !file_exists(static::getStorageClsssFile($storageId)))
			throw new \Exception('storage not found');

		$storageId = toLower($storageId);
		$className = '\Ls\Externalstorage\\' . ucfirst($storageId) . 'Storage';
		$curStorage = array($className => 'lib/z_' .  $storageId . '.php');
		\Bitrix\Main\Loader::registerAutoLoadClasses('ls.externalstorage', $curStorage);
		if(!class_exists($className))
			throw new \Exception('storage class not found');
		return $className;
	}


	public static function getStorageNameList()
	{
		$storageList = array();
		$dir = new \DirectoryIterator(__DIR__);
		foreach($dir as $file)
		{
			if($file->isFile() && preg_match('/^z_(.*?)\.php$/', $file->getFilename(), $storage))
			{
				$storageList[$storage[1]] = $storage[1];
			}
		}
		
		return $storageList;
	}
	
	
	public static function getStorageList()
	{
		$storageList = array();
		$storageNameList = static::getStorageNameList();
		foreach($storageNameList as $storageId)
		{
			//$class = static::getStorageStatic($storageId);
			$class = static::getStorage($storageId);
			$storageList[$storageId] = array(
				'options' => $class::getOptions(),
				'name' => $class::getName(),
				'id' => $class::getId(),
				'obj' => $class,
				'state' => $class->getInfo(),
				'file' => static::getStorageClsssFile($storageId),
			);
			
			
		}
		
		return $storageList;
	}
}

?>
