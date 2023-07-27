<?php
require_once __DIR__ . '/models/Database/Database.php';
$_EXCLUDES_USES = ['models\BaseModel', 'controllers\BaseController'];
spl_autoload_register(function($class_name) {
	global $_EXCLUDES_USES;
	// Convert the namespace and class name to a file path
	$file_path = ROOT_PATH . str_replace('\\', '/', $class_name) . '.php';

	// Check if the file exists and load it
	if (file_exists($file_path)) {
		require $file_path;
	}
});