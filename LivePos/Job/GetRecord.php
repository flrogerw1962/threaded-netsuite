<?php

final class LivePos_Job_GetRecord {


	private $_response = array();
	private $_hasErrors = false;
	private $_authResponse;


	public function __construct( $bCreateAuth = true ) {
		
		if( $bCreateAuth ){
			$this->_sendAuth();
			if( !$this->isOk() ){
				throw new Exception('Could Not get Auth String from LivePOS: ' . $this->_response);
			}
		}
	}

	public function isOk(){

		$bIsOk = ( $this->_hasErrors )? false: true;
		return( $bIsOk );
	}

	public function getResponse(){

		return( $this->_response );
	}

	public function getSessionId(){

		$oResponse = json_decode( $this->_authResponse );
		return( $oResponse[0]->strAPISessionKey );

	}

	private function _setHeader( $iContentLength = 0, $sSessionId ){

		$header = array(
				'APISessionKey: ' . $sSessionId,
				'Content-Type: application/json',
				'Content-length: '. $iContentLength,
		'Accept: */*');

		return( $header );
	}

	public function sendRequest( $requestType, $sSessionId, array $params = null ){
		
		$sPayload = ( $params == null )? $params: json_encode( $params );
				
		$options = array(
				
				//CURLOPT_HEADER =>true,
				//CURLINFO_HEADER_OUT => true,
				//CURLOPT_VERBOSE => true,
				
				CURLOPT_URL => LIVEPOS_URL . $requestType,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_POST => 1,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => $sPayload,
				CURLOPT_HTTPHEADER => $this->_setHeader( strlen($sPayload), $sSessionId ),
				CURLOPT_RETURNTRANSFER => true );
		
		$cURL = curl_init();
		
		curl_setopt_array( $cURL, $options );
		
		for( $i=0; $i<3; $i++ ) {
		
			$curl_result = curl_exec($cURL);
					
			if ( curl_errno($cURL) == 0 ) {
				
				$this->_response['data'] = $curl_result;
				$this->_response['code'] = curl_getinfo($cURL, CURLINFO_HTTP_CODE);

				curl_close($cURL);
				return;
			}
		}
		
		$this->_response['error'] =  'cURL Error: ' . curl_error( $cURL );
		$this->_hasErrors = true;
		curl_close($cURL);
		return;
		}
	
	
	private function _setAuthHeader( $iContentLength = 0 ) {

		$auth_header = array(
				'APIApplicationKey: ' . LIVEPOS_API_KEY,
				'APIApplicationID: ' . LIVEPOS_API_ID,
				'Content-length: '. $iContentLength,
				'Content-Type: application/json',
				
		'Accept: */*');

				return( $auth_header );
	}

	private function _sendAuth(){

		$loginCredentials = array(
				'strAdminUserName'=> LIVEPOS_USER,
				'strAdminPassword'=> LIVEPOS_PASS,
				'strAdminSecurityCode'=> LIVEPOS_API_CODE,
		);

		$sPayload = json_encode( $loginCredentials );

		$options = array(
				CURLOPT_URL => LIVEPOS_AUTH_URL,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_POST => 1,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => $sPayload,
				CURLOPT_HTTPHEADER => $this->_setAuthHeader( strlen($sPayload) ),
				CURLOPT_RETURNTRANSFER => true );

		$cURL = curl_init();
		curl_setopt_array( $cURL, $options );

		for( $i=0; $i<3; $i++ ) {

			$curl_result = curl_exec($cURL);

			if ( curl_errno($cURL) == 0 ) {
				curl_close($cURL);
				$this->_authResponse = $curl_result;
				return;
			}
		}

		$this->_response =  'cURL Error: ' . curl_error( $cURL );
		$this->_hasErrors = true;
		curl_close($cURL);
	}
}