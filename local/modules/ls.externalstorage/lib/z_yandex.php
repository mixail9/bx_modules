<?php
namespace Ls\Externalstorage;

use \Ls\Externalstorage\CacheFunc;

class YandexStorage implements \Ls\Externalstorage\StorageInterface
{
	protected static $token = 'AQAAAAAGfVIOAARH19DomEchAkydqtZ1Q8OBeAs'; //'AQAAAAAGfVIOAARH12Ssh9nxG0XomTK5i-fLPs0';
	protected static $appId = '3c1d388d117f4ef6ad116f3dd2acd92f';
	protected static $baseUrl = 'https://cloud-api.yandex.net/v1/disk/';
	protected static $authUrl = 'https://oauth.yandex.ru/authorize?response_type=token&diisplay=popup&force_confirm=no&client_id=';
	
	protected static $localDir = '';

	
	protected static $remoteDir = '';


	public static function getId()
	{
		return 'yandex';
	}
	
	public static function getName()
	{
		return 'Яндекс.Диск';
	}
	
	public static function getOptions()
	{
		$options = array(
			'token' => array('value' => static::$token, 'name' => 'token', 'type' => 'text', 'title' => 'token'),
			'appId' => array('value' => static::$appId, 'name' => 'appId', 'type' => 'text', 'title' => 'appId')
		);
		return $options;
	}
	
	public static function getOptionsHtml()
	{
		$data = $this->sendRequest(static::$baseUrl . 'resources?path=' . urlencode('app:/') . '&limit=500');
		$files = array();
		foreach($data['_embedded']['files'] as $file)
		{
			$files[] = array(
				'name' => $file['name'],
				'storage' => $this->getName(),
				'size' => $file['size']
			);
		}
		return $data;
	}
	
	protected function saveToken()
	{
	
	}

	protected function sendRequest($url, $method = 'GET', $file = false)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		$header = array();
		if(static::$token)
			$header[] = 'Authorization: OAuth ' . static::$token;
		if($file && file_exists($file))
		{
			$header[] = 'Expect: ';
			$f = fopen($file, 'r');
			curl_setopt($ch, CURLOPT_UPLOAD, 1);
			curl_setopt($ch, CURLOPT_INFILE, $f);
			curl_setopt($ch, CURLOPT_INFILESIZE, filesize($file));
		}

		if($header)
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		$response = curl_exec($ch);
		curl_close($ch);
		return json_decode(trim($response), true);
	}


	public static function getFileName($path)
	{
		if(strpos($path, '/') !== false)
			return substr($path, strrpos($path, '/') + 1);
		else
			return $path;
	}


	public function showAuth()
	{
		print '<a href="' . static::$authUrl . '">Authorize</a>';
	}

	/* init params instead constructor */
	public function authorize($params = null)
	{ 
		static::$authUrl .= static::$appId;
		static::$localDir = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/backup/';
		if(!static::$token)
			$this->showAuth();
	}


	public function getInfo()
	{
		$data = $this->sendRequest(static::$baseUrl);
		return $data;
	}
	
	
	public function getAllFiles()
	{
		if($cache = CacheFunc::get('getAllFiles'))
			return $cache;
		$data = $this->sendRequest(static::$baseUrl . 'resources?path=' . urlencode('app:/') . '&limit=500');
		$files = array();
		foreach($data['_embedded']['items'] as $file)
		{
			$files[] = array(
				'name' => $file['name'],
				'storage' => $this->getName(),
				'storage_id' => $this->getId(),
				'size' => $file['size']
			);
		}
		CacheFunc::set('getAllFiles', $files);
		return $files;
	}

	
	
	
	public function upload($fromFile = array(), $toFile = array(), $saveNames = false, $rewrite = false)
	{
		if(!is_array($fromFile))
			$fromFile = array($fromFile);
			
		foreach($fromFile as $k => $file)
		{
			if(!file_exists($file) && file_exists(static::$localDir . $file))
				$fromFile[$k] = static::$localDir . $file;
		}

		if($toFile && !is_array($toFile))
			$toFile = array($toFile);
		elseif(!$toFile)
			$toFile = array();

		if(count($toFile) < count($fromFile))
		{
			foreach($fromFile as $k => $v)
			{
				if($saveNames)
					$toFile[$k] = $this->getFileName($v);
				else
					$toFile[$k] = md5(rand(100000, 999999)) . $this->getFileName($v);
			}
		}
		$uploadResult = array();
		foreach($fromFile as $k => $v)
		{
			$data = $this->sendRequest(static::$baseUrl . 'resources/upload?path=' . urlencode('app:/' . $this->getFileName($v)) . '&overwrite=' . (($rewrite) ? 'true' : 'false'));
			if($data['href'] && $data['method'])
				$uploadResult[] = $this->sendRequest($data['href'], $data['method'], $v);
		}
		return $uploadResult;
	}
	
	
	
	public function download($fromFile = array(), $toFile = array(), $saveNames = false, $rewrite = false)
	{
		if(!is_array($fromFile))
			$fromFile = array($fromFile);
			

		if($toFile && !is_array($toFile))
			$toFile = array($toFile);
		elseif(!$toFile)
			$toFile = array();

		if(count($toFile) < count($fromFile))
		{
			foreach($fromFile as $k => $v)
			{
				if($saveNames)
					$toFile[$k] = $this->getFileName($v);
				else
					$toFile[$k] = md5(rand(100000, 999999)) . $this->getFileName($v);
			}
		}
		foreach($toFile as $k => $file)
		{
			//if(!file_exists($file) && file_exists(static::$localDir . $file))
				$toFile[$k] = static::$localDir . $file;
		}
		
		$downloadResult = array();
		foreach($fromFile as $fileInd => $file)
		{
			$data = $this->sendRequest(static::$baseUrl . 'resources/download?path=app:/' . $file);
			$inputFile = fopen($data['href'], 'r');
			$outFile = fopen($toFile[$fileInd], 'w');
			while($block = fread($inputFile, 8192))
			{
				fwrite($outFile, $block);
			}
			fclose($inputFile);
			fclose($outFile);
			print_r($toFile[$fileInd]);
			$downloadResult[] = $data;
			
		}
		return $downloadResult;
	}
	
	
	public function getStructure()
	{
		$data = $this->sendRequest(static::$baseUrl . 'resources?path=app:/');
		print_r($data);
	}
	public function delete($files = array())
	{
		if(!is_array($files))
			$files = array($files);
		$result = array();
		foreach($files as $file)
			$result[] = $this->sendRequest(static::$baseUrl . 'resources/?path=' . urlencode('app:/' . $file) . '&permanently=true', 'DELETE');
			
		return $result;
	}
	public function fileExists($file = array())
	{
		if(!is_array($file))
			$file = array($file);
		$data = array();
		foreach($file as $oneFile)
			$data[] = $this->sendRequest(static::$baseUrl . 'resources?path=app:/' . static::$dir . '/' . $oneFile, 'GET');
		return $data;
	}
}

?>
