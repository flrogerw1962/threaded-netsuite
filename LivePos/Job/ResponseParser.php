<?php
final class LivePos_Job_ResponseParser {

	public function __construct() {

	}

	/**
	 * Parse GetReceipts Response
	 *
	 * @param string $data
	 * @return array $returnArray - array od receipt ID's
	 */
	public function GetReceiptIds( $data ){

		$returnArray = array();
		$aReceipts = json_decode($data);
		
		foreach( $aReceipts as $oReceipt ){
			$returnArray[] = $oReceipt->intReceiptNumber;
		}
		
		return($returnArray);
	}
	
	public function GetProductIds( $data ){
	
		$returnArray = array();
		$aProducts = json_decode($data);
	
		foreach( $aProducts as $oProduct ){

			$returnArray[] = $oProduct->strProductSKU;
		}
	
		return($returnArray);
	}
}