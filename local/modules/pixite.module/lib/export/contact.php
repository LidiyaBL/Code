<?
namespace Pixite\Export;

use \Pixite\Common;

class Contact extends \Pixite\Export {

    protected $element;
    protected $fields;   
       
    public function export($id = false) {
        
        $this->setExportElement(['ID' => $id]);
        $this->setFields();
        $data = $this->getData();

error_log("<pre>result".print_r($data,true)."</pre>\n", 3, $_SERVER['DOCUMENT_ROOT'].'/log.log');
        // if($data)
        //     \Pixite\Exchange\RabbitMQExport::sendMessage('contact', $data);

    }

    protected function setExportElement(array $params) {

        if(!empty($params['ID'])) {
            $this->element['ID'] = $params['ID'];
        }

    }

    protected function getData() {        
        
        $this->getContact();
        $this->searchUser();
        $result = $this->buildingData();

        return $result;
       
      
        return false;
    }

    protected function setFields() {

        $this->fields = [
            'ID', 'LAST_NAME', 'NAME', 'SECOND_NAME', 'POST', 'ADDRESS', 'TYPE_ID', 'ASSIGNED_BY_ID', 'CREATED_BY_ID', 'BIRTHDATE', 'COMPANY_ID',
            Common::getOption('CONTACT_GUID'),
            Common::getOption('CONTACT_ROLE'),
            Common::getOption('CONTACT_DEPARTAMENT'),
            Common::getOption('CONTACT_DEPARTAMENT_LIST'),
        ];

    }

    protected function buildingData() {   
        
        $element = $this->element;

        $arExportFields = [
            'XML_ID' => $element[Common::getOption('CONTACT_GUID')],
            'LAST_NAME' => $element['LAST_NAME'],
            'NAME' => $element['NAME'],
            'SECOND_NAME' => $element['SECOND_NAME'],
            'POST' => $element['POST'],
            'ADDRESS' => $element['ADDRESS'],
            'TYPE' => $element['TYPE_ID'],
            'ASSIGNED' => $element['ASSIGNED_BY_ID'],
            'BIRTHDATE' => $element['BIRTHDATE'],
            'ROLE' => $element[Common::getOption('CONTACT_ROLE')],
            'DEPARTAMENT' => $element[Common::getOption('CONTACT_DEPARTAMENT')],
            'COMPANY' => $element['COMPANY_ID'],
            'CONTACT_INFORMATION' => $element['CONTACT_INFORMATION'],             
        ];

        return $arExportFields;
       
    }

    protected function getContact() {     

        $contact = current(\Pixite\Crm\Contact::search(['ID' => $this->element['ID']], $this->fields));

        if(!empty($contact['BIRTHDATE']))
            $contact['BIRTHDATE'] = $contact['BIRTHDATE']->toString();

        if(strlen($contact[Common::getOption('CONTACT_DEPARTAMENT')]) <= 0)
            $contact[Common::getOption('CONTACT_DEPARTAMENT')] = $contact[Common::getOption('CONTACT_DEPARTAMENT_LIST')];

        if(!empty($contact['COMPANY_ID'])) {
            $company = current(\Pixite\Crm\Company::search(['ID' => $contact['COMPANY_ID']], ['ID', Common::getOption('COMPANY_GUID')]));
            $contact['COMPANY_ID'] = $company[Common::getOption('COMPANY_GUID')];               
        }

        $arFieldsMulti = \Pixite\Crm\Helper::getCrmFieldMulti($this->element['ID'], 'CONTACT');
        foreach ($arFieldsMulti as $key => $field) {
            $contact['CONTACT_INFORMATION'][] = [
                'TYPE' => $field['TYPE_ID'],
                'VALUE_TYPE' => $field['VALUE_TYPE'],
                'VALUE' => $field['VALUE'],
            ];                
        }

        $this->element = $contact;      
       
    }

   
    protected function searchUser() { 
            
        if(!empty($this->element['ASSIGNED_BY_ID']))        
            $arUsers[] = $this->element['ASSIGNED_BY_ID'];
        if(!empty($this->element['CREATED_BY_ID']))        
            $arUsers[] = $this->element['CREATED_BY_ID'];
        $arUsers = array_unique($arUsers); 

        $users = \Pixite\Main\User::search(['ID' => $arUsers], ['ID', 'XML_ID']);
        foreach ($users as $value) {
            if($this->element['ASSIGNED_BY_ID'] == $users['ID'])        
                $this->element['ASSIGNED_BY_ID'] = $users['XML_ID'];
            if($this->element['CREATED_BY_ID'] == $users['ID'])        
                $this->element['CREATED_BY_ID'] = $users['XML_ID'];
        }        
       
    }
     

     
    
}