<?php

class Thread_Server {

	protected $_model;
	public $orders;
	protected $_pool;
	public $orderCount;

	public function __construct(){

		$this->_pool = new Thread_Pool( MAX_THREADS );
		$this->_model = new Netsuite_Db_Model();
		$this->_activa = new Netsuite_Db_Activa();
		//$this->_model->resetStalledOrders();
		$this->orders = $this->_model->readOrderQueue( MAX_ORDER_RECORDS );
		if( $this->hasOrders() ){
			$this->_hasDuplicates();
		}
	}
	

	/**
	 * Looks at Current Order Array for Duplicate Orders
	 * @return void
	 */
	protected function _hasDuplicates(){

		$aCurrentOrders = array();
		$aSetToDuplicate = array();
		$aNewOrders = array();

		array_walk( $this->orders, function( $aOrder, $iKey ) use( &$aCurrentOrders){
			$aCurrentOrders[$aOrder['order_activa_id']] = $aOrder['queue_id'];
		});

			$aDupOrders = Netsuite_Record_SalesOrder::hasBeenProcessed( array_keys($aCurrentOrders) );

			if(!empty($aDupOrders)){

				$aSetToDuplicate = array_values(array_intersect_key($aCurrentOrders, array_flip(array_intersect( $aDupOrders, array_keys($aCurrentOrders) ))));
				$this->_model->setOrdersDuplicate( $aSetToDuplicate );

				array_walk( $this->orders, function( $aOrder, $iKey ) use( &$aSetToDuplicate, &$aNewOrders){
						
					if( !in_array($aOrder['queue_id'], $aSetToDuplicate) ){
						$aNewOrders[] = $this->orders[$iKey];
					}
				});
					$this->orders = $aNewOrders;
			}
	}

	protected function _setOrders(){

		$this->_model->setOrderWorking( $this->orders );
		$this->_activa->setOrderWorking( $this->orders );
	}

	public function hasOrders(){

		$bReturn = ( sizeof( $this->orders ) < 1 )? false: true;
		if( $bReturn ){
			$this->_setOrders();
		}
		return( $bReturn );
	}

	public function poolOrders() {

		foreach( $this->orders as $aOrder ){

			$sOrderData = json_decode( $this->_decrypt( $aOrder['order_json'] ), true );

			$this->_replaceBool( $sOrderData );
			$aWork[] = $tThread = $this->_pool->submit( new Netsuite_Netsuite( $sOrderData, $aOrder['queue_id'], $aOrder['order_activa_id'] ) );

		}

		$this->_pool->shutdown();

		if( DEBUG ){
			foreach($this->_pool->workers as $worker) {
				print_r($worker->getData());	
			}
		}
	}

	protected function _replaceBool( &$aArray ){

		$aIsBoolean = array(
				'isperson',
				'custcol_produce_in_store',
				'custcol_store_pickup',
				'ismultiship',
				'_netsuite',
				'istaxable'
		);

		foreach( $aArray as $key=> &$data ){
			if( is_array( $data ) ){
				$this->_replaceBool($data);
			}else{
				if( in_array( $key, $aIsBoolean ) ){
					$aArray[$key] = ( $data == '1' )? true: false;
				}
			}

		}
	}
	
	protected function _decrypt( $sData ){

		$td = mcrypt_module_open( 'rijndael-128', '', 'ecb', '' );
		$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size( $td ), MCRYPT_RAND);
		$ks = mcrypt_enc_get_key_size( $td );
		$key = substr( md5( SECRET_KEY ), 0, $ks );

		mcrypt_generic_init( $td, $key, $iv );
		$decrypted = mdecrypt_generic( $td, base64_decode( $sData ) );
		$decrypted = rtrim($decrypted,"\0");
		mcrypt_generic_deinit( $td );
		mcrypt_module_close( $td );

		return( htmlspecialchars_decode($decrypted, ENT_QUOTES ) );

	}
}