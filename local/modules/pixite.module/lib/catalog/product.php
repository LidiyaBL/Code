<?
namespace Pixite\Catalog;

use \Bitrix\Main\Config\Option;
use \Bitrix\Main\Type\DateTime;
use \Bitrix\Main\Result;
use \Bitrix\Main\Error;

use \Pixite\Internals\ProductTermsProductionTable;
use \Pixite\Directory\HL;

class Product extends \Pixite\Directory\Lists {

    protected const VAT0 = 1;
	protected const VAT20 = 2;

    function __construct() {

        parent::__construct();

        $this->setIblockId = CATALOG;

    }

    /*
	* Поиск раздела товара по внешнему коду
	*/
	public function getProductByXMLID($xmlId, $arSelect = ['ID']) {

        $dbItems = \Bitrix\Iblock\ElementTable::getList([
            'order' => [], 
            'select' => $arSelect,
            'filter' => ['IBLOCK_ID' => CATALOG, 'XML_ID' => $xmlId]            
        ]);
        while ($arItem = $dbItems->fetch()) {	
            return $arItem;	
        }

        return false;
	}

    /*
	* Добавление товара
	*/
	protected function add($arFields) {
        
        $arFilter['IBLOCK_ID'] = $this->iblockId;
        $arFields['TIMESTAMP_X'] = new DateTime();
        $productFileds = $arFields['PRODUCT_FIELDS'];
        unset($arFields['PRODUCT_FIELDS']);

        $el = new \CIBlockElement;
        
        if($id = $el->Add($arFields)) {
            
            $productFileds['ID'] = $id; 
            $productFileds['TYPE'] = \Bitrix\Catalog\ProductTable::TYPE_PRODUCT; //Тип товара Простой

            if(\Bitrix\Catalog\Model\Product::add($productFileds)) {
                $this->result->setData(['result' => $id]);
            }
        }            
        else
            $this->result->addError(new Error($el->LAST_ERROR));

	}

    /*
	* Обновление товара
	*/
	protected function update($id, $arFields) { 

        $el = new \CIBlockElement;

        $arFields['TIMESTAMP_X'] = new DateTime();

        if($el->Update($id, $arFields))
            $this->result->setData(['result' => $id]);
        else
            $this->result->addError(new Error($el->LAST_ERROR));
 
	}

    /*
	* Импорт товара
	*/
	public function import($arFields, $delete) {

        if(!empty($arFields['PROPERTY_VALUES']['PRODUCER'])) {
            $hl = 2;
            $Directory = new HL($hl);//TODO
            $producer = $Directory->search(['UF_XML' => $arFields['PROPERTY_VALUES']['PRODUCER']]);	
            
            if(!empty($producer['UF_NAME']))
                $arFields['PROPERTY_VALUES']['PRODUCER'] = $producer['UF_NAME'];
        }

        if($arFields['IBLOCK_SECTION_ID'] == '00000000-0000-0000-0000-000000000000')
            unset($arFields['IBLOCK_SECTION_ID']);
        else {
            $section = \Pixite\Main\ElementMappingSave::search($arFields['IBLOCK_SECTION_ID'], 'section');
            if(empty($section)) {
                $Section = new \Pixite\Catalog\Section;
                $section = $Section->getByXMLID($arFields['IBLOCK_SECTION_ID']);
            }
            
            $arFields['IBLOCK_SECTION_ID'] = $section['ID'];

            if(empty($arFields['IBLOCK_SECTION_ID'])) {
                $this->result->addError(new Error('Не найден раздел-родитель'));
                return $this->result;
            }					
        }

        if($delete)
            $arFields['ACTIVE'] = 'N';
        else
            $arFields['ACTIVE'] = 'Y';

        $element = \Pixite\Main\ElementMappingSave::search($arFields['XML_ID'], 'product');
        error_log("<pre>element1".print_r($element,true)."</pre>\n", 3, $_SERVER['DOCUMENT_ROOT'].'/log.log');
        if(!$element)
            $element = $this->getProductByXMLID($arFields['XML_ID']);

        $vat = $arFields['VAT'];        
        $unit = $arFields['UNIT'];
        unset($arFields['VAT']);
        unset($arFields['UNIT']);
        if($vat == 'НДС20') {
            $productFileds['VAT_ID'] = VAT20;
            $productFileds['VAT_INCLUDED'] = 'Y';
        }
        if($vat == 'НДС0') {
            $productFileds['VAT_ID'] = VAT0;
            $productFileds['VAT_INCLUDED'] = 'Y';
        }

        $hl = 3;
        $Directory = new \Pixite\Directory\HL($hl);//TODO
        $unitHl = $Directory->search(['UF_XML' => $unit]);	
        if(!empty($unitHl['UF_CODE'])) {
            $unitBx = \Pixite\Catalog\Unit::getUnitByCode($unitHl['UF_CODE']);
        }
        if($unitBx['ID'])
            $productFileds['MEASURE'] = $unitBx['ID'];

        if(!$element) {
            $arFields['PRODUCT_FIELDS'] = $productFileds;
            $this->add($arFields);
        }            
        else {
            unset($productFileds['VAT_ID']);
            unset($productFileds['VAT_INCLUDED']);
            $this->update($element['ID'], $arFields);
        }            

        \Pixite\Main\ElementMappingSave::save($this->result, $arFields, 'product', false); 

        return $this->result;

	}

