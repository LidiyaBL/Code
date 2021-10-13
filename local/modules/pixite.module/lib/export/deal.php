<?
namespace Pixite\Export;

use \Pixite\Common;

class Deal extends \Pixite\Export {

    protected $element;
    protected $fields;
   
    public function export($id = false) {
        
        $this->setExportElement(['ID' => $id]);
        $this->setFields();
        $data = $this->getData();

error_log("<pre>result".print_r($data,true)."</pre>\n", 3, $_SERVER['DOCUMENT_ROOT'].'/log.log');
        // if($data)
        //     \Pixite\Exchange\RabbitMQExport::sendMessage('deal', $data);

    }

    protected function setExportElement(array $params) {

        if(!empty($params['ID'])) {
            $this->element['ID'] = $params['ID'];
        }

    }

    protected function getData() {        
        
        $check = $this->getDeal();

        if($check) {
            if(!$this->getDealProducts())
                return false;
            $this->getDealContact();
            $this->getDealCompany();
            $this->searchUser();
            $result = $this->buildingData();
    
            return $result;
        }
      
        return false;
    }

    protected function setFields() {

        $this->fields = [
            'DEAL' => [
                'ID', 'TITLE', 'OPPORTUNITY', 'CURRENCY_ID', 'STAGE_ID', 'CLOSED', 'BEGINDATE', 
                'CLOSEDATE', 'ASSIGNED_BY_ID', 'CREATED_BY_ID', 'CONTACT_ID', 'COMPANY_ID', 'CATEGORY_ID',
                Common::getOption('DEAL_GUID'),
                Common::getOption('DEAL_MY_COMPANY'),
                Common::getOption('DEAL_CONTRACT'),  
                Common::getOption('DEAL_EXPORT_IN_1С'),  
            ],
            'CONTACT' => [
                'ID', 'LAST_NAME', 'NAME', 'SECOND_NAME', 'POST', 'ADDRESS', 'TYPE_ID', 'ASSIGNED_BY_ID', 'CREATED_BY_ID', 'BIRTHDATE', 'COMPANY_ID',
                Common::getOption('CONTACT_GUID'),
                Common::getOption('CONTACT_ROLE'),
                Common::getOption('CONTACT_DEPARTAMENT'),
                Common::getOption('CONTACT_DEPARTAMENT_LIST'),
            ],
            'COMPANY' => [
                'ID', 'TITLE', 'COMPANY_TYPE', 'COMMENTS', 'ADDRESS', 'ADDRESS_LEGAL', 'ASSIGNED_BY_ID', 'CREATED_BY_ID',
                Common::getOption('COMPANY_GUID'),
                Common::getOption('COMPANY_CODE'),
                Common::getOption('COMPANY_REGION'),
            ]
        ];

    }

    protected function buildingData() {   
        
        $element = $this->element;

        $arExportFields = [
            'XML_ID' => $element['DEAL'][Common::getOption('DEAL_GUID')],
            'TITLE' => $element['DEAL']['TITLE'],
            'SUM' => $element['DEAL']['OPPORTUNITY'],
            'CURRENCY' => $element['DEAL']['CURRENCY_ID'],
            'STAGE' => $element['DEAL']['STAGE_ID'],
            'CLOSED' => $element['DEAL']['CLOSED'],
            'BEGINDATE' => $element['DEAL']['BEGINDATE'],
            'CLOSEDATE' => $element['DEAL']['CLOSEDATE'],
            'ASSIGNED' => $element['DEAL']['ASSIGNED_BY_ID'],
            'CREATED' => $element['DEAL']['CREATED_BY_ID'],
            'CATEGORY' => $element['DEAL']['CATEGORY_ID'],
            'ORGANIZATION' => $element['DEAL'][Common::getOption('DEAL_MY_COMPANY')],
            'CONTRACT' => $element['DEAL'][Common::getOption('DEAL_CONTRACT')],
            'PRODUCTS' => $element['PRODUCTS'],
            'CONTACT' => [
                'XML_ID' => $element['CONTACT'][Common::getOption('CONTACT_GUID')],
                'LAST_NAME' => $element['CONTACT']['LAST_NAME'],
                'NAME' => $element['CONTACT']['NAME'],
                'SECOND_NAME' => $element['CONTACT']['SECOND_NAME'],
                'POST' => $element['CONTACT']['POST'],
                'ADDRESS' => $element['CONTACT']['ADDRESS'],
                'TYPE' => $element['CONTACT']['TYPE_ID'],
                'ASSIGNED' => $element['CONTACT']['ASSIGNED_BY_ID'],
                'BIRTHDATE' => $element['CONTACT']['BIRTHDATE'],
                'ROLE' => $element['CONTACT'][Common::getOption('CONTACT_ROLE')],
                'DEPARTAMENT' => $element['CONTACT'][Common::getOption('CONTACT_DEPARTAMENT')],
                'COMPANY' => $element['CONTACT']['COMPANY_ID'],
                'CONTACT_INFORMATION' => $element['CONTACT']['CONTACT_INFORMATION'],
            ],
            'COMPANY' => [
                'XML_ID' => $element['COMPANY'][Common::getOption('COMPANY_GUID')],                
                'TITLE' => $element['COMPANY']['TITLE'],
                'TYPE' => $element['COMPANY']['COMPANY_TYPE'],
                'COMMENTS' => $element['COMPANY']['COMMENTS'],
                'ADDRESS' => $element['COMPANY']['ADDRESS'],
                'ADDRESS_LEGAL' => $element['COMPANY']['ADDRESS_LEGAL'],
                'ASSIGNED' => $element['COMPANY']['ASSIGNED_BY_ID'],
                'CREATED' => $element['COMPANY']['CREATED_BY_ID'],
                'CODE' => $element['COMPANY'][Common::getOption('COMPANY_CODE')],
                'REGION' => $element['COMPANY'][Common::getOption('COMPANY_REGION')],
                'COMPANY_INFORMATION' => $element['COMPANY']['COMPANY_INFORMATION'],
            ],
            'COMPANY_REQUISITE' => $element['COMPANY_REQUISITE']   
        ];

        return $arExportFields;
       
    }

    protected function getDeal() {        

        $deal = current(\Pixite\Crm\Deal::search(['ID' => $this->element['ID']], $this->fields['DEAL']));
error_log("<pre>deal".print_r($deal,true)."</pre>\n", 3, $_SERVER['DOCUMENT_ROOT'].'/log.log');
        $statusExport = Common::getOption('DEAL_STAGES_FOR_EXPORT');
        $categoryExport = Common::getOption('DEAL_CATEGORY_FOR_EXPORT');
 
        if($deal['ID'] <= 1843)
            return false;

        if(intval($deal[Common::getOption('DEAL_EXPORT_IN_1С')]) > 0)
            return false;

        // if(!in_array($deal['STAGE_ID'], $statusExport))
        //     return false;

        // if(!in_array($deal['CATEGORY_ID'], $categoryExport))
        //     return false;

        $deal['STAGE_ID'] = $this->getStage1C($deal['STAGE_ID']);

        if(!empty($deal['BEGINDATE']))
            $deal['BEGINDATE'] = $deal['BEGINDATE']->toString();

        if(!empty($deal['CLOSEDATE']))
            $deal['CLOSEDATE'] = $deal['CLOSEDATE']->toString();

        // if(!empty($deal['CONTACT_ID'])) {
        //     $contact = current(\Pixite\Crm\Contact::search(['ID' => $deal['CONTACT_ID']], ['ID', Common::getOption('CONTACT_GUID')]));
        //     //$deal['CONTACT_ID'] = $contact[Common::getOption('CONTACT_GUID')];
        //     $this->element['CONTACT_ID'] = $contact['ID'];
        // }

        $dealCategory = \Pixite\Crm\Deal::getCategory();
        $deal['CATEGORY_ID'] = $dealCategory[$deal['CATEGORY_ID']];
        
        if(!empty($deal[Common::getOption('DEAL_MY_COMPANY')])) {
            $myCompanies = \Pixite\Crm\Company::getOrganizations();
            $deal[Common::getOption('DEAL_MY_COMPANY')] = array_search($deal[Common::getOption('DEAL_MY_COMPANY')], $myCompanies);
        }

        if(!empty($deal[Common::getOption('DEAL_CONTRACT')])) {
            $Contract = new \Pixite\Crm\Contract;
            $Contract->setIblockId(\Bitrix\Main\Config\Option::get(SITE_MODULE, 'PX_IBLOCK_CONTRACT'));            
            $contract = current($Contract->search(['ID' => $deal[Common::getOption('DEAL_CONTRACT')]], ['ID', 'XML_ID']));    
            $deal[Common::getOption('DEAL_CONTRACT')] = $contract['XML_ID'];            
        }

        // $Deal = new \Pixite\Crm\Deal;
        // $res = $Deal->update($deal['ID'], [Common::getOption('DEAL_EXPORT_IN_1С') => true]);

        unset($deal['ID']);
        $this->element['DEAL'] = $deal;

        return true;
       
    }
    
    protected function getDealProducts() {

        $arProducts = \CCrmDeal::LoadProductRows($this->element['ID']);

        foreach ($arProducts as $key => $product) {
            foreach ($product as $keyField => $field) {
                switch ($keyField) {
                    case 'ID':
                    case 'OWNER_ID':
                    case 'OWNER_TYPE':
                    case 'ORIGINAL_PRODUCT_NAME':
                    case 'PRODUCT_DESCRIPTION':
                    case 'PRODUCT_DESCRIPTION':
                    case 'PRICE_EXCLUSIVE':
                    case 'PRICE_ACCOUNT':
                    case 'DISCOUNT_TYPE_ID':
                    case 'CUSTOMIZED':
                    case 'SORT':
                        unset($arProducts[$key][$keyField]);
                    case 'PRODUCT_ID':
                        ////
                        $elements = \Bitrix\Iblock\Elements\ElementCrmCatalogTable::getList([
                            'select' => ['ID', 'XML_ID'],
                            'filter' => [
                                'IBLOCK_ID' => CATALOG,
                                'ACTIVE' => 'Y',
                                'ID' => $field
                            ],        
                        ])->fetchCollection();
                        foreach($elements as $element) {
                            $arProducts[$key]['XML_ID'] = $element->getXmlID();
                        }                        
                        ////
                        unset($arProducts[$key][$keyField]);
                        break;                    
                }
            }
        }

        foreach ($arProducts as $key => $product) {
            if($product['PRICE'] == 0 || $product['QUANTITY'] == 0) {
                return false;
            }
        }

        $this->element['PRODUCTS'] = $arProducts;

        return true;
        
    }   

    protected function getStage1C($stageId) {        

        $arStage = [
            5 => 'КОбеспечению' //Производство
        ];

        return $arStage[$stageId];
       
    }

    protected function getDealContact() {     

        if(!empty($this->element['DEAL']['CONTACT_ID'])) {
            $contact = current(\Pixite\Crm\Contact::search(['ID' => $this->element['DEAL']['CONTACT_ID']], $this->fields['CONTACT']));

            if(!empty($contact['BIRTHDATE']))
                $contact['BIRTHDATE'] = $contact['BIRTHDATE']->toString();

            if(strlen($contact[Common::getOption('CONTACT_DEPARTAMENT')]) <= 0)
                $contact[Common::getOption('CONTACT_DEPARTAMENT')] = $contact[Common::getOption('CONTACT_DEPARTAMENT_LIST')];

            if(!empty($contact['COMPANY_ID'])) {
                $company = current(\Pixite\Crm\Company::search(['ID' => $contact['COMPANY_ID']], ['ID', Common::getOption('COMPANY_GUID')]));
                $contact['COMPANY_ID'] = $company[Common::getOption('COMPANY_GUID')];               
            }

            $arFieldsMulti = \Pixite\Crm\Helper::getCrmFieldMulti($this->element['DEAL']['CONTACT_ID'], 'CONTACT');
            foreach ($arFieldsMulti as $key => $field) {
                $contact['CONTACT_INFORMATION'][] = [
                    'TYPE' => $field['TYPE_ID'],
                    'VALUE_TYPE' => $field['VALUE_TYPE'],
                    'VALUE' => $field['VALUE'],
                ];                
            }

            $this->element['CONTACT'] = $contact;
        }
        
       
    }

    protected function getDealCompany() {     
        
        if(!empty($this->element['DEAL']['COMPANY_ID'])) {

            $company = current(\Pixite\Crm\Company::search(['ID' => $this->element['DEAL']['COMPANY_ID']], $this->fields['COMPANY']));
            //$this->element['DEAL']['COMPANY_ID'] = $company[Common::getOption('COMPANY_GUID')];
            $companyReq = \Pixite\Crm\Company::requisiteGetListByXmlID($company['ID']);
            $arReq = [];
            $arReq['COMPANY'] = $company[Common::getOption('COMPANY_GUID')];           
            foreach($companyReq as $codeReq => $req) {
                switch ($codeReq) {
                    case 'PRESET_ID':
                        if($req == 1)
                            $arReq['PRESET'] = 'ЮрЛицо';
                        if($req == 2)
                            $arReq['PRESET'] = 'ИндивидуальныйПредприниматель';
                        if($req == 4)
                            $arReq['PRESET'] = 'ЮрЛицоНеРезидент';
                        break;
                    case 'NAME':
                    case 'XML_ID':
                    case 'RQ_NAME':
                    case 'RQ_FIRST_NAME':
                    case 'RQ_LAST_NAME':
                    case 'RQ_SECOND_NAME':
                    case 'RQ_COMPANY_NAME':
                    case 'RQ_COMPANY_FULL_NAME':
                    case 'RQ_COMPANY_REG_DATE':
                    case 'RQ_DIRECTOR':
                    case 'RQ_ACCOUNTANT':
                    case 'RQ_CONTACT':
                    case 'RQ_EMAIL':
                    case 'RQ_PHONE':
                    case 'RQ_FAX':
                    case 'RQ_INN':
                    case 'RQ_KPP':
                    case 'RQ_OGRN':
                    case 'RQ_OGRNIP':
                    case 'RQ_OKPO':
                    case 'RQ_OKTMO':
                    case 'RQ_OKVED':
                    case 'RQ_EDRPOU':
                    case 'RQ_DRFO':
                    case 'RQ_KBE':
                    case 'RQ_IIN':
                    case 'RQ_BIN':
                        $arReq[$codeReq] = $req;
                        break;
                }
            }

            if(strlen($arReq['XML_ID']) <= 0){
                $guid = \Pixite\Common::guidv4();
                $arReq['XML_ID'] = $guid;      
                $obCompany = new \Pixite\Crm\Company;  
                $obCompany->updateReq($companyReq['ID'], ['XML_ID' => $guid]);   
            }

            if(count($arReq) > 0) {
                $this->element['COMPANY_REQUISITE'] = $arReq;
            }

            if(!empty($company['COMPANY_TYPE'])) {
                if($company1CType = \Pixite\Crm\Company::mappingTypeBy1C($company['COMPANY_TYPE'], false))
                    $company['COMPANY_TYPE'] = $company1CType;	
            }  

            if(!empty($company[Common::getOption('COMPANY_REGION')])) {
                $region = current(\Pixite\Crm\Company::getRegion(['ID' => $company[Common::getOption('COMPANY_REGION')]], ['ID', 'XML_ID']));
                if(!empty($region['ID']))
                    $company[Common::getOption('COMPANY_REGION')] = $region['XML_ID'];
            } 
            
            $arFieldsMulti = \Pixite\Crm\Helper::getCrmFieldMulti($company['ID'], 'COMPANY');
            foreach ($arFieldsMulti as $key => $field) {
                $company['COMPANY_INFORMATION'][] = [
                    'TYPE' => $field['TYPE_ID'],
                    'VALUE_TYPE' => $field['VALUE_TYPE'],
                    'VALUE' => $field['VALUE'],
                ];                
            }

            $this->element['COMPANY'] = $company; 
           
        }
        
       
    }

    protected function searchUser() { 
        
        if(!empty($this->element['COMPANY']['ASSIGNED_BY_ID']))        
            $arUsers[] = $this->element['COMPANY']['ASSIGNED_BY_ID'];
        if(!empty($this->element['COMPANY']['CREATED_BY_ID']))        
            $arUsers[] = $this->element['COMPANY']['CREATED_BY_ID'];
        if(!empty($this->element['CONTACT']['ASSIGNED_BY_ID']))        
            $arUsers[] = $this->element['CONTACT']['ASSIGNED_BY_ID'];
        if(!empty($this->element['CONTACT']['CREATED_BY_ID']))        
            $arUsers[] = $this->element['CONTACT']['CREATED_BY_ID'];
        if(!empty($this->element['DEAL']['ASSIGNED_BY_ID']))        
            $arUsers[] = $this->element['DEAL']['ASSIGNED_BY_ID'];
        if(!empty($this->element['DEAL']['CREATED_BY_ID']))        
            $arUsers[] = $this->element['DEAL']['CREATED_BY_ID'];
        $arUsers = array_unique($arUsers); 

        $users = \Pixite\Main\User::search(['ID' => $arUsers], ['ID', 'XML_ID']);
        foreach ($users as $value) {
            if($this->element['COMPANY']['ASSIGNED_BY_ID'] == $users['ID'])        
                $this->element['COMPANY']['ASSIGNED_BY_ID'] = $users['XML_ID'];
            if($this->element['COMPANY']['CREATED_BY_ID'] == $users['ID'])        
                $this->element['COMPANY']['CREATED_BY_ID'] = $users['XML_ID'];
            if($this->element['CONTACT']['ASSIGNED_BY_ID'] == $users['ID'])        
                $this->element['CONTACT']['ASSIGNED_BY_ID'] = $users['XML_ID'];
            if($this->element['CONTACT']['CREATED_BY_ID'] == $users['ID'])        
                $this->element['CONTACT']['CREATED_BY_ID'] = $users['XML_ID'];
            if($this->element['DEAL']['ASSIGNED_BY_ID'] == $users['ID'])        
                $this->element['DEAL']['ASSIGNED_BY_ID'] = $users['XML_ID'];
            if($this->element['DEAL']['CREATED_BY_ID'] == $users['ID'])        
                $this->element['DEAL']['CREATED_BY_ID'] = $users['XML_ID'];
        }        
       
    }
     

     
    
}