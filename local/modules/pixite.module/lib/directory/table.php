<?
namespace Pixite\Directory;

use \Bitrix\Main\Result;
use \Bitrix\Main\Error;

class Table extends \Pixite\CRMObject {

    protected $table;

    public function setTableName($Table) {

        $this->table = $Table;
    }
    
    /*
	* Импорт записи
	*/
	public function import($arFields, $delete) {

        $element = $this->search(['XML_ID' => $arFields['XML_ID']], ['ID']);

        if(!$element)
            $this->add($arFields);
        elseif($delete)
            $this->delete($element['ID']);
        else
            $this->update($element['ID'], $arFields);

        return $this->result;

	}

    /*
	* Поиск записи
	*/
	public function search($arFilter, $arSelect) {

        $db = $this->table->getList(['filter' => $arFilter, 'select' => $arSelect]);
		if($result = $db->fetch()) {
			return $result;
		}

        return false;
	}

    /*
	* Поиск всех записей
	*/
	public function searchAll($arFilter, $arSelect) {

        $arItems = [];
        $db = $this->table->getList(['filter' => $arFilter, 'select' => $arSelect]);
		if($result = $db->fetch()) {
			$arItems[] = $result;
		}

        return $arItems;
	}

    /*
	* Добавление записи
	*/
	protected function add($arFields) {    
        
        if($id = $this->table->add($arFields))
            $this->result->setData(['result' => $id]);         
        else
            $this->result->addError(new Error('Запись не добавлена'));
   
	}

    /*
	* Обновление записи
	*/
	protected function update($id, $arFields) {       

        if($this->table->update($id, $arFields))
            $this->result->setData(['result' => $id]);
        else
            $this->result->addError(new Error('Запись не обновлена'));  

	}

    /*
	* Удаление записи
	*/
	protected function delete($id) {       

        if($this->table->delete($id))
            $this->result->setData(['result' => $id]);
        else
            $this->result->addError(new Error('Запись не удалена')); 

	}  

     
}