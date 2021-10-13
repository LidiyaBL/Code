<?
namespace Pixite\Catalog;

use \Bitrix\Main\Result;
use \Bitrix\Main\Error;

class Unit {

    /*
	* Поиск
	*/
	public function getUnitByCode($code, $arSelect = ['ID']) {

        $dbItems = \Bitrix\Catalog\MeasureTable::getList([
            'order' => [], 
            'select' => $arSelect,
            'filter' => ['CODE' => $code]            
        ]);
        while ($arItem = $dbItems->fetch()) {	
            return $arItem;	
        }

        return false;
	}

    /*
	* Добавление
	*/
	protected function add($arFields) {

        $result = new Result;
        
        $result = \Bitrix\Catalog\MeasureTable::add($arFields);
        
        return $result;
	}

    /*
	* Обновление
	*/
	protected function update($id, $arFields) {

        $result = \Bitrix\Catalog\MeasureTable::update($id, $arFields);        

        return $result;

	}

    /*
	* Удаление
	*/
	protected function delete($id, $arFields) {

        $result = \Bitrix\Catalog\MeasureTable::update($id, $arFields);        

        return $result;

	}

    /*
	* Импорт товара
	*/
	public function import($arFields, $delete) {

        $element = self::getUnitByCode($arFields['CODE']);
        if(!$element)
            $result = self::add($arFields);
        elseif($delete)
            $result = self::delete($element['ID'], $arFields);
        else
            $result = self::update($element['ID'], $arFields);

        return $result;

	}


   
     
}