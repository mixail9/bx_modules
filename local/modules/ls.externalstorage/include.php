<?php

$classes = array(
	'\Ls\Externalstorage\Factory' => 'lib/storage_factory.php',
	'\Ls\Externalstorage\StorageInterface' => 'lib/storage_interface.php',
	'\Ls\Externalstorage\BackupHelper' => 'lib/b_backup_helper.php',
	'\Ls\Externalstorage\CacheFunc' => 'lib/cache.php',
	//'\Ls\Externalstorage\StorageYandex' => 'lib/b_storage_yandex.php',
);

\Bitrix\Main\Loader::registerAutoLoadClasses('ls.externalstorage', $classes);

?>
