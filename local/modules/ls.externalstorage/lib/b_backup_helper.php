<?
namespace Ls\Externalstorage;

class BackupHelper
{
	public static $files;

	public static function isFileFromBackup($file)
	{
		return preg_match('/^(.*?)\.tar\.gz(\.[\d]{1,3})?$/', $file);
	}
	
	public static function formatSize(&$size)
	{
		$sizeSuff = array(' b', ' Kb', ' Mb', ' Gb', ' Tb');
		$i = 0;
		while($size > 1000)
		{
			$i++;
			$size = round($size / 1024);				
		}
		$size .= $sizeSuff[$i];
	}

	public static function sliceFormalBackup($files)
	{
		$backups = array();
		foreach($files as $file)
		{
			if(!static::isFileFromBackup($file['name']))
				continue;
			$file['name'] = preg_replace('/^(.*?)(\.[\w]{1,3})?(\.[\w]{1,3})?(\.[\d]{1,3})?$/', '$1', $file['name']);
			if(!$backups[$file['name']])
				$backups[$file['name']] = $file;
			else
				$backups[$file['name']]['size'] += $file['size'];
		}
		foreach($backups as &$backup)
		{
			$backup['raw_size'] = $backup['size'];
			static::formatSize($backup['size']);
		}
		return $backups;
	}
	
	
	public static function getLocalBackupList($rawFiles = false)
	{
		if($cache = CacheFunc::get('getLocalBackupList' . intval($rawFiles)))
			return $cache;
		$files = array();
		$dir = new \DirectoryIterator($_SERVER['DOCUMENT_ROOT'] . '/bitrix/backup/');
		foreach ($dir as $file) {
			if (!$file->isDot()) {
				$files[] = array(
					'name' => $file->getFilename(),
					'size' => $file->getSize(),
					'storage' => 'local'
				);
			}
		}
		$result = array();
		if($rawFiles)
			$result = $files;
		else
			$result = static::sliceFormalBackup($files);
			
		CacheFunc::set('getLocalBackupList' . intval($rawFiles), $result);
		return $result;
	}
	
	
	public static function getRemoteBackupList($storageObj, $rawFiles = false)
	{
		$files = $storageObj->getAllFiles();
		if($rawFiles)
			return $files;
		else
			return static::sliceFormalBackup($files);
	}
	
	
	public static function chooseBackupFiles($backupName, $allFiles)
	{
		$backupFiles = array();
		$backupName .= '.tar.gz';
		foreach($allFiles as $file)
		{
			if(preg_match('/^' . $backupName . '(\.[\d]+)?$/', $file['name']))
				$backupFiles[] = $file['name'];
		}
		return $backupFiles;
	}
	
}

?>
