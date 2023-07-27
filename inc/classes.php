<?php

use Database\Database;

//$model_path  = ROOT_PATH.'models/';
//$model_files = array_diff(scandir($model_path), ['.', '..', '_notes']);
//
//foreach ($model_files as $model_file) {
//	if (!is_dir(ROOT_PATH . $model_path . $model_file)) {
//		$class_ext = substr($model_file, -4);
//		if ($class_ext == '.php') {
//			$class_name = str_replace(".php", '', $model_file);
//			if ($class_name != 'BaseModel') {
//				$$class_name = new $class_name();
//			}
//		}
//	}
//}
//$seo_conn     = new mysqli(SEO_HOST, SEO_DB_USER, SEO_DB_PASS, SEO_DB_NAME, SEO_DB_PORT);
//$comp_conn    = new mysqli(COMP_HOST, COMP_DB_USER, COMP_DB_PASS, COMP_DB_NAME, COMP_DB_PORT);
//$SeoDatabase  = new Database($seo_conn);
//$CompDatabase = new Database($comp_conn);
//
//$CompanyModel           = new Company($CompDatabase);
//$DomainModel            = new Domain($CompDatabase);
//$WebsettingModel        = new Websetting($CompDatabase);
//$SalesModel             = new CusSales($CompDatabase);
//$CustomerModel          = new CusCustomer($CompDatabase);
//$UserModel              = new User($SeoDatabase);
//$PermissionsModel       = new Permissions($SeoDatabase);
//$ProjectModel           = new Project($SeoDatabase);
//$GoogleAccountModel     = new GoogleAccount($SeoDatabase);
//$SearchConsoleListModel = new SearchConsoleList($SeoDatabase);
//$GaListModel            = new GaList($SeoDatabase);
//$UaListModel            = new UaList($SeoDatabase);
//$SeoDataModel           = new SeoData($SeoDatabase);
//$IndexingModel          = new Indexing($SeoDatabase);
