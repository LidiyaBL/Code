<?
namespace Pixite;

use \Bitrix\Main\Result;
use \Bitrix\Main\Error;
use \Bitrix\Main\Loader;

Loader::includeModule('crm'); 
Loader::includeModule('iblock'); 
Loader::includeModule('main'); 

abstract class CRMObject {

    protected $result;

    function __construct() {

        $this->result = new Result;
 
    }

    /*
    * Импорт
    */
    abstract public function import($arFields, $delete);

    /*
    * Поиск
    */
    abstract public function search($arFilter, $arSelect);

    /*
    * Добавление
    */
    abstract protected function add($arFields);

    
    /*
    * Обновление
    */
    abstract protected function update($id, $arFields);

    /*
    * Удаление
    */
    abstract protected function delete($id);


}