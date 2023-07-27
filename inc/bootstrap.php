<?php
require_once __DIR__."/constants.php";
require_once ROOT_PATH."/inc/env.php";
require_once ROOT_PATH."/__autoload.php";
require_once ROOT_PATH."/inc/classes.php";
require_once ROOT_PATH."/inc/sessions.php";
require_once ROOT_PATH."/routes/Router.php";

//require_once ROOT_PATH . "utils/Helper.php";
ini_set('max_execution_time', 0);
set_time_limit(0);
ini_set('memory_limit', '64M');