<?
namespace Pixite;

use \Bitrix\Main\Result;
use \Bitrix\Main\Error;
use \Bitrix\Main\Loader;

Loader::includeModule('crm'); 
Loader::includeModule('iblock'); 
Loader::includeModule('main'); 

abstract class Export {

    protected $result;

    function __construct() {

        $this->result = new Result;
 
    }

    /*
    * Сбор данных и выгрузка
    */
    abstract public function export($id = false);

    /*
    * Список выгружаемых элементов
    */
    abstract protected function setExportElement(array $params);

    /*
    * Данные выгружаемых элементов
    */
    abstract protected function getData();

    /*
    * Опеределение полей и свойств для выгрузки
    */
    abstract protected function setFields();

    /*
    * Опеределение полей и свойств для выгрузки
    */
    abstract protected function buildingData();



}