<?php 

class LivePos_LivePosProduct extends Stackable {

	protected $_productId;
	protected $_sessionId;
	protected $_errors = array();
	protected $_locationId;

	public function __construct( $iProductId, $sSessionId ){

		$this->_productId = $iProductId;
		$this->_sessionId = $sSessionId;
	}


	public function run(){

		spl_autoload_register(function ($sClass) {
			$sClass = str_replace( "_", "/", $sClass );
			include $sClass . '.php';
		});

			try{

				$call = new LivePos_Job_GetRecord( false );
				$call->sendRequest('GetProductDetails', $this->_sessionId, array('strProductSKU' => $this->_productId));

				if( $call->isOk() ){

					$aResponse = $call->getResponse();
					$product = LivePos_Maps_MapFactory::create( 'product', json_decode( $aResponse['data'], true ) );
						
					$this->worker->addData( array('code' => $aResponse['code']) );
					$this->worker->addData( array('product' => $product->getPublicVars() ) );
					$this->worker->addData( array('error' => implode( ',', $this->_errors ) ) );
				}else{

					$aResponse = $call->getResponse();
					$this->worker->addData( array('productId' => $this->_productId ) );
					$this->worker->addData( array('code' => $aResponse['code']) );
					$this->worker->addData( array('error' => implode( ',', $call->getErrors() ) ) );
				}

			} catch( Exception $e ) {

				LivePos_Db_Model::logError( $e->getMessage() );
				$this->worker->addData( array('productId' => $this->_productId) );
				$this->_errors[] = $e->getMessage();
				$this->worker->addData( array('error' => implode( ',', $this->_errors ) ) );

			}
	}
}