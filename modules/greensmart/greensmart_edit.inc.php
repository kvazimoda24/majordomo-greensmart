<?php
/*
* @version 0.1 (wizard)
*/
if ($this->owner->name=='panel') {
	$out['CONTROLPANEL']=1;
}
if ($this->tab=='') {
	require(dirname(__FILE__).'/greensmart_edit_dev.inc.php');
}
if ($this->tab=='data') {
	require(dirname(__FILE__).'/greensmart_edit_prop.inc.php');
}
if (is_array($rec)) {
	foreach($rec as $k=>$v) {
		if (!is_array($v)) {
			$rec[$k]=htmlspecialchars($v);
		}
	}
}
outHash($rec, $out);
