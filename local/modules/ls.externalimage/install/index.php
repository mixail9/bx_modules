<?
class ls_externalimage extends CModule
{

	var $MODULE_ID = "ls.externalimage";
	var $MODULE_VERSION = '1.0.0';
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	
	function __construct()
	{
		include(__DIR__ . "/version.php");
		
		if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion["VERSION"];
			$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		}
		
		$this->MODULE_NAME = "Хранилище картинок";
		$this->MODULE_DESCRIPTION = "Управление сохранением картинок";
	}

	function DoInstall()
	{
		RegisterModule($this->MODULE_ID);
		\Bitrix\Main\Loader::IncludeModule($this->MODULE_ID);
		Bitrix\Main\EventManager::getInstance()->registerEventHandler("iblock", "OnBeforeIBlockElementAdd", $this->MODULE_ID, '\Ls\Externalimage\Handlers', 'setNewFileName');
		Bitrix\Main\EventManager::getInstance()->registerEventHandler("iblock", "OnBeforeIBlockElementUpdate", $this->MODULE_ID, '\Ls\Externalimage\Handlers', 'setNewFileName');
		Bitrix\Main\EventManager::getInstance()->registerEventHandler("main", "OnFileSave", $this->MODULE_ID, '\Ls\Externalimage\Handlers', 'saveFileCustom');
		echo CAdminMessage::ShowNote("Модуль установлен");
	}

	function DoUninstall()
	{
		\Bitrix\Main\Loader::IncludeModule($this->MODULE_ID);
		Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler("iblock", "OnBeforeIBlockElementAdd", $this->MODULE_ID, '\Ls\Externalimage\Handlers', 'setNewFileName');
		Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler("iblock", "OnBeforeIBlockElementUpdate", $this->MODULE_ID, '\Ls\Externalimage\Handlers', 'setNewFileName');
		Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler("main", "OnFileSave", $this->MODULE_ID, '\Ls\Externalimage\Handlers', 'saveFileCustom');
		UnRegisterModule($this->MODULE_ID);
		echo CAdminMessage::ShowNote("Модуль удален");
	}
}
?>
