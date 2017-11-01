<?php
namespace Ls\Externalstorage;

interface StorageInterface
{
	public static function getId();
	public static function getName();
	public static function getOptions();
	public static function getOptionsHtml();
	public function authorize($params = null);
	public function getInfo();
	public function getAllFiles();
	public function upload($fromFile = array(), $toFile = array(), $saveNames = false, $rewrite = false);
	public function download($fromFile = array(), $toFile = array(), $saveNames = false, $rewrite = false);
	public function getStructure();	
	public function delete($file = array());
	public function fileExists($file = array());
}
?>
