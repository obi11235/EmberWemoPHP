<?php
/**
 * Description of Hue_Light
 *
 * @author paul
 */
class WeMo_Switch
{
	
	const ON = 1;
	const OFF = 0;
	const PORT = 49153;
	
	private $ip;
	private $port;
	
	private $id;
	private $name;
	
	private $state;
	
	private $ch = NULL;
	
	
	/**
	 *
	 * @param string $ip
	 */
	function __construct($ip)
	{
		$this->port = self::PORT;
		$this->ip = $ip;
		
		$this->getState();
	}
	
	function getState()
	{
		$this->_setupCurl();
		
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, array('Accept: ', 
			'Content-type: text/xml; charset="utf-8"', 
			'SOAPACTION: "urn:Belkin:service:basicevent:1#GetBinaryState"'));
		
		curl_setopt($this->ch, CURLOPT_POSTFIELDS,'<?xml version="1.0" encoding="utf-8"?><s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:GetBinaryState xmlns:u="urn:Belkin:service:basicevent:1"></u:GetBinaryState></s:Body></s:Envelope>');
		
		$response = curl_exec($this->ch);

		$this->_parseState($response);
		
		return $this->state;
	}
	
	private function _setupCurl()
	{
		if(!is_null($this->ch))
			return;
		
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL,'http://'.$this->ip.':'.$this->port.'/upnp/control/basicevent1');
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
		curl_setopt($ch, CURLOPT_USERAGENT, '');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		
		$this->ch = $ch;
	}
	
	public function closeConnection()
	{
		if(is_null($this->ch));
			return;
		curl_close($this->ch);
		$this->ch = null;
	}

	private function _parseState($response)
	{
		if(!isset($response) || $response == '')
			throw new Exception('Invalid Response, empty.');
		
		$matches = preg_match('/<BinaryState>(.*)<\/BinaryState>/s', $response, $status);
		
		Debug::print_r($response, 'Response');
		Debug::print_r($status, 'Status');
		Debug::print_r($matches, 'Matches');
		
		switch($matches)
		{
			case 0:
				throw new Exception('Missing BinaryStates');
				break;
			case FALSE:
				throw new Exception('Error Parsing Response');
				break;
			case 1:
			default:
				break;
		}
		
		if($status[1] == 'Error')
			Throw new Exception('Wemo Error');
		
		$this->state = self::_validateStatus($status[1]);
	}
	
	private static function _validateStatus($state)
	{
		switch($state)
		{
			case self::OFF:
			case self::ON:
				return $state;
				break;
			default:
				throw new Exception('Invalid Status');
		}
	}
	
	function setState($state)
	{
		$this->getState();
		
		if($this->state == $state)
			return true;
		
		$this->_setupCurl();
		
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, array('Accept: ', 
			'Content-type: text/xml; charset="utf-8"', 
			'SOAPACTION: "urn:Belkin:service:basicevent:1#SetBinaryState"'));
		
		curl_setopt($this->ch, CURLOPT_POSTFIELDS,'<?xml version="1.0" encoding="utf-8"?><s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:SetBinaryState xmlns:u="urn:Belkin:service:basicevent:1"><BinaryState>'.$state.'</BinaryState></u:SetBinaryState></s:Body></s:Envelope>');
		
		$response = curl_exec($this->ch);
		
		$this->_parseState($response);
		
		if($state == $this->state)
			return true;
		else
		{
			Debug::print_r($state, 'state');
			Debug::print_r($this->state, 'This->State');
			throw new Exception('Error changing state');
		}

	}
	
	function flipSwitch()
	{
		$current = $this->getState();
		switch($current)
		{
			case self::ON: $this->setState(self::OFF); break;
			case self::OFF: $this->setState(self::ON); break;
		}
	}
	
	function switchChanged()
	{
		$old_state = $this->state;
		if($old_state == $this->getState())
			return false;
		else
			return true;
	}
}
