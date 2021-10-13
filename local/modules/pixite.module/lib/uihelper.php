<?
namespace Pixite;

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Config\Option;

Loc::loadMessages(__FILE__);

class UIHelper {

    /*
    * Вычисление хеша
    */
    public function hashData(array &$data) {

        return md5(serialize($data));
        
    }
    
}