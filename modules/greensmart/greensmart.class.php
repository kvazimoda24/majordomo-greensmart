<?php
/**
* Gree Smart Air Conditioners
* @package project
* @author Wizard <sergejey@gmail.com>
* @copyright http://majordomo.smartliving.ru/ (c)
* @version 0.1 (wizard, 20:06:36 [Jun 19, 2021])
*/
//
//
include_once(DIR_MODULES . 'greensmart/greensmart.lib.php');
class GreenSmart extends module {
	/**
	* GreenSmart
	*
	* Module class constructor
	*
	* @access private
	*/
	function __construct() {
		$this->name="greensmart";
		$this->title="Green Smart Air Conditioners";
		$this->module_category="<#LANG_SECTION_DEVICES#>";
		$this->checkInstalled();
	}
	/**
	* saveParams
	*
	* Saving module parameters
	*
	* @access public
	*/
	function saveParams($data=1) {
		$p=array();
		if (IsSet($this->id)) {
			$p["id"]=$this->id;
		}
		if (IsSet($this->view_mode)) {
			$p["view_mode"]=$this->view_mode;
		}
		if (IsSet($this->edit_mode)) {
			$p["edit_mode"]=$this->edit_mode;
		}
		if (IsSet($this->tab)) {
			$p["tab"]=$this->tab;
		}
		return parent::saveParams($p);
	}
	/**
	* getParams
	*
	* Getting module parameters from query string
	*
	* @access public
	*/
	function getParams() {
		global $id;
		global $mode;
		global $view_mode;
		global $edit_mode;
		global $tab;
		if (isset($id)) {
			$this->id=$id;
		}
		if (isset($mode)) {
			$this->mode=$mode;
		}
		if (isset($view_mode)) {
			$this->view_mode=$view_mode;
		}
		if (isset($edit_mode)) {
			$this->edit_mode=$edit_mode;
		}
		if (isset($tab)) {
			$this->tab=$tab;
		}
	}
	/**
	* Run
	*
	* Description
	*
	* @access public
	*/
	function run() {
		global $session;
		$out=array();
		if ($this->action=='admin') {
			$this->admin($out);
		} else {
			$this->usual($out);
		}
		if (IsSet($this->owner->action)) {
			$out['PARENT_ACTION']=$this->owner->action;
		}
		if (IsSet($this->owner->name)) {
			$out['PARENT_NAME']=$this->owner->name;
		}
		$out['VIEW_MODE']=$this->view_mode;
		$out['EDIT_MODE']=$this->edit_mode;
		$out['MODE']=$this->mode;
		$out['ACTION']=$this->action;
		$out['TAB']=$this->tab;
		$this->data=$out;
		$p=new parser(DIR_TEMPLATES.$this->name."/".$this->name.".html", $this->data, $this);
		$this->result=$p->result;
	}
	/**
	* BackEnd
	*
	* Module backend
	*
	* @access public
	*/
	function admin(&$out) {
		$this->getConfig();
		$out['UPDATE_INTERVAL']=$this->config['UPDATE_INTERVAL'];
		if (!$out['UPDATE_INTERVAL']) {
			$out['UPDATE_INTERVAL']='10';
		}
		$out['BROADCAST_IP']=$this->config['BROADCAST_IP'];
		$out['GENERIC_KEY']=$this->config['GENERIC_KEY'];
		if (!$out['GENERIC_KEY']) {
			$out['GENERIC_KEY']='a3K8Bx%2r8Y7#xDh';
		}
		if ($this->view_mode=='update_settings') {
			global $update_interval;
			$this->config['UPDATE_INTERVAL']=$update_interval;
			global $broadcast_ip;
			$this->config['BROADCAST_IP']=$broadcast_ip;
			global $generic_key;
			$this->config['GENERIC_KEY']=$generic_key;
			$this->saveConfig();
			if($this->config['CYCLE_PID']) {
				$cycle=shell_exec('ps ho cmd ' . $this->config['CYCLE_PID']);
				if(preg_match('/\.\/scripts\/cycle_greensmart\.php/', $cycle)) {
					posix_kill($this->config['CYCLE_PID'], 1);
				}
			}
			$this->redirect("?");
		}
		if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
			$out['SET_DATASOURCE']=1;
		}
		if ($this->data_source=='greensmart' || $this->data_source=='') {
			if ($this->view_mode=='' || $this->view_mode=='greensmart_search') {
				$this->greensmart_search($out);
			}
			if ($this->view_mode=='greensmart_scan') {
				$this->greensmart_scan($out);
			}
			if ($this->view_mode=='greensmart_edit') {
				$this->greensmart_edit($out, $this->id);
			}
			if ($this->view_mode=='greensmart_delete') {
				$this->greensmart_delete($this->id);
				$this->redirect("?");
			}
		}
	}
	/**
	* FrontEnd
	*
	* Module frontend
	*
	* @access public
	*/
	function usual(&$out) {
		$this->admin($out);
	}
	/**
	* greensmart search
	*
	* @access public
	*/
	function greensmart_search(&$out) {
		require(dirname(__FILE__).'/greensmart_search.inc.php');
	}
	/**
	/**
	* greensmart scan
	*
	* @access public
	*/
	function greensmart_scan(&$out) {
		require(dirname(__FILE__).'/greensmart_scan.inc.php');
	}
	/**
	* greensmart edit/add
	*
	* @access public
	*/
	function greensmart_edit(&$out, $id) {
		require(dirname(__FILE__).'/greensmart_edit.inc.php');
	}
	/**
	* greensmart delete record
	*
	* @access public
	*/
	function greensmart_delete($id) {
		$rec=SQLSelectOne("SELECT * FROM greensmart_dev WHERE ID='$id'");
		// some action for related tables
		$properties=SQLSelect("SELECT * FROM greensmart_prop WHERE LINKED_OBJECT!='' and LINKED_PROPERTY!='' and DEV_ID='".$rec['ID']."'");
		foreach($properties as $prop) {
			removeLinkedProperty($prop['LINKED_OBJECT'], $prop['LINKED_PROPERTY'], $this->name);
		}
		SQLExec("DELETE FROM greensmart_prop WHERE DEV_ID='".$rec['ID']."'");
		SQLExec("DELETE FROM greensmart_dev WHERE ID='".$rec['ID']."'");
	}
	function propertySetHandle($object, $property, $value) {
		$this->getConfig();
		$properties=SQLSelect("SELECT greensmart_dev.IP,greensmart_dev.MAC,greensmart_dev.SECRETKEY,greensmart_prop.NAME
					FROM greensmart_prop INNER JOIN greensmart_dev
					WHERE greensmart_prop.DEV_ID=greensmart_dev.ID
						and greensmart_prop.LINKED_OBJECT='".DBSafe($object)."'
						and greensmart_prop.LINKED_PROPERTY='".DBSafe($property)."'");
		$total=count($properties);
		if (!$total) return;
		foreach($properties as $prop) {
			$gsDev = new GreensmartLib($prop['IP'], $prop['SECRETKEY'], $prop['MAC']);
			$gsDev->setParams(array($prop['NAME'] => $value));
			unset($gsDev);
		}
	}
	private $linkedObj;
	public function reloadLinkedObj() {
//		DebMes(basename(__FILE__) . ': Read linked objects');
		$this->linkedObj=array();

		$tmp = SQLSelect("SELECT greensmart_dev.ID,greensmart_dev.IP,greensmart_dev.MAC,greensmart_dev.SECRETKEY,
					greensmart_prop.NAME,greensmart_prop.LINKED_OBJECT,greensmart_prop.LINKED_PROPERTY
				FROM greensmart_prop INNER JOIN greensmart_dev
				WHERE greensmart_prop.DEV_ID=greensmart_dev.ID
					and greensmart_prop.LINKED_OBJECT!=''
					and greensmart_prop.LINKED_PROPERTY!=''
				ORDER BY greensmart_dev.ID");

		foreach ($tmp as $link) {
			if(!isset($this->linkedObj[$link['ID']])) {
					$this->linkedObj[$link['ID']]=array(
						'gsDev' => new GreensmartLib($link['IP'], $link['SECRETKEY'], $link['MAC'])
					);
			}
			if(!isset($this->linkedObj[$link['ID']]['opt'])) $this->linkedObj[$link['ID']]['opt'] = array();
			$this->linkedObj[$link['ID']]['opt'][$link['NAME']] = $link['LINKED_OBJECT'].'.'.$link['LINKED_PROPERTY'];
		}
		$this->linkedObj['time']=time();

		unset($tmp);
		unset($link);
	}

	public function needReloadLinkedObj() {
		$this->ndRldLnkdObj=true;
	}

	private $oldLinkedParams=array();
	public $ndRldLnkdObj=false;
	public function processCycle() {
		if($this->linkedObj == '') $this->reloadLinkedObj();
		if(time()-$this->linkedObj['time'] > 600) $this->reloadLinkedObj();

//		DebMes(basename(__FILE__) . ': Polling devices...');

		foreach ($this->linkedObj as $id => $dev) {
			if ($id == 'time') continue;

			$linkedParams = $dev['gsDev']->getParams(array_keys($dev['opt']));
			foreach ($linkedParams as $param => $value) {
				if ($value == $this->oldLinkedParams[$id][$param]) continue;
				setGlobal($dev['opt'][$param], $value, array($this->name => '0'));
			}
			$this->oldLinkedParams[$id] = $linkedParams;
			unset($param);
			unset($value);
		}
		unset($id);
		unset($dev);
		foreach ($this->oldLinkedParams as $key => $value) {
			if (!isset($this->linkedObj[$key])) unset($this->oldLinkedParams[$key]);
		}
		unset($key);
		unset($value);
		unset($linkedParams);
	}
	/**
	* Install
	*
	* Module installation routine
	*
	* @access private
	*/
	function install($data='') {
		parent::install();
	}
	/**
	* Uninstall
	*
	* Module uninstall routine
	*
	* @access public
	*/
	function uninstall() {
		$id = SQLSelect('SELECT ID FROM greensmart_dev');
		for($i=0; $i<count($id); $i++){
			$this->greensmart_delete($id[$i]['ID']);
		}
		SQLExec('DROP TABLE IF EXISTS greensmart_dev');
		SQLExec('DROP TABLE IF EXISTS greensmart_prop');
		parent::uninstall();
	}
	/**
	* dbInstall
	*
	* Database installation routine
	*
	* @access private
	*/
	function dbInstall($data) {
		/*
		greensmart_dev -
		greensmart_prop -
		*/
		$data = <<<EOD
		greensmart_dev: ID int(10) unsigned NOT NULL auto_increment
		greensmart_dev: TITLE varchar(80) NOT NULL DEFAULT ''
		greensmart_dev: IP varchar(255) NOT NULL DEFAULT ''
		greensmart_dev: MAC varchar(12) NOT NULL DEFAULT ''
		greensmart_dev: SECRETKEY varchar(16) NOT NULL DEFAULT ''

		greensmart_prop: ID int(10) unsigned NOT NULL auto_increment
		greensmart_prop: DEV_ID int(10) unsigned NOT NULL
		greensmart_prop: NAME varchar(16) NOT NULL
		greensmart_prop: LINKED_OBJECT varchar(255) NOT NULL DEFAULT ''
		greensmart_prop: LINKED_PROPERTY varchar(255) NOT NULL DEFAULT ''
		EOD;
		parent::dbInstall($data);
	}
	// --------------------------------------------------------------------
}
