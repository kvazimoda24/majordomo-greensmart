<?php
class GreensmartLib {
	private $gs_ip;
	private $gs_port = 7000;
	private $gs_id;
	private $gs_key;

	public function __construct($ip, $key, $id = '') {
		$this->gs_ip = $ip;
		$this->gs_key = $key;
		$this->gs_id = $id;
	}

	private function gsSendData($data) {
		$s = socket_create (AF_INET, SOCK_DGRAM, SOL_UDP);
		socket_set_option ($s, SOL_SOCKET, SO_SNDTIMEO, array("sec"=>5, "usec"=>0));
		socket_set_option ($s, SOL_SOCKET, SO_RCVTIMEO, array("sec"=>5, "usec"=>0));
		socket_set_option ($s, SOL_SOCKET, SO_REUSEADDR, 1);
		$dataLen = strlen($data);
		socket_sendto ($s, $data, $dataLen, 0, $this->gs_ip, $this->gs_port);
		socket_recv ($s, $recv, 1024, MSG_WAITALL);
		socket_close ($s);
		return $recv;
	}

	private function gsDecrypt($pack_encoded) {
		$pack_decrypted = openssl_decrypt ($pack_encoded, 'AES-128-ECB', $this->gs_key, 0);
		return $pack_decrypted;
	}

	private function gsEncrypt($pack) {
		$pack_encrypted = openssl_encrypt($pack, 'AES-128-ECB', $this->gs_key, 0);
		return $pack_encrypted;
	}

	private function bindDevice($search_result) {
		$this->gs_ip = $search_result['IP'];
		$this->gs_id = $search_result['MAC'];

		$pack = sprintf('{"mac":"%s","t":"bind","uid":0}', $this->gs_id);
		$pack_encrypted = $this->gsEncrypt($pack);

		$request = sprintf('{"cid":"app","i":1,"t":"pack","uid":0,"tcid":"%s","pack":"%s"}', $this->gs_id, $pack_encrypted);
		$result = $this->gsSendData($request);

		$response = json_decode(substr($result, 0, strpos($result, '}') + 1), true);
		if ($response['t'] == 'pack') {
			$pack = $response['pack'];

			$bind_resp = json_decode($this->gsDecrypt($pack), true);
			if ($bind_resp['t'] == 'bindok') {
				$secretkey = $bind_resp['key'];
				return $secretkey;
			}
		}
	}

	public function searchDevices() {
		$broadcast = $this->gs_ip;
		$search_key = $this->gs_key;

		$data = '{"t":"scan"}';
		$dataLen = 12;

		$s = socket_create (AF_INET, SOCK_DGRAM, SOL_UDP);
		socket_set_option ($s, SOL_SOCKET, SO_SNDTIMEO, array("sec"=>5, "usec"=>0));
		socket_set_option ($s, SOL_SOCKET, SO_RCVTIMEO, array("sec"=>5, "usec"=>0));
		socket_set_option ($s, SOL_SOCKET, SO_REUSEADDR, 1);
		socket_set_option ($s, SOL_SOCKET, SO_BROADCAST, 1);
		socket_sendto ($s, $data, $dataLen, 0, $broadcast, $this->gs_port);

		$result = [];

		while (true) {
			$recv = socket_recvfrom ($s, $data, 1024, 0, $address, $port);
			if ($recv === false) {
				socket_close ($s);
				break;
			}
			if ($recv == 0) continue;
			$resp = json_decode (substr($data, 0, strpos($data, '}') + 1), true);
			$pack = json_decode ($this->gsDecrypt($resp['pack']), true);
			if ( isset($pack['name']) ) $name = $pack['name'];
				else $name = '<unknown>';
			$result[] = array(
				'IP' => $address,
				'PORT' => $port,
				'MAC' => $pack['cid'],
				'NAME' => $name
			);
		}

		if (count($result) > 0) {
			foreach ($result as $r) {
				$devices[] = array(
					'MAC' => $r['MAC'],
					'NAME' => $r['NAME'],
					'IP' => $r['IP'],
					'SECRETKEY'=> $this->bindDevice($r)
				);
			}
			return $devices;
		}
	}

	public function getParams($params) {
		$parameters = array();
		$params_chunk = array_chunk($params, 16);
		foreach ($params_chunk as $p) {
			$pack_array = array(
				'cols' => $p,
				'mac' => $this->gs_id,
				't' => 'status'
			);
			$pack = json_encode($pack_array);
			$pack_encrypted = $this->gsEncrypt($pack);

			$request = sprintf('{"cid":"app","i":0,"pack":"%s","t":"pack","tcid":"%s","uid":0}', $pack_encrypted, $this->gs_id);

			$result = $this->gsSendData($request);
			$response = json_decode(substr($result, 0, strpos($result, '}') + 1), true);
			if ($response['t'] == 'pack') {
				$pack = $response['pack'];

				$pack_json = json_decode($this->gsDecrypt($pack), true);
				$getting_parameters = array_combine($pack_json['cols'], $pack_json['dat']);
				$parameters = array_merge($parameters, $getting_parameters);
			}
		}
		if ( isset($parameters['TemSen']) ) $parameters['TemSen'] = $parameters['TemSen'] - 40;
		return $parameters;
	}

	public function getParam($param) {
		$result = $this->getParams(array($param));
		return $result[$param];
	}

	public function setParams($params) {
		$status = false;
		foreach ($params as $key => &$value) {
			if(preg_match('/^\d{1,3}$/', $value) && !preg_match('/^[a-z]+$/', $key)) $value = (int)$value;
		}
		unset($value);
		$params_chunk = array_chunk($params, 16, true);
		foreach ($params_chunk as $p) {
			$pack_array = array(
				'opt' => array_keys($p),
				'p' => array_values($p),
				't' => 'cmd'
			);
			$pack = json_encode($pack_array);
			$pack_encrypted = $this->gsEncrypt($pack);
			$request = sprintf('{"cid":"app","i":0,"pack":"%s","t":"pack","tcid":"%s","uid":0}', $pack_encrypted, $this->gs_id);

			$result = $this->gsSendData($request);

			$response = json_decode(substr($result, 0, strpos($result, '}') + 1), true);
			if ($response['t'] == 'pack') {
				$pack = $response['pack'];

				$pack_json = json_decode($this->gsDecrypt($pack), true);

				if ($pack_json['r'] != 200) {
					$status = false;
					break;
				}
				$status = true;
			}
		}
		return $status;
	}

	public function getAllParams() {
		$params = array(
			'Pow', 'Mod',
			'SetTem', 'WdSpd',
			'Air', 'Blo',
			'Health', 'SwhSlp',
			'Lig', 'SwingLfRig',
			'SwUpDn', 'Quiet',
			'Tur', 'StHt',
			'HeatCoolType', 'TemRec',
			'SvSt', 'TemSen',
			'time', 'name',
			'mac'
		);
			# 'TemUm',
		$result = $this->getParams($params);
		return $result;
	}
}

?>
