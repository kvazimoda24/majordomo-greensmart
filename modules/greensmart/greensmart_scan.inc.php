<?php
/*
* @version 0.1 (wizard)
*/
global $session;
if ($this->owner->name=='panel') {
	$out['CONTROLPANEL']=1;
}
$qry="1";
// search filters
// QUERY READY
global $save_qry;
if ($save_qry) {
	$qry=$session->data['greensmart_qry'];
} else {
	$session->data['greensmart_qry']=$qry;
}
if (!$qry) $qry="1";

$sortby_greensmart="id DESC";
$out['SORTBY']=$sortby_greensmart;
// SEARCH RESULTS
$this->getConfig();

if (! isset($this->config['BROADCAST_IP']) or $this->config['BROADCAST_IP'] == '') {
	$out['BRDCST_ERR']=1;
	return;
}
$gsDev = new GreensmartLib($this->config['BROADCAST_IP'], $this->config['GENERIC_KEY']);
$res=$gsDev->searchDevices();

if (count($res)>0) {
	//paging($res, 100, $out); // search result paging
	$total=count($res);
	for($i=0;$i<$total;$i++) {
		// some action for every record if required
	}
	$out['RESULT']=$res;
}
