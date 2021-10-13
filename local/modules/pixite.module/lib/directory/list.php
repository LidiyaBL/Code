<?
namespace Pixite\Directory;

use \Bitrix\Main\Result;
use \Bitrix\Main\Error;
use \Bitrix\Main\Config\Option;
use \Bitrix\Main\Type\DateTime;


class Lists extends \Pixite\CRMObject {

    protected $iblockId;
    protected $iblockTable;

    function __construct() {

        parent::__construct();

    }

    public function setIblockId($iblockId) {

        $this->iblockId = intval($iblockId);
    }

    /*
	* Импорт элемента списка
	*/
	public function import($arFields, $delete) {

        $arFields['IBLOCK_ID'] = $this->iblockId;
        $arFields['TIMESTAMP_X'] = new DateTime();
        $arFields['ACTIVE'] = 'Y';

        $element = current($this->search(['XML_ID' => $arFields['XML_ID']], ['ID']));
        
        if(!$element)
            $this->add($arFields);
        elseif($delete)
            $this->delete($element['ID']);
        else
            $this->update($element['ID'], $arFields);

        return $this->result;

	}

    /*
	* Поиск элемента
	*/
	public function search($arFilter, $arSelect) {

        $arFilter['IBLOCK_ID'] = $this->iblockId;
        $arFilter['ACTIVE'] = 'Y';

        $dbItems = \Bitrix\Iblock\ElementTable::getList([
            'order' => [], 
            'select' => $arSelect,
            'filter' => $arFilter           
        ]);
        while ($arItem = $dbItems->fetch()) {	
            $arItems[] = $arItem;	
        }

        return (!empty($arItems) && is_array($arItems) ? $arItems : false);
	}

    /*
	* Добавление элемента
	*/
	protected function add($arFields) {        

        $Element = new \CIBlockElement;
        
        if($id = $Element->Add($arFields))
            $this->result->setData(['result' => $id]);         
        else
            $this->result->addError(new Error($Element->LAST_ERROR));
   
	}

    /*
	* Обновление товара
	*/
	protected function update($id, $arFields) {       

        $Element = new \CIBlockElement;

        if($Element->Update($id, $arFields))
            $this->result->setData(['result' => $id]);
        else
            $this->result->addError(new Error($Element->LAST_ERROR));  

	}

    /*
	* Удаление товара
	*/
	protected function delete($id) {       

        $Element = new \CIBlockElement;

        if($Element->Update($id, ['ACTIVE' => 'N']))
            $this->result->setData(['result' => $id]);
        else
            $this->result->addError(new Error($Element->LAST_ERROR));  

	}

    /*
	* Поиск элемента
	*/
	public function searchORM($arFilter, $arSelect) {

        if(!empty($this->iblockTable)) {

            $arItems = [];
            $elements = $this->iblockTable::getList([
                'select' => $arSelect,
                'filter' => $arFilter,        
            ])->fetchCollection();
            foreach($elements as $element) {            
                $arItems[] = $element;
            }

            return $arItems;
        }       

	}

  

     
}