<?
namespace Pixite\Crm;

use \Bitrix\Main\Result;
use \Bitrix\Main\Error;
use \Bitrix\Main\Type\DateTime;
use \Bitrix\Main\Config\Option;
use \Bitrix\Crm\ContactTable;
use \Pixite\Common;
use \Pixite\Crm\Company;

class Contact extends \Pixite\CRMObject {

    protected $contact;

    /*
    * Загрузка
    */
    public function import($arFields, $delete) {

        $this->contact = new \CCrmContact(false);

        if($arFields['BIRTHDATE'] !== '0001-01-01T00:00:00') {
            $arFields['BIRTHDATE'] = \Bitrix\Main\Type\DateTime::createFromPhp(new \DateTime($arFields['BIRTHDATE']));
        }
        else {
            unset($arFields['BIRTHDATE']);
        } 

        if(!empty($arFields['COMPANY_ID'])) {
            $company = current(Company::search([Common::getOption('COMPANY_GUID') => $arFields['COMPANY_ID']], ['ID', 'ASSIGNED_BY_ID']));
            error_log("<pre>company".print_r($company,true)."</pre>\n", 3, $_SERVER['DOCUMENT_ROOT'].'/log.log');
            if(!empty($company['ID']))
                $arFields['COMPANY_ID'] = $company['ID'];
            else
                unset($arFields['COMPANY_ID']);
        }

        if(!empty($arFields[Common::getOption('CONTACT_ROLE')])) {
            $Property = new \Pixite\Directory\UserField;
			$Property->initProperty(Common::getOption('CONTACT_ROLE'));
            $property = $Property->search(['XML_ID' => $arFields[Common::getOption('CONTACT_ROLE')]]);
            if(!empty($property['ID']))
                $arFields[Common::getOption('CONTACT_ROLE')] = $property['ID'];
            else
                unset($arFields[Common::getOption('CONTACT_ROLE')]);
        }
        
        /*
        if(!empty($arFields[Common::getOption('CONTACT_DEPARTAMENT_LIST')])) {
            $Property = new \Pixite\Directory\UserField;
			$Property->initProperty(Common::getOption('CONTACT_DEPARTAMENT_LIST'));
            $property = $Property->search(['XML_ID' => $arFields[Common::getOption('CONTACT_DEPARTAMENT_LIST')]], ['ID', 'VALUE']);
            if(!empty($property['ID'])){
                $arFields[Common::getOption('CONTACT_DEPARTAMENT_LIST')] = $property['ID'];
                $arFields[Common::getOption('CONTACT_DEPARTAMENT')] = $property['VALUE'];
            }
            else {
                unset($arFields[Common::getOption('CONTACT_DEPARTAMENT_LIST')]);
                unset($arFields[Common::getOption('CONTACT_DEPARTAMENT')]);
            }
                
        }
        */

        if(!empty($company['ASSIGNED_BY_ID']))
            $arFields['ASSIGNED_BY_ID'] = $company['ASSIGNED_BY_ID'];
        else 
            unset($arFields['ASSIGNED_BY_ID']);            
        
        $arFields['TYPE_ID'] = 'CLIENT';
       
        error_log("<pre>arFields" . date('d.m.Y H:i:s') . "\n" . print_r($arFields ,true)."</pre>\n", 3, $_SERVER['DOCUMENT_ROOT'].'/log.log');
  
        $contact = current($this->search([Common::getOption('CONTACT_GUID') => $arFields['XML_ID']], ['ID']));
        error_log("<pre>contact" . date('d.m.Y H:i:s') . "\n" . print_r($contact['ID'] ,true)."</pre>\n", 3, $_SERVER['DOCUMENT_ROOT'].'/log.log');

        if(empty($contact['ID'])) {
            $this->add($arFields);
        }
        elseif(!$delete) {
            $this->update($contact['ID'], $arFields);
        }
        else
            $this->delete($contact['ID']);

        return $this->result;    

    }

    /*
    * Добавление
    */
    protected function add($arFields) {  

        $arFields[Common::getOption('CONTACT_1C_URL')] = \Pixite\Crm\Helper::generateUrlFor1C($arFields[Common::getOption('CONTACT_GUID')], 'contact');

        if($this->contact->Add($arFields))
            $this->result->setData(['ID' => $contactId]);
        else
            $this->result->addError(new Error($this->contact->LAST_ERROR));       
        
    }

    /*
    * Обновление
    */
    public function update($contactId, $arFields) {

        $arFieldsMulti = \Pixite\Crm\Helper::getCrmFieldMulti($contactId, 'CONTACT');

        if(!empty($arFieldsMulti)) {
            foreach($arFields['FM'] as $key => $fields) {
                foreach($fields as $keyField => $field) {
                    foreach($arFieldsMulti as $fieldMulti) {
                        if($fieldMulti['TYPE_ID'] == $key && $fieldMulti['VALUE_TYPE'] == 'WORK') {
                            if($fieldMulti['VALUE'] == $field['VALUE'])
                                unset($arFields['FM'][$key][$keyField]);                           
                        }
                    }
                }
            }
        }

        if($this->contact->Update($contactId, $arFields))
            $this->result->setData(['ID' => $contactId]);  
        else
            $this->result->addError(new Error($this->contact->LAST_ERROR));

    }

    /*
    * Поиск
    */
    public function search($arFilter, $arSelect = ['*']) {

        $result = ContactTable::getList([
            'select' => $arSelect,
            'filter' => $arFilter
        ])->fetchAll();

        if(!is_array($result))
            return false;
        
        return $result;

    }

    /*
    * Удаление
    */
    protected function delete($contactId) {

        if($this->contact->Add($arFields)->Update($contactId, $arFields))
            $this->result->setData(['ID' => $contactId]);  
        else
            $this->result->addError(new Error($this->contact->Add($arFields)->LAST_ERROR));

    }

}