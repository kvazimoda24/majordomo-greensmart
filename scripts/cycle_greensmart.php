<?php
chdir(dirname(__FILE__) . '/../');
include_once("./config.php");
include_once("./lib/loader.php");
include_once("./lib/threads.php");
set_time_limit(0);
// connecting to database
$db = new mysql(DB_HOST, '', DB_USER, DB_PASSWORD, DB_NAME);
include_once("./load_settings.php");
include_once(DIR_MODULES . "control_modules/control_modules.class.php");
$ctl = new control_modules();
include_once(DIR_MODULES . 'greensmart/greensmart.class.php');
$greensmart_module = new GreenSmart();
$greensmart_module->getConfig();
//echo date("H:i:s") . " running " . basename(__FILE__) . PHP_EOL;
$latest_poll=0;
$latest_check=0;
$scriptEveryCheck=30;
$pollEvery=$greensmart_module->config['UPDATE_INTERVAL'];
$cycleVarName='ThisComputer.'.str_replace('.php', '', basename(__FILE__)).'Run';

pcntl_signal(SIGHUP, [$greensmart_module, 'needReloadLinkedObj']);
pcntl_async_signals('enable');
$greensmart_module->config['CYCLE_PID']=posix_getpid();
$greensmart_module->saveConfig();

while (1) {
	if($greensmart_module->ndRldLnkdObj) {
		$greensmart_module->getConfig();
		$greensmart_module->reloadLinkedObj();
		$greensmart_module->ndRldLnkdObj=false;
		$pollEvery=$greensmart_module->config['UPDATE_INTERVAL'];
		$latest_poll=0;
		$latest_check=0;
	}
	if (time()-$latest_check > $scriptEveryCheck) {
		$latest_check=time();
		setGlobal($cycleVarName, time(), 1);
	}
	if (time()-$latest_poll > $pollEvery) {
		$latest_poll=time();
		$greensmart_module->processCycle();
	}
	if (file_exists('./reboot') || IsSet($_GET['onetime'])) {
		$db->Disconnect();
		exit;
	}
	sleep(1);
}
DebMes("Unexpected close of cycle: " . basename(__FILE__));
