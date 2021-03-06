<?
class ls_multiprop extends CModule
{

	var $MODULE_ID = "ls.multiprop";
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
		
		$this->MODULE_NAME = "Мультисвойства";
		$this->MODULE_DESCRIPTION = "Множественная установка свойств инфоблока";
	}

	function DoInstall()
	{
		RegisterModule($this->MODULE_ID);
		echo CAdminMessage::ShowNote("Модуль установлен");
	}

	function DoUninstall()
	{
		UnRegisterModule($this->MODULE_ID);
		echo CAdminMessage::ShowNote("Модуль удален");
	}
}
?>
