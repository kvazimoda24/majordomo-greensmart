<?php
/*
* @version 0.1 (wizard)
*/
if ($this->owner->name=='panel') {
	$out['CONTROLPANEL']=1;
}
$table_name='greensmart_dev';
$rec=SQLSelectOne("SELECT * FROM $table_name WHERE ID='$id'");
if ($this->mode=='new') {
	$rec['TITLE']=gr('name');
	$rec['IP']=gr('ip');
	$rec['MAC']=gr('mac');
	$rec['SECRETKEY']=gr('secretkey');
}
elseif ($this->mode=='update') {
	$ok=1;
	//updating '<%LANG_TITLE%>' (varchar, required)
	$rec['TITLE']=gr('title');
	if (!preg_match('/^[\w\-]{1,80}$/', $rec['TITLE'])) {
		$out['ERR_TITLE']=1;
		$ok=0;
	}
	//updating 'ip' (varchar)
	$rec['IP']=gr('ip');
	if (!filter_var($rec['IP'], FILTER_VALIDATE_IP, array('flags' => FILTER_FLAG_IPV4))) {
		$out['ERR_IP']=1;
		$ok=0;
	}
	//updating 'mac' (varchar)
	$rec['MAC']=gr('mac');
	if(!preg_match('/^[0-9a-f]{12}$/', $rec['MAC'])) {
		$out['ERR_MAC']=1;
		$ok=0;
	}
	//updating 'key' (varchar)
	$rec['SECRETKEY']=gr('secretkey');
	if(!preg_match('/^\w{16}$/', $rec['SECRETKEY'])) {
		$out['ERR_SECRETKEY']=1;
		$ok=0;
	}
	//UPDATING RECORD
	if ($ok) {
		$gsDev = new GreensmartLib($rec['IP'], $rec['SECRETKEY'], $rec['MAC']);
		$gsDev->setParams(array('name' => $rec['TITLE']));
		if ($rec['ID']) {
			SQLUpdate($table_name, $rec); // update
		} else {
			$new_rec=1;
			$rec['ID']=SQLInsert($table_name, $rec); // adding new record
			$sql = 'INSERT INTO greensmart_prop (DEV_ID, NAME)
				VALUES (%1$d, \'Pow\'), (%1$d, \'Mod\'),
					(%1$d, \'Air\'), (%1$d, \'Blo\'),
					(%1$d, \'Tur\'), (%1$d, \'StHt\'),
					(%1$d, \'Lig\'), (%1$d, \'SwingLfRig\'),
					(%1$d, \'SvSt\'), (%1$d, \'TemSen\'),
					(%1$d, \'time\'), (%1$d, \'name\'),
					(%1$d, \'Health\'), (%1$d, \'SwhSlp\'),
					(%1$d, \'SwUpDn\'), (%1$d, \'Quiet\'),
					(%1$d, \'SetTem\'), (%1$d, \'WdSpd\'),
					(%1$d, \'HeatCoolType\'), (%1$d, \'TemRec\');';
			$sql = sprintf($sql, $rec['ID']);
			SQLExec($sql);
		}
		$out['OK']=1;
	} else {
		$out['ERR']=1;
	}
}
