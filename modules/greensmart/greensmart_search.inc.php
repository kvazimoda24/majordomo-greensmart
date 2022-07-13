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
$sortby_greensmart="ID DESC";
$out['SORTBY']=$sortby_greensmart;
// SEARCH RESULTS
$res=SQLSelect("SELECT * FROM greensmart_dev WHERE $qry ORDER BY ".$sortby_greensmart);
if ($res[0]['ID']) {
	//paging($res, 100, $out); // search result paging
	$total=count($res);
	for($i=0;$i<$total;$i++) {
		// some action for every record if required
	}
	$out['RESULT']=$res;
}
