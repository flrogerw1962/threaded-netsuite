<?php

class LivePos_Maps_Itemlist extends LivePos_Maps_Map{

	public $hasWebItems = false;

	protected $_webSkus = array('Ship', 'Custom Art');
	protected $_itemList = array();

	/**
	 * List of the PreDiscount Skus of Items in List
	 * @var array
	 * @access protected
	*/
	protected $_listSkus = array();

	protected $_productList = array();
	
	protected $_webItems = array();

	/**
	 * Has made call to DB for Order's Products
	 * @var boolean
	 * @access private
	*/
	private $_calledProducts = false;

	/**
	 *
	 * @access public
	 * @return void
	 */
	public function __construct( array $aItems, $locationData ) {

		parent::__construct();
		$this->_aData = $aItems;
		$this->_getItemList( $locationData );
		$this->_skusToNsIds();
	}


	private function _getItemList( $locationData ){

		foreach( $this->_aData as $aItem ){

			$item = LivePos_Maps_MapFactory::create( 'item', $aItem, $locationData );

			if( in_array( $item->item, $this->_webSkus ) ){
				$this->_webItems[] = $item;
				$this->hasWebItems = true;
				continue;
			}

			$this->_itemList[] = $item;
		}
	}
	
	public function addItem( LivePos_Maps_Item $oItem ){
		
		$this->_itemList[] = $oItem;
	}
	
	public function getWebItemsTotal(){
		$fTotal = 0;
		
		array_walk( $this->_webItems, function($oItem, $sKey) use (&$fTotal){
			$fTotal += ( $oItem->getPrice() * $oItem->getQuantity() );
		});
		return( $fTotal );
	}
	
	public function getTotal(){
		
		$fTotal = 0;
		
		array_walk( $this->_itemList, function($oItem, $sKey) use (&$fTotal){
			$fTotal += ( $oItem->getPrice() * $oItem->getQuantity() );
		});
		
			return( $fTotal );
	}
	
	/**
	 * Gets Total of Items Before Discount is Applied
	 * @return number
	 * @deprecated
	 */
	public function getPreDiscountTotal(){

		$fPreDiscountTotal = 0;

		array_walk( $this->_itemList, function($oItem, $sKey) use (&$fPreDiscountTotal){
			$fPreDiscountTotal += ( $oItem->getPreDiscountPrice() * $oItem->getQuantity() );
		});

			return( $fPreDiscountTotal );
	}



	public function popPreDiscountPrices(){

		$this->_getProductsBySku();
	}

	public function hasItems(){

		$bReturn = ( !empty( $this->_itemList ) )? true: false;
		return( $bReturn );
	}

	public function getItemsArray(){

		$aItemsArray = array();

		foreach( $this->_itemList as $oItem ){

			$aItemsArray[] = $oItem->getPublicVars();
		}
		return( $aItemsArray );
	}

	public function applyDiscount( LivePos_Maps_Discount $discount ){

		array_walk( $this->_itemList, function(&$oItem, $sKey) use ($discount){
			
			$fDiscountAmount = $discount->getDiscountTotal( $oItem->getPrice() );			
			$oItem->applyDiscount( $fDiscountAmount );
		});
	}

	public function getItems(){

		return( $this->_itemList );
	}

	private function _getProductsBySku(){

		$model = new LivePos_Db_Model();
		$aProductData = $model->getProducts( $this->_listSkus );
		$model = null;

		foreach( $aProductData as $aProduct ){
			$product = LivePos_Maps_MapFactory::create( 'product', $aProduct  );
			$this->_productList[ $product->productsku ] = $product;
		}

		array_walk( $this->_itemList, function(&$oItem, $sKey){
			$oItem->setPreDiscountPrice( $this->_productList[ $oItem->getSku() ]->getPrice() );
		});

			$this->_calledProducts = true;
	}

	private function _skusToNsIds(){

		if( $this->hasItems() ){

			$aSkusArray = array();

			// Create Array of Item Skus
			array_walk( $this->_itemList, function($oData, $sKey) use (&$aSkusArray){
				$aSkusArray[] = $oData->item;
			});

				$model = new LivePos_Db_Model();
				$aNsData = $model->skusToNsId( array_unique( $aSkusArray ) );

				// Replace Skus
				array_walk( $this->_itemList, function(&$oData, $sKey) use (&$aNsData){
					$this->_listSkus[] = $oData->item;
					$oData->item = ( $aNsData[ $oData->item ]['id'] == null )? $oData->item: $aNsData[ $oData->item ]['id'];
				});

					$model = null;
		}
	}
}