<?
namespace Pixite\Catalog;

use \Bitrix\Main\Config\Option;
use \Bitrix\Main\Type\DateTime;
use \Bitrix\Main\Result;
use \Bitrix\Main\Error;

class Section extends \Pixite\Directory\Lists {

    function __construct() {

        parent::__construct();

        $this->setIblockId = CATALOG;

    }
   
	/*
	* Поиск раздела каталога по внешнему коду
	*/
	public function getByXMLID($xmlId, $arSelect = ['ID']) {

        $rsSections = \CIBlockSection::GetList([], ['IBLOCK_ID' => CATALOG, 'XML_ID' => $xmlId], false, $arSelect);
        while ($arSection = $rsSections->Fetch()) {
            error_log("<pre>arSection" . date('d.m.Y H:i:s') . "\n" . print_r($arSection ,true)."</pre>\n", 3, $_SERVER['DOCUMENT_ROOT'].'/log.log');

            return $arSection;
        }

        return false;
	}  

    /*
	* Добавление раздела
	*/
	protected function add($arFields) {
        
        $arFilter['IBLOCK_ID'] = $this->iblockId;
        $arFields['ACTIVE'] = 'Y';
        $arFields['TIMESTAMP_X'] = new \Bitrix\Main\Type\DateTime();

        $this->result = \Bitrix\Iblock\SectionTable::add($arFields);        

	}

    /*
	* Обновление раздела
	*/
	protected function update($id, $arFields) {

        //$arFields['TIMESTAMP_X'] = new \Bitrix\Main\Type\DateTime();

        $el = new \CIBlockSection;

        if($el->Update($id, $arFields))
            $this->result->setData(['result' => $id]);
        else
            $this->result->addError(new Error($el->LAST_ERROR));

	}

    /*
	* Обновление раздела
	*/
	public function import($arFields, $delete) {

        if($arFields['IBLOCK_SECTION_ID'] == '00000000-0000-0000-0000-000000000000')
            unset($arFields['IBLOCK_SECTION_ID']);
        else {
            $section = \Pixite\Main\ElementMappingSave::search($arFields['IBLOCK_SECTION_ID'], 'section');
            if(empty($section)) {
                $section = $this->getByXMLID($arFields['IBLOCK_SECTION_ID']);
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

        $element = \Pixite\Main\ElementMappingSave::search($arFields['XML_ID'], 'section');
        if(empty($element))
            $element = $this->getByXMLID($arFields['XML_ID']);
        if(!$element)
            $this->add($arFields);
        else
            $this->update($element['ID'], $arFields);

        \Pixite\Main\ElementMappingSave::save($this->result, $arFields, 'section', false); 

        return $this->result;

	}
	     
}