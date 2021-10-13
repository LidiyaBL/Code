<?
namespace Pixite\Crm;

use \Bitrix\Main\Config\Option;
use \Bitrix\Crm\EntityRequisite;
use \Pixite\Common;
use \Bitrix\Main\Result;
use \Bitrix\Main\Error;
use Bitrix\Crm\CompanyTable;

class Company extends \Pixite\CRMObject {

    /*
    * Загрузка компании
    */
    public function import($arFields, $delete) {

        if (array_key_exists('RQ_INN', $arFields)) {
            $this->importReq($arFields, $arFields['PARENT'], $delete);
        }
        else {
            $this->importCompany($arFields, $delete);
        }

        return $this->result;    
        
    }

    /*
    * Загрузка компании
    */
    protected function importCompany($arFields, $delete) {

        //бизнес-регион
        $regionCode = Common::getOption('COMPANY_REGION');
        if(!empty($arFields[$regionCode]) && $arFields[$regionCode] != '00000000-0000-0000-0000-000000000000') {
            $region = $this->getRegion(['XML_ID' => $arFields[$regionCode]], ['ID']);
            if(!empty($region['ID']))
                $arFields[$regionCode] = $region['ID'];
        }
        else 
            unset($arFields[$regionCode]);

        //Ответственный
        if(!empty($arFields['ASSIGNED_BY_ID']) && $arFields['ASSIGNED_BY_ID'] != '00000000-0000-0000-0000-000000000000') {
            $User = new \Pixite\Main\User;            
            $assigned = $User->searchUserByGUID($arFields['ASSIGNED_BY_ID']);
            if(!empty($assigned['ID']))
                $arFields['ASSIGNED_BY_ID'] = $assigned['ID'];
        }
        else 
            unset($arFields['ASSIGNED_BY_ID']);

        unset($arFields['XML_ID']);

        //Моя компания
        if($arFields['IS_MY_COMPANY'] == 'Y') {
            $arRequisite = $arFields['REQ'];
            unset($arFields['REQ']);
        }

        $company = $this->searchCompanyByGUID($arFields[Common::getOption('COMPANY_GUID')]);
        error_log("<pre>company" . date('d.m.Y H:i:s') . "\n" . print_r($company['ID'] ,true)."</pre>\n", 3, $_SERVER['DOCUMENT_ROOT'].'/log.log');

        if(empty($company)) {
            $this->add($arFields);
        }
        else {
            $this->update($company['ID'], $arFields);
        }

        if(!empty($arRequisite)) {
            $this->importReq($arRequisite, $arFields[Common::getOption('COMPANY_GUID')], false);
        }

        return $this->result;    
        
    }

    /*
    * Загрузка реквизитов компании
    */
    protected function importReq($arFields, $companyGuid, $delete) {

        unset($arFields['PARENT']);
        
        $company = $this->searchCompanyByGUID($companyGuid);
        error_log("<pre>company" . date('d.m.Y H:i:s') . "\n" . print_r($company['ID'] ,true)."</pre>\n", 3, $_SERVER['DOCUMENT_ROOT'].'/log.log');

        if(!empty($company)) {
            $arRequisites = $this->requisiteGetList($company['ID'], $arFields['PRESET_ID']);        

            if(!empty($arRequisites)) {
                //если реквизиты существуют то сначала их удаляем, а потом добавляем, иначе будет несколько инн-ов
                foreach ($arRequisites as $req) {
                    if($req['NAME'] == $arFields['NAME'] || (empty($req['RQ_INN']))) {                    
                        $requisite = new \Bitrix\Crm\EntityRequisite();
                        $requisite->deleteByEntity(\CCrmOwnerType::Company, $req['ENTITY_ID']);
                    }
                }
            }

            $arFields['ENTITY_ID'] = $company['ID'];
            $this->addReq($arFields);
        
        }
        else {
            $this->result->addError(new Error('Не найдена компания'));
        }

        return $this->result;          
        
    }

    /*
    * Поиск компании по GUID
    */
    public function searchCompanyByGUID($guid, $arSelect = ['ID']) {

        $dbRes = \CCrmCompany::GetListEx(
            ['ID' => 'ASC'], 
            [Common::getOption('COMPANY_GUID') => $guid, 'CHECK_PERMISSIONS' => 'N'],
            $arSelect
        );
        $result = $dbRes->Fetch();

        return $result;
        
    }

    /*
    * Добавление компании
    */
    public function add($arFields) {  
       
        $company = new \CCrmCompany(false);
        $companyId = $company->Add($arFields);
       
        if($companyId)
            $this->result->setData(['result' => $companyId]);
        else
            $this->result->addError(new Error($company->LAST_ERROR));
        
    }

    /*
    * Добавление реквизитов компании
    */
    public function addReq($arFields) {  
        
        $arFields['ENTITY_TYPE_ID'] = \CCrmOwnerType::Company;

        $address = $arFields['ADDRESS'];
        unset($arFields['ADDRESS']);

        $fm['FM'] = $arFields['COMPANY'];
        unset($arFields['COMPANY']);
        $this->update($arFields['ENTITY_ID'], $fm);
       
        $requisite = new \Bitrix\Crm\EntityRequisite();
        $this->result = $requisite->add($arFields);

        if($this->result->isSuccess() && $this->result->getId() > 0) {
            if(!empty($address))
                $this->addReqAddress($this->result->getId(), $address);
        }
        
    }

    /*
    * Обновление реквизитов компании
    */
    public function updateReq($id, $arFields) {  
       
        $requisite = new \Bitrix\Crm\EntityRequisite();
        $this->result = $requisite->update($id, $arFields);
       
    }

    /*
    * Обновление компании
    */
    public function update($companyId, $arFields) {
        
        $company = new \CCrmCompany(false);

        $arFieldsMulti = \Pixite\Crm\Helper::getCrmFieldMulti($companyId, 'COMPANY');

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

        if($company->Update($companyId, $arFields))
            $this->result->setData(['result' => true]);       
        else
            $this->result->addError(new Error($company->LAST_ERROR));
        
    }

    /*
    * Получение реквизитов компании
    */
    public function requisiteGetList($companyId, $presetId) {

        $requisite = new \Bitrix\Crm\EntityRequisite();
        $dbRes = $requisite->getList(['filter' => ['ENTITY_ID' => $companyId, 'PRESET_ID' => $presetId, 'ENTITY_TYPE_ID' => \CCrmOwnerType::Company]]);
        while($req = $dbRes->fetch()) {
            $arRequisite[] = $req;
        }

        return $arRequisite;

    }

    /*
    * Добавление адреса компании
    */
    public function addReqAddress($reqId, $arFields) {

        $address = new \Bitrix\Crm\EntityAddress();
        $address->register(
            8, 
            $reqId, 
            6,
            $arFields
        );

    }

    /*
    * Поиск компании
    */
    public function search($arFilter, $arSelect = []) {

        $result = CompanyTable::getList([
            'select' => $arSelect,
            'filter' => $arFilter
        ])->fetchAll();

        if(!is_array($result))
            return false;
        
        return $result;

    }

    /*
    * Поиск компании
    */
    protected function delete($id) {


    }

    /*
    * Получение реквизитов компании
    */
    public function requisiteGetListByXmlID($companyId, $xmlId = false) {

        $result = [];
        $requisite = new \Bitrix\Crm\EntityRequisite();
        $dbRes = $requisite->getList(['filter' => ['ENTITY_ID' => $companyId, 'ENTITY_TYPE_ID' => \CCrmOwnerType::Company]]);
        while($req = $dbRes->fetch()) {
            $result[] = $req;
        }

        return $result[0];

    }

    /*
    * Получение списка моих компаний
    */
    public function getOrganizations() {

        $cache_id = 'TEKO.CRM.MYCOMPANY';
        $arItems = [];
        $obCache = new \CPHPCache();
        if ($obCache->InitCache(3600, $cache_id . 'crm', '/crm/company')){
            $arItems = $obCache->GetVars();
        }
        elseif ($obCache->StartDataCache())	{
            $arCompanies = Company::search(['IS_MY_COMPANY' => 'Y'], ['ID', Common::getOption('COMPANY_GUID')]);           
            foreach($arCompanies as $company) {
                $arItems[$company[Common::getOption('COMPANY_GUID')]] = $company['ID'];
            }
            $obCache->EndDataCache($arItems);
        }
        
        return $arItems;

    }

    /*
    * Получение данных моих компаний
    */
    public function getOrganizationData($arFilter = [], $arSelect = ['*']) {

        $arFilter['IS_MY_COMPANY'] = 'Y';
        $arCompanies = Company::search($arFilter, $arSelect);           
        foreach($arCompanies as $company) {
            $arItems[$company['ID']] = $company;
        }
        
        return $arItems;

    }

    /*
    * Получение типов компании
    */
    public function mappingTypeBy1C($typeBX = false, $type1C = false) {

        $arType = [
            '4' => 'Перевозчик',
            '3' => 'ПрочиеОтношения',
            'COMPETITOR' => 'Конкурент',
            'SUPPLIER' => 'Поставщик',
            'CUSTOMER' => 'Клиент',
        ];

        if($typeBX && !empty($arType[$typeBX]))
            return $arType[$typeBX];

        if($type1C && $key = array_search($type1C, $arType))
            return $key;

        return false;

    }

    /*
    * Получение данных региона
    */
    public function getRegion($arFilter, $arSelect) {

        $List = new \Pixite\Directory\Lists;      
        $List->setIblockId(Option::get(SITE_MODULE, 'PX_IBLOCK_REGION'));      
        $region = $List->search($arFilter, $arSelect);
        if(!empty($region))
            return $region;

        return false;       

    }

    /*
    * Проверка выгрузки компании в 1С
    */
    public function checkExport1C($id) {

        $company = current(Company::search(['ID' => $id], ['ID', Common::getOption('COMPANY_EXPORT_IN_1С')]));  
        
        return (!empty($company[Common::getOption('COMPANY_EXPORT_IN_1С')]) ? true : false);       

    }

}