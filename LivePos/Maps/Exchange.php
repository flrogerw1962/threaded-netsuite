<?php 
/**
 * LivePOS Refund Object
 *
 * @package Netsuite
 * @subpackage LivePOS
 * @author gWilli
 * @version 1.0
 * @copyright 2014
 * @name LivePOS Refund
 */
/**
 * LivePOS Receipt to Netsuite Record Refund Object
 *
 * Thread for Processing Receipts into Netsuite.  Captures Refund from LivePOS Receipt and
 * Converts the Data into a Netsuite Refund and Inserts into the Queue for Insertion
 * into Netsuite.
 *
 * @uses Configure
 * @uses LivePos_Maps_Map
 * @package Netsuite
 * @subpackage LivePOS
 * @final Can NOT Extend
 */
final class LivePos_Maps_Exchange extends LivePos_Maps_Map {
	
	public $account = 112; // Undeposited Funds
	public $department;
	public $entity;
	public $item = array();
	public $location;
	public $memo;
	public $paymentmethod = 8;
	public $trandate;
	
	
	protected $_refReceipt;
	protected $_orderId;
	
	protected $_mapArray = array(
			'intReferenceReceiptNumber' => '_refReceipt',
			'dtTransactionDate' => 'trandate' );
	
	/**
	 *
	 * @access public
	 * @return void
	 */
	public function __construct( array $aExchange ) {
	
		parent::__construct();
		$this->_aData = $aExchange;
		//$this->_map();
		//$this-> _setInternalSources( $locationData );
		//$this->_logic( $items );
	}
	
	public function getRefundJson(){
		
		$aRefund = $this->_aData;
		
		
		// intProductSoldUnits
		
		
		
		return( $this->_aData['enumProductsSold'] );
		//return( json_encode( $this->_aData ) );
		
	}
	
	public function getSaleJson(){
	
		$aSalesOrder = $this->_aData;
		$aItems = array();
		
		foreach( $aSalesOrder['enumProductsSold'] as $value ){
			
			if( $value['intProductSoldUnits'] > 0 ){
				$aItems[] = $value;
			}
		}
		
		$aSalesOrder['enumProductsSold'] = $aItems;

		return( json_encode( $aSalesOrder ) );
	}
	
	private function _logic( $items ){
	
		$this->memo = 'Original Receipt Id: ' . $this->_refReceipt;
		$this->item = $items->getItemsArray();
	
		$date = new DateTime( $this->trandate );
		$this->trandate =  $date->format('m/d/Y');
	}
	
	private function _setInternalSources( $locationData ){
	
		$this->entity = (int) $locationData['location_entity'];
		$this->location = (int) $locationData['location_netsuite_id'];
		$this->department = (int) $locationData['location_netsuite_department'];
	}
}