    /*
	* Обновление остатка
	*/
	public function updateQuantity($arFields) {
        
        $element = \Pixite\Catalog\Product::getProductByXMLID($arFields['XML_ID']);
        $quantity = intval($arFields['QUANTITY']) + intval($arFields['RESERVE']);
        $result = \Bitrix\Catalog\ProductTable::update($element['ID'], ['QUANTITY' => $quantity, 'AVAILABLE' => ($quantity > 0 ? 'Y' : 'N')]);

        \CIBlockElement::SetPropertyValuesEx($element['ID'], false, ['QUANTITY' => (intval($quantity) - intval($arFields['RESERVE']))]);
        \CIBlockElement::SetPropertyValuesEx($element['ID'], false, ['QUANTITY_WITH_RESERVE' => $quantity]);

        return $result;

    }

    /*
	* Обновление цены
	*/
    function updatePrice($arFields) {
        
        $element = \Pixite\Main\ElementMappingSave::search($arFields['XML_ID'], 'product');
        error_log("<pre>element1".print_r($element,true)."</pre>\n", 3, $_SERVER['DOCUMENT_ROOT'].'/log.log');
        if(!$element)
            $element = $this->getProductByXMLID($arFields['XML_ID']);
        error_log("<pre>element2".print_r($element,true)."</pre>\n", 3, $_SERVER['DOCUMENT_ROOT'].'/log.log');

        $rsP = \Bitrix\Catalog\PriceTable::getList([
            'filter' => ['CATALOG_GROUP_ID' => BASE_PRICE_ID, 'PRODUCT_ID' => $element['ID']],
        ]);

        if($arP = $rsP->fetch()) {
            $result = \Bitrix\Catalog\PriceTable::update($arP['ID'], ['PRICE' => $arFields['PRICE'], 'PRICE_SCALE' => $arFields['PRICE']]);
        }
        else {
            $result = \Bitrix\Catalog\PriceTable::add([
                'CATALOG_GROUP_ID' => BASE_PRICE_ID,
                'PRODUCT_ID' =>  $element['ID'],
                'PRICE' => $arFields['PRICE'],
                'PRICE_SCALE' => $arFields['PRICE'],
                'CURRENCY' => 'RUB'
            ]); 
        }

        return $result;

    }

    /*
	* Поиск товара
	*/
	public function getProducts($arFilter = [], $arSelect = ['ID']) {

        $res = \CIBlockElement::GetList([], array_merge(['IBLOCK_ID' => CATALOG, 'ACTIVE' => 'Y'], $arFilter), false, false, $arSelect);
        while($arItem = $res->GetNext()) {
            $arItems[] = $arItem;	
        }

        return $arItems;
	}

    /*
	* Поиск раздела товара по названию
	*/
	public function getProductProductionTime($arFilter = [], $arSelect = ['ID']) {

        $arProduct = self::getProducts($arFilter, $arSelect);
        if(!empty($arProduct[0]['XML_ID'])) {
            $Table = new \Pixite\Directory\Table;
			$Table->setTableName(new ProductTermsProductionTable);
            $result = $Table->search(['PRODUCT' => $arProduct[0]['XML_ID']], ['DAY', 'COMMENT']);	
            if(intval($result['DAY']) > 0)
                $arProduct[0]['TIME'] = intval($result['DAY']);			
            if(strlen($result['COMMENT']) > 0)
                $arProduct[0]['COMMENT'] = $result['COMMENT'];			
        }

        return $arProduct;

	}
     
}