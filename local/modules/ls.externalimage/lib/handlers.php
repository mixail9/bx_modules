<?
namespace Ls\Externalimage;

class Handlers
{
	public static function setNewFileName(&$fields)
	{
		$fileNameField = \Bitrix\Main\Config\Option::get('iblock', 'name_img_field', 'CODE');
	
		//  file will save with own original name
		if($fileNameField == 'original')
			return true;
			
		//  default value, other fields may be empty
		$newFileName = \CUtil::translit($fields['NAME'], LANG);
		$needTranslate = false;
		if(strpos($fileNameField, 'translate') !== false)
		{
			$fileNameField = str_replace('translate', '', $fileNameField);
			$needTranslate = true;
		}
		if($fields[$fileNameField])
		{
			$newFileName = $fields[$fileNameField];
			if($needTranslate)
				$newFileName = \CUtil::translit($newFileName, LANG);
		}
		
		//  add new filename into array files info with prefix and extension
		if($fields['PREVIEW_PICTURE'] && $fields['PREVIEW_PICTURE']['tmp_name'])
			$fields['PREVIEW_PICTURE']['new_name'] = 's_' . $newFileName . substr($fields['PREVIEW_PICTURE']['tmp_name'], strrpos($fields['PREVIEW_PICTURE']['tmp_name'], '.'));
		if($fields['DETAIL_PICTURE'] && $fields['DETAIL_PICTURE']['tmp_name'])
			$fields['DETAIL_PICTURE']['new_name'] = 'b_' . $newFileName . substr($fields['DETAIL_PICTURE']['tmp_name'], strrpos($fields['DETAIL_PICTURE']['tmp_name'], '.'));

		return true;
	}
	
	
	public static function saveFileCustom(&$file)
	{
		//print_r($file);
		//die('');
		if(strpos($file['type'], 'image') === false)
			return false;
	
		$subdir = \Bitrix\Main\Config\Option::get('iblock', 'img_subdir', 'iblock_img') . '/';
		$handler = \Bitrix\Main\Config\Option::get('iblock', 'img_save_handler', '');

		$name = $file['ORIGINAL_NAME'];
		if($file['new_name'])
			$name = $file['new_name'];
		$size = \CFile::GetImageSize($file['tmp_name'], true);	
		
		$r = null;
		//if($handler)
		//	$r = $handler($file);
		//else
			$r = rename($file['tmp_name'], $_SERVER['DOCUMENT_ROOT'] . '/upload/' . $subdir . $name);
		//if(!$r)
		//	return false;
		
		$file['WIDTH'] = $size[0];
		$file['HEIGHT'] = $size[1];
		$file['FILE_NAME'] = $name;
		$file['SUBDIR'] = $subdir;
		return true;
	}
}
?>
