<?php
/*
* @version 0.1 (wizard)
*/
if ($this->owner->name=='panel') {
	$out['CONTROLPANEL']=1;
}
global $del_prop;
if($del_prop) {
	$properties=SQLSelectOne("SELECT * FROM greensmart_prop WHERE ID='".(int)$del_prop."'");
	if ($properties['LINKED_PROPERTY']) removeLinkedProperty($properties['LINKED_OBJECT'], $properties['LINKED_PROPERTY'], $this->name);
        SQLExec("DELETE FROM greensmart_prop WHERE ID='".(int)$del_prop."'");
}
if($this->mode=='update') {
	$ok=1;
	$title_new=gr('title_new');
	if($title_new) {
		if(!preg_match('/^[A-Za-z]{1,16}$/', $title_new)) {
			$out['ERR_NTITLE']=1;
			$ok=0;
		}
	}
	$properties=SQLSelect("SELECT * FROM greensmart_prop WHERE DEV_ID='".(int)$id."' ORDER BY ID");
	$total=count($properties);
	for($i=0;$i<$total;$i++) {
		$old_linked_object=$properties[$i]['LINKED_OBJECT'];
		$old_linked_property=$properties[$i]['LINKED_PROPERTY'];
		global ${'linked_object'.$properties[$i]['ID']};
		$properties[$i]['LINKED_OBJECT']=trim(${'linked_object'.$properties[$i]['ID']});
		global ${'linked_property'.$properties[$i]['ID']};
		$properties[$i]['LINKED_PROPERTY']=trim(${'linked_property'.$properties[$i]['ID']});
		if ($properties[$i]['LINKED_OBJECT'] != '' && ($properties[$i]['LINKED_PROPERTY'] == '' && $properties[$i]['LINKED_METHOD'] == '')) {
			$properties[$i]['LINKED_OBJECT'] = '';
		}
		SQLUpdate('greensmart_prop', $properties[$i]);
		if ($old_linked_object && $old_linked_object!=$properties[$i]['LINKED_OBJECT']
			|| $old_linked_property && $old_linked_property!=$properties[$i]['LINKED_PROPERTY']) {
				removeLinkedProperty($old_linked_object, $old_linked_property, $this->name);
		}
		if ($properties[$i]['LINKED_OBJECT'] && $properties[$i]['LINKED_PROPERTY']) {
			addLinkedProperty($properties[$i]['LINKED_OBJECT'], $properties[$i]['LINKED_PROPERTY'], $this->name);
		}
	}
	if($this->config['CYCLE_PID']) {
		$cycle=shell_exec('ps ho cmd ' . $this->config['CYCLE_PID']);
		if(preg_match('/\.\/scripts\/cycle_greensmart\.php/', $cycle)) {
			posix_kill($this->config['CYCLE_PID'], 1);
		}
	}
	if($ok) {
		if($title_new) {
			$record['DEV_ID']=$id;
			$record['NAME']=$title_new;
			$record['ID']=SQLInsert('greensmart_prop', $record);
		}
		$out['OK']=1;
	} else {
		$out['ERR']=1;
	}
}
$out['ID']=$id;
$device=SQLSelectOne("SELECT * FROM greensmart_dev WHERE ID='$id'");
if ($device['TITLE']) {
	$out['TITLE']=$device['TITLE'];
}
$rec=SQLSelect("SELECT ID as PROP_ID,DEV_ID as ID,NAME,LINKED_OBJECT,LINKED_PROPERTY FROM greensmart_prop WHERE DEV_ID='$id' ORDER BY PROP_ID");
if ($rec[0]['ID']) {
	$getProps=array();
	$total=count($rec);
	for($i=0;$i<$total;$i++) {
		$getProps[]=$rec[$i]['NAME'];
	}
	$gsDev = new GreensmartLib($device['IP'], $device['SECRETKEY'], $device['MAC']);
	$props=$gsDev->getParams($getProps);
	unset($getProps);
	for($i=0;$i<$total;$i++) {
		$name=$rec[$i]['NAME'];
		$rec[$i]['VALUE']=$props[$name];
	}
	$out['RESULT']=$rec;
}
