<?
namespace Pixite\Catalog;

use \Bitrix\Main\Result;
use \Bitrix\Main\Error;
use \Pixite\Internals\ProductAnalogDifferenceMappingTable;

class AnalogDifferenceMapping extends \Pixite\Directory\Table {

    function __construct() {

        parent::__construct();

        $this->setTableName(new ProductAnalogDifferenceMappingTable);
 
    }

    /*
    * Загрузка
    */
    public function import($arFields, $delete) { 
        
        $arFields['DIFFERENCE_TYPE'] = $arFields['TYPE'];
        unset($arFields['TYPE']);
      
        $element = $this->search(['ANALOG_ROW' => $arFields['ANALOG_ROW'], 'DIFFERENCE_XML_ID' => $arFields['DIFFERENCE_XML_ID']], ['*']);

        if(empty($element['ID'])) {
            $this->add($arFields);
        }
        else {
            $this->update($element['ID'], $arFields);
        }

        return $this->result;    

    }   

}