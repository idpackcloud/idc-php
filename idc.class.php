<?php

//============================================================+
// File name   : idc.class.php
// Begin       : 2022-12-07
// Last Update : 2023-03-10
// Author      : Martin Bourdages - IDpack in the Cloud - www.idpack.cloud - support@idpack.cloud
// License     : MIT
// -------------------------------------------------------------------
// Copyright (C) 2023 Martin Bourdages - IDpack in the Cloud
//
// This file is part of IDpack in the Cloud library.
//
// See LICENSE file for more information.
// -------------------------------------------------------------------
//
// Description :
//    A PHP library for communicating with the IDpack in the Cloud REST API and accessing the Badge Producer from your system.
//
//============================================================+

/**
 * @class IDpack
*/

class IDpack {

	const VERSION = '1.3.072';

	// Protected properties

	// Valid output format for: get_record or get_all_records
	protected $valid_output_format1 = array('json', 'xml'); 
	// Valid output format for: get_photo_id or get_badge_preview 
	protected $valid_output_format2 = array('json', 'xml', 'base64'); 
	// Valid photo id format
	protected $valid_photo_id_format = array('jpeg', 'png', 'webp');
	// Valid badge preview format
	protected $valid_badge_preview_format = array('jpeg', 'png', 'webp', 'pdf');
	// Valid api authorization
	protected $valid_api_authorization = array('basic','');

	protected $username = '';
	protected $password = '';
	
	protected $server_ip = '';
	protected $server_http_return_code = '';

	protected $insert_id = '';

	protected $payload_array = array();
	protected $payload_json = '';

	protected $api_action = '';
	protected $api_output_format = 'json';
	protected $api_authorization = 'basic';
	protected $api_base_url = 'https://api.idpack.cloud';
	protected $api_resource = '/producer/';

	public function __construct($username = '', $password = '', $user_secret_key = '', $project_secret_key = '') {
		$this->username = $username;
		$this->password = $password;
		$this->payload_array = array('user_secret_key' => $user_secret_key, 'project_secret_key' => $project_secret_key);
	}

	protected function getErrorResponse($message=null, $code=null) : string 
	{
		$error_response = array('status' => 'error');
		if (!empty($message)) {
			$error_response += ['message' => 'idc-php: '.$message];
		}
		if (!empty($code)) {
			$error_response += ['code' => $code];
			$this->server_http_return_code = $code;
		}
		if (!empty($this->api_action)) {
			$error_response += ['api_action' => $this->api_action];
		}
		$error_response += ['api' => [
			'api_authorization' => $this->api_authorization,
			'idc_php_version' => self::VERSION]
		];
		return json_encode($error_response);
	}

	protected function isBool($var) : bool
	{
		if (!is_string($var)) return (bool) $var;
		switch (strtolower(trim($var))) {
			case '1':
			case 'true':
			case 'on':
			case 'yes':
			case 'y':
				return true;
			default:
				return false;
		}
	}

	protected function isPrimaryKey($api_primary_key=array()) : string
	{
		if (empty($api_primary_key)) {
			return $this->getErrorResponse('api_primary_key can\'t be empty.', 720);
		}
		if (count($api_primary_key) > 1) {
			return $this->getErrorResponse('api_primary_key must have only one argument', 720);
		}
		if (empty(array_keys($api_primary_key)[0])) {
			return $this->getErrorResponse('api_primary_key must have a field', 720);
		}
		if (empty(array_values($api_primary_key)[0])) {
			return $this->getErrorResponse('api_primary_key must have a value', 720);
		}
		return true;
	}

	protected function getResponse() : string 
	{
		if (empty($this->payload_array)) {
			return $this->getErrorResponse('payload can\'t be empty!', 600);
		}
		if (($this->api_action == 'get_photo_id') or ($this->api_action == 'get_badge_preview')) {
			if (!in_array($this->api_output_format, $this->valid_output_format2)) {
				return $this->getErrorResponse('invalid api_output_format: '.$this->api_output_format, 610);
			}
		} else {
			if (!in_array($this->api_output_format, $this->valid_output_format1)) {
				return $this->getErrorResponse('invalid api_output_format: '.$this->api_output_format, 610);
			}
		}
		$this->payload_array['api'] += [
			'api_action' => $this->api_action,
			'api_output_format' => $this->api_output_format,
			'api_authorization' => $this->api_authorization,
			'idc_php_version' => self::VERSION];
		$this->payload_json = json_encode($this->payload_array);
		$headers = [
			'Accept-Language:en-US',
			'Accept:application/json',
			'Content-Type:application/json',
			'Content-Length:'.strlen($this->payload_json)
		];
		// Init cURL
		try {
			$ch = curl_init();
			if ($ch === false) {
				return $this->getErrorResponse('failed to initialize cURL', 620);
			}
			// Setup cURL
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return content
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Don't verify the certificate's name against host
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Don't verify the peer's ssl certificate
			curl_setopt($ch, CURLOPT_HEADER, false); // Don't return headers
			curl_setopt($ch, CURLOPT_POST, true); // Post JSON to URL
			curl_setopt($ch, CURLOPT_POSTFIELDS, $this->payload_json); // Payload to post
			curl_setopt($ch, CURLOPT_TIMEOUT_MS, 15000); // The maximum number of milliseconds (15 sec.) to allow cURL functions to execute
			curl_setopt($ch, CURLOPT_URL, $this->api_base_url.$this->api_resource); // IDC URL
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); // Array of http header fields
			if ($this->api_authorization == 'basic') {
				curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC); // The http authentication method(s) to use.
				curl_setopt($ch, CURLOPT_USERPWD, $this->username.':'.$this->password); // A username and password to use for the connection.
			}
			$response = curl_exec($ch); // Execute cURL
			if ($response === false) {
				return $this->getErrorResponse('cURL error: '.curl_error($ch).' '. curl_errno($ch), 630);
			} else {
				$this->server_ip = curl_getinfo($ch, CURLINFO_PRIMARY_IP);
				$this->server_http_return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				if ($this->server_http_return_code == 401) {
					return $this->getErrorResponse('HTTP/1.0 401 Unauthorized.', $this->server_http_return_code);
				} elseif ($this->server_http_return_code == 500) {
					return $this->getErrorResponse('HTTP/1.0 500 IDC API - Server Error.', $this->server_http_return_code);
				} elseif ($this->server_http_return_code <> 200) {
					return $this->getErrorResponse('Unexpected '.$this->server_http_return_code.' HTTP code.', $this->server_http_return_code);
				} else  {
					if (empty($response)) {
						return $this->getErrorResponse('Response is empty!', 640);
					} else {
						if ($this->api_action == 'insert_record') {
							$response_array = json_decode($response, 1);
							if (isset($response_array['data']['idc_id_number'])) {
								$this->insert_id = $response_array['data']['idc_id_number'];
							}
						}
						return $response;
					}
				}
			}
		} catch (Exception $e) {
			return $this->getErrorResponse('caught exception: '.$e->getMessage(), 650);
		}		
	}

	//------------------------------------------------------------
	// Methods
	//------------------------------------------------------------

	public function getServerIP() : string { return $this->server_ip; }
	public function getServerHTTPReturnCode() : string { return $this->server_http_return_code; }
	public function getInsertID() : string { return $this->insert_id; }
	public function getPayloadJSON() : string { return $this->payload_json; }
	public function getPayloadArray() : string { return $this->payload_array; }

	public function setUsername($username) { $this->username = $username; }
	public function setPassword($password) { $this->password = $password; }
	public function setUserSecretKey($user_secret_key) { $this->payload_array['user_secret_key'] = $user_secret_key; }
	public function setProjectSecretKey($project_secret_key) { $this->payload_array['project_secret_key'] = $project_secret_key; }

	public function setApiAuthorization($api_authorization) : bool 
	{ 
		$api_authorization = strtolower($api_authorization);
		if (!in_array($api_authorization, $this->valid_api_authorization)) {
			$this->api_authorization = $api_authorization;
			return true;
		} else {
			return false;
		}
	}

	public function setApiOutputFormat($api_output_format) : bool  
	{ 
		$api_output_format = strtolower($api_output_format);
		if (!in_array($api_output_format, $this->valid_output_format2)) {
			$this->api_output_format = $api_output_format;
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @param array $api_primary_key An array for the Primary Key
	 * @param null|bool $api_photo_id Include the Photo ID in the data structure
	 * @param null|string $api_photo_id_format Photo ID image format
	 * @param null|bool $api_badge_preview Include the Badge Preview in the data structure
	 * @param null|string $api_badge_preview_format Badge Preview image format
	 * @param null|int $api_badge_preview_number Select Duplex (0), Front (1), or Back (2) for the Badge Preview
	 * @return string Return API response
	*/
	public function get_record($api_primary_key=array(), $api_photo_id=0, $api_photo_id_format=null, $api_badge_preview=0, $api_badge_preview_format=null, $api_badge_preview_number=0) : string
	{
		$this->api_action = strtolower(__FUNCTION__);
		$response = $this->isPrimaryKey($api_primary_key);
		if($response <> 1) {
			return $response;
		}
		$this->payload_array += ['api' => [
			'api_primary_key' => $api_primary_key]
		];
		if ($this->isBool($api_photo_id)) {
			$api_photo_id_format = strtolower($api_photo_id_format);
			if (!in_array($api_photo_id_format, $this->valid_photo_id_format)) {
				return $this->getErrorResponse('invalid api_photo_id_format: '.$api_photo_id_format, 730);
			} else {
				$this->payload_array['api'] += ['api_photo_id' => 1];
				$this->payload_array['api'] += ['api_photo_id_format' => $api_photo_id_format];
			}
		}
		if ($this->isBool($api_badge_preview)) {
			$api_badge_preview_format = strtolower($api_badge_preview_format);
			if (!in_array($api_badge_preview_format, $this->valid_badge_preview_format)) {
				return $this->getErrorResponse('invalid api_badge_preview_format: '.$api_badge_preview_format, 740);
			} else {
				$this->payload_array['api'] += ['api_badge_preview' => 1];
				$this->payload_array['api'] += ['api_badge_preview_format' => $api_badge_preview_format];
				if (!empty($api_badge_preview_number)) {
					if ($api_badge_preview_number == 1 or $api_badge_preview_number == 2) {
						$this->payload_array['api'] += ['api_badge_preview_number' => $api_badge_preview_number];
					}
				}
			}
		}
		return $this->getResponse();
	}

	/**
	 * @return string Return API response
	*/
	public function get_all_records() : string
	{
		$this->api_action = strtolower(__FUNCTION__);
		$this->payload_array += ['api' => []];
		return $this->getResponse();
	}

	/**
	 * @param array $api_primary_key An array for the Primary Key
	 * @param null|string $api_photo_id_format Photo ID image format
	 * @return string Return API response
	*/
	public function get_photo_id($api_primary_key=array(), $api_photo_id_format=null) : string
	{
		$this->api_action = strtolower(__FUNCTION__);
		$response = $this->isPrimaryKey($api_primary_key);
		if($response <> 1) {
			return $response;
		}
		$this->payload_array += ['api' => [
			'api_primary_key' => $api_primary_key]
		];
		$api_photo_id_format = strtolower($api_photo_id_format);
		if (!in_array($api_photo_id_format, $this->valid_photo_id_format)) {
			return $this->getErrorResponse('invalid api_photo_id_format: '.$api_photo_id_format, 730);
		} else {
			$this->payload_array['api'] += ['api_photo_id_format' => $api_photo_id_format];
		}
		return $this->getResponse();
	}

	/**
	 * @param array $api_primary_key An array for the Primary Key
	 * @param null|string $api_badge_preview_format Badge Preview image format
	 * @param null|int $api_badge_preview_number Select Duplex (0), Front (1), or Back (2) for the Badge Preview
	 * @return string Return API response
	*/
	public function get_badge_preview($api_primary_key=array(), $api_badge_preview_format=null, $api_badge_preview_number=0) : string
	{
		$this->api_action = strtolower(__FUNCTION__);
		$response = $this->isPrimaryKey($api_primary_key);
		if($response <> 1) {
			return $response;
		}
		$this->payload_array += ['api' => [
			'api_primary_key' => $api_primary_key]
		];
		$api_badge_preview_format = strtolower($api_badge_preview_format);
		if (!in_array($api_badge_preview_format, $this->valid_badge_preview_format)) {
			return $this->getErrorResponse('invalid api_badge_preview_format: '.$api_badge_preview_format, 740);
		} else {
			$this->payload_array['api'] += ['api_badge_preview_format' => $api_badge_preview_format];
			if (!empty($api_badge_preview_number)) {
				if ($api_badge_preview_number == 1 or $api_badge_preview_number == 2) {
					$this->payload_array['api'] += ['api_badge_preview_number' => $api_badge_preview_number];
				}
			}
		}
		return $this->getResponse();
	}

	/**
	 * @param array $api_primary_key An array for the Primary Key
	 * @param array $api_data An array for the fields
	 * @return string Return API response
	*/
	public function update_record($api_primary_key=array(), $api_data=array()) : string
	{
		$this->api_action = strtolower(__FUNCTION__);
		$response = $this->isPrimaryKey($api_primary_key);
		if($response <> 1) {
			return $response;
		}
		if (empty($api_data)) {
			return $this->getErrorResponse('api_data can\'t be empty.', 750);
		}
		$this->payload_array += ['api' => [
			'api_primary_key' => $api_primary_key]
		];
		$this->payload_array += ['data' => $api_data];
		return $this->getResponse();
	}

	/**
	 * @param array $api_data An array for the fields
	 * @return string Return API response
	*/
	public function insert_record($api_data=array()) : string
	{
		$this->api_action = strtolower(__FUNCTION__);
		if (empty($api_data)) {
			return $this->getErrorResponse('api_data can\'t be empty.', 750);
		}
		$this->payload_array += ['api' => []];
		$this->payload_array += ['data' => $api_data];
		return $this->getResponse();
	}

	/**
	 * There is no way back, this record and Photo ID will be deleted permanently.
	 * @param array $api_primary_key An array for the Primary Key
	 * @return string Return API response
	*/
	public function delete_record($api_primary_key=array()) : string
	{
		$this->api_action = 'update_record';
		$response = $this->isPrimaryKey($api_primary_key);
		if($response <> 1) {
			return $response;
		}
		$api_data = ['idc_delete' => '1'];
		$this->payload_array += ['api' => [
			'api_primary_key' => $api_primary_key]
		];
		$this->payload_array += ['data' => $api_data];
		return $this->getResponse();
	}

	/**
	 * @param array $api_primary_key An array for the Primary Key
	 * @return string Return API response
	*/
	public function set_record_active($api_primary_key=array()) : string
	{
		$this->api_action = 'update_record';
		$response = $this->isPrimaryKey($api_primary_key);
		if($response <> 1) {
			return $response;
		}
		$api_data = ['idc_active' => '1'];
		$this->payload_array += ['api' => [
			'api_primary_key' => $api_primary_key]
		];
		$this->payload_array += ['data' => $api_data];
		return $this->getResponse();
	}

	/**
	 * @param array $api_primary_key An array for the Primary Key
	 * @return string Return API response
	*/
	public function set_record_not_active($api_primary_key=array()) : string
	{
		$this->api_action = 'update_record';
		$response = $this->isPrimaryKey($api_primary_key);
		if($response <> 1) {
			return $response;
		}
		$api_data = ['idc_active' => '0'];
		$this->payload_array += ['api' => [
			'api_primary_key' => $api_primary_key]
		];
		$this->payload_array += ['data' => $api_data];
		return $this->getResponse();
	}

	/**
	 * @param array $api_primary_key An array for the Primary Key
	 * @return string Return API response
	*/
	public function set_record_trash($api_primary_key=array()) : string
	{
		$this->api_action = 'update_record';
		$response = $this->isPrimaryKey($api_primary_key);
		if($response <> 1) {
			return $response;
		}
		$api_data = ['idc_trash' => '1'];
		$this->payload_array += ['api' => [
			'api_primary_key' => $api_primary_key]
		];
		$this->payload_array += ['data' => $api_data];
		return $this->getResponse();
	}

	/**
	 * @param array $api_primary_key An array for the Primary Key
	 * @return string Return API response
	*/
	public function set_record_not_trash($api_primary_key=array()) : string
	{
		$this->api_action = 'update_record';
		$response = $this->isPrimaryKey($api_primary_key);
		if($response <> 1) {
			return $response;
		}
		$api_data = ['idc_trash' => '0'];
		$this->payload_array += ['api' => [
			'api_primary_key' => $api_primary_key]
		];
		$this->payload_array += ['data' => $api_data];
		return $this->getResponse();
	}

} // END OF IDPACK CLASS

//============================================================+
// END OF FILE
//============================================================+

?>