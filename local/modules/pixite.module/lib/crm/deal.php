<?
namespace Pixite\Crm;

use \Bitrix\Main\Result;
use \Bitrix\Main\Error;
use \Bitrix\Main\Type\Date;
use \Bitrix\Main\Type\DateTime;
use \Bitrix\Main\Config\Option;
use \Bitrix\Crm\DealTable;
use \Pixite\Common;
use \Pixite\Crm\Company;

class Deal extends \Pixite\CRMObject {

    /*
    * Загрузка
    */
    public function import($arFields, $delete) {

        if($arFields['BEGINDATE'] !== '0001-01-01T00:00:00') {
            $beginDate = Date::createFromPhp(new \DateTime($arFields['BEGINDATE']));
            $arFields['BEGINDATE'] = $beginDate->format('d.m.Y');
        }
        else {
            unset($arFields['BEGINDATE']);
        } 
        
        if(!empty($arFields['COMPANY_ID'])) {
            $company = Company::searchCompanyByGUID($arFields['COMPANY_ID'], ['ID']);
            if(!empty($company['ID']))
                $arFields['COMPANY_ID'] = $company['ID'];
            else
                unset($arFields['COMPANY_ID']);
        }

        if(!empty($arFields['CONTACT_IDS'])) {
            $contact = current(\Pixite\Crm\Contact::search([Common::getOption('CONTACT_GUID') => $arFields['CONTACT_IDS']], ['ID']));
            if(!empty($contact['ID']))
                $arFields['CONTACT_IDS'] = [$contact['ID']];
            else
                unset($arFields['CONTACT_IDS']);
        }

        if(!empty($arFields[Common::getOption('DEAL_COMPANY_REQ')]) && $company['ID']) {
            $companyReq = Company::requisiteGetListByXmlID($company['ID'], $arFields[Common::getOption('DEAL_COMPANY_REQ')]);
            if($companyReq) {
                $arFields[Common::getOption('DEAL_COMPANY_REQ')] = (strlen($companyReq['RQ_INN']) > 0 ? $companyReq['RQ_INN'] : $companyReq['NAME']);
            }                
            else
                unset($arFields[Common::getOption('DEAL_COMPANY_REQ')]);
        }

        if(!empty($arFields[Common::getOption('DEAL_MY_COMPANY')])) {
            $myCompanies = Company::getOrganizations();
            if(!empty($myCompanies[$arFields[Common::getOption('DEAL_MY_COMPANY')]]))
                $arFields[Common::getOption('DEAL_MY_COMPANY')] = 'CO_' . $myCompanies[$arFields[Common::getOption('DEAL_MY_COMPANY')]];
            else
                unset($arFields[Common::getOption('DEAL_MY_COMPANY')]);            
        }

        //Ответственный
        if(!empty($arFields['ASSIGNED_BY_ID']) && $arFields['ASSIGNED_BY_ID'] != '00000000-0000-0000-0000-000000000000') {
            $User = new \Pixite\Main\User;            
            $assigned = $User->searchUserByGUID($arFields['ASSIGNED_BY_ID']);
            if(!empty($assigned['ID'])) {
                $arFields['ASSIGNED_BY_ID'] = $assigned['ID'];
                $arFields['CREATED_BY_ID'] = $assigned['ID'];
            }
            else {
                unset($arFields['ASSIGNED_BY_ID']);
                unset($arFields['CREATED_BY_ID']);
            }                
        }
        else {
            unset($arFields['ASSIGNED_BY_ID']);
            unset($arFields['CREATED_BY_ID']);
        }            
        
        if(!empty($arFields[Common::getOption('DEAL_CONTRACT')])) {
            $Contract = new \Pixite\Crm\Contract;
            $Contract->setIblockId(Option::get(SITE_MODULE, 'PX_IBLOCK_CONTRACT'));            
            $contract = current($Contract->search(['XML_ID' => $arFields[Common::getOption('DEAL_CONTRACT')]], ['ID']));    
   
            if(!empty($contract))
                $arFields[Common::getOption('DEAL_CONTRACT')] = $contract['ID'];
            else
                $arFields[Common::getOption('DEAL_CONTRACT')] = 0;            
        }

        if(!empty($arFields[Common::getOption('DEAL_ASSIGNED_UNIT')])) {
            $CompanyStructure = new  \Pixite\Main\CompanyStructure;           
            $structure = $CompanyStructure->getStructure();    
   
            if(!empty($structure[$arFields[Common::getOption('DEAL_ASSIGNED_UNIT')]]))
                $arFields[Common::getOption('DEAL_ASSIGNED_UNIT')] = $structure[$arFields[Common::getOption('DEAL_ASSIGNED_UNIT')]];
            else
                unset($arFields[Common::getOption('DEAL_ASSIGNED_UNIT')]);            
        }   

        if(!empty($arFields['STAGE_ID']) && !empty($stageId = $this->getStageId($arFields['STAGE_ID']))) {
            $arFields['STAGE_ID'] = $stageId;           
        }   

        //TODO Форма длговора - обязателен для некоторых стадий. Убрать???
        $arFields['UF_CRM_1625643130'] = 202;

        $arFields['TYPE_ID'] = 'SALE';//Тип сделки
        
        if(intval($arFields['OPPORTUNITY']) <= 0)
            unset($arFields['OPPORTUNITY']);

        error_log("<pre>arFields" . date('d.m.Y H:i:s') . "\n" . print_r($arFields ,true)."</pre>\n", 3, $_SERVER['DOCUMENT_ROOT'].'/log.log');

        $arProducts = $arFields['PRODUCTS'];
        unset($arFields['PRODUCTS']);
        
        $arInvoice = $arFields['INVOICE'];       
        unset($arFields['INVOICE']);

        $comment = $arFields['COMMENT'];       
        unset($arFields['COMMENT']);

        $deal = current($this->search([Common::getOption('DEAL_GUID') => $arFields[Common::getOption('DEAL_GUID')]], ['ID']));
        error_log("<pre>deal" . date('d.m.Y H:i:s') . "\n" . print_r($deal['ID'] ,true)."</pre>\n", 3, $_SERVER['DOCUMENT_ROOT'].'/log.log');
        
        if(empty($deal['ID'])) {
            //TODO сделки будут создаваться только в битриксе, из 1С загружаем только изменения
            //$this->add($arFields);
        }
        else {
            $this->update($deal['ID'], $arFields);
        }

        if($this->result->isSuccess()) {
            $data = $this->result->getData();
            if(is_array($arProducts))
                $this->importProducts($data['ID'], $arProducts);
            if(!empty($arInvoice))
                $this->importInvoice($data['ID'], $arInvoice, $arFields, $companyReq);
            if(!empty($comment))
                $this->importComment($data['ID'], $comment);

            if(!empty($deal['ID'])) {
                $Payment = new \Pixite\Crm\PaymentInvoice;
				$Payment->setTableName(new \Pixite\Internals\PaymentInvoiceTable);
                $Payment->calculationPayment($arFields[Common::getOption('DEAL_GUID')]);
            }
        }

        return $this->result;    

    }

    /*
    * Добавление
    */
    protected function add($arFields) {  

        $deal = new \CCrmDeal(false);
        $dealId = $deal->Add($arFields);

        $arFields[Common::getOption('DEAL_1C_URL')] = \Pixite\Crm\Helper::generateUrlFor1C($arFields[Common::getOption('DEAL_GUID')], 'deal');
       
        if($dealId)
            $this->result->setData(['ID' => $dealId]);
        else
            $this->result->addError(new Error($deal->LAST_ERROR));       
        
    }

    /*
    * Обновление
    */
    public function update($dealId, $arFields) {

        $deal = new \CCrmDeal(false);

        if($deal->Update($dealId, $arFields, true, true, ['DISABLE_USER_FIELD_CHECK' => true]))
            $this->result->setData(['ID' => $dealId]);  
        else
            $this->result->addError(new Error($deal->LAST_ERROR));

    }


    /*
    * Поиск
    */
    public function search($arFilter, $arSelect) {

        $result = DealTable::getList([
            'select' => $arSelect,
            'filter' => $arFilter
        ])->fetchAll();

        if(!is_array($result))
            return false;
        
        return $result;

    }

    /*
    * Поиск
    */
    protected function delete($id) {


    }

    /*
    * Импорт товаров
    */
    protected function importProducts($dealId, $arProducts) {  
        
        foreach ($arProducts as &$product) {
            $arXmlId[] = $product['PRODUCT_ID'];

            $vat = $product['TAX_RATE']; 
            if($vat == 'НДС20') {
                $product['TAX_RATE'] = 20;
                $product['TAX_INCLUDED'] = 'N';
                //$product['PRICE_EXCLUSIVE'] = $product['PRICE'];
                //$product['PRICE'] = $product['PRICE'] * 1.2;
            }
            if($vat == 'НДС0') {
                $product['TAX_RATE'] = 0;
                $product['TAX_INCLUDED'] = 'N';
                //$product['PRICE_EXCLUSIVE'] = $product['PRICE'];
            }
            if($vat == 'НДС18') {
                $product['TAX_RATE'] = 18;
                $product['TAX_INCLUDED'] = 'N';
                //$product['PRICE_EXCLUSIVE'] = $product['PRICE'];
            }

            if($product['DISCOUNT_RATE'] > 0) {
                $product['PRICE_EXCLUSIVE'] = $product['PRICE'] - ($product['PRICE'] * $product['DISCOUNT_RATE'])/100;
                $product['PRICE'] = $product['PRICE'] - ($product['PRICE'] * $product['DISCOUNT_RATE'])/100;
                //$product['DISCOUNT_SUM'] = $product['DISCOUNT_SUM']/$product['QUANTITY'];
                // $product['PRICE_NETTO'] = $product['PRICE'];
                // $product['PRICE'] = $product['PRICE'] - $product['DISCOUNT_SUM'];
                
                
            }
            $product['PRICE_EXCLUSIVE'] = $product['PRICE'];
            $product['DISCOUNT_TYPE_ID'] = 2;

        }
        unset($product);

        ////
        $elements = \Bitrix\Iblock\Elements\ElementCrmCatalogTable::getList([
            'select' => ['ID', 'XML_ID'],
            'filter' => [
                'IBLOCK_ID' => CATALOG,
                'ACTIVE' => 'Y',
                'XML_ID' => $arXmlId
            ],        
        ])->fetchCollection();
        foreach($elements as $element) {
            foreach ($arProducts as $key => $product) {
                if($product['PRODUCT_ID'] == $element->getXmlID())
                    $arProducts[$key]['PRODUCT_ID'] = $element->getId();
            }
        }
        
        ////
        \CCrmDeal::SaveProductRows($dealId, $arProducts, $checkPerms = true, $regEvent = true, $syncOwner = true);
    
    }
    
    /*
    * Импорт счетов
    */
    protected function importInvoice($dealId, $arInvoice, $arFields, $companyReq) {

        $Invoice = new \Pixite\Crm\Invoice;

        foreach ($arInvoice as $key => $invoice) {

            if(!empty($arFields['ASSIGNED_BY_ID']))
                $invoice['RESPONSIBLE_ID'] = $arFields['ASSIGNED_BY_ID'];
            if(!empty($arFields['COMPANY_ID']))
                $invoice['UF_COMPANY_ID'] = $arFields['COMPANY_ID'];
            if(!empty($arFields[Common::getOption('DEAL_MY_COMPANY')]))
                $invoice['UF_MYCOMPANY_ID'] = intval(str_replace('CO_', '', $arFields[Common::getOption('DEAL_MY_COMPANY')]));
            $invoice['UF_DEAL_ID'] = $dealId;
            $invoice['INVOICE_PROPERTIES'] = $companyReq;
            $invoice['KEY'] = $key;

            $result = $Invoice->import($invoice, false);

            if(!$result->isSuccess()) {
                $this->result->addError(new Error($result->getErrors()));
            }

        }

        $invoiceAll = $Invoice->search(['UF_DEAL_ID' => $dealId], ['ID', 'ORDER_TOPIC']);
        if(count($invoiceAll) > count($arInvoice)) {
            $arDelete = [];
            foreach ($invoiceAll as $key => $invoice) {
                foreach ($arInvoice as $invoice1С) {
                    if($invoice1С['ORDER_TOPIC'] !== $invoice['ORDER_TOPIC'])
                        $arDelete[] = $invoice;                    
                }
            }
            if(count($arDelete) > 0)
                $Invoice->deleteAll($arDelete);

        }
       

    }

    public function updateField($dealId, $arFields) {

        $this->update($dealId, $arFields);

        return $this->result;    

    }

    /*
    * Импорт комментариев
    */
    protected function importComment($dealId, $comment) {

        $arFields = [
            'BINDINGS' => [[
                'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
                'ENTITY_ID' => $dealId
            ]],
            'TEXT' => $comment
        ];

        $Timeline = new \Pixite\Crm\Timeline;
        $Timeline->import($arFields, false);

    }

    /*
    * Статусы сделок
    */
    protected function getStageId($stage) {

        $arStage = [
            6 => 'КОбеспечению' //Производство
        ];

        return array_search($stage, $arStage);

    }

    public function getCategory() { 
        
        $cache_id = 'TEKO.CRM.DEAL.CATEGORY';
        $arItems = [];
        $obCache = new \CPHPCache();
        if ($obCache->InitCache(36000, $cache_id . 'crm', '/crm/deal/category')){
            $arItems = $obCache->GetVars();
        }
        elseif ($obCache->StartDataCache())	{
            $dbRes = \Bitrix\Crm\Category\DealCategory::getList([]);       
            while ($res = $dbRes->fetch()) { 
                $arItems[$res['ID']] = $res['NAME'];   
            }   
            $arItems[0] = 'Активные продажи';
            $obCache->EndDataCache($arItems);
        }
        
        return $arItems;       
       
    }

    public function checkProducts($dealId) { 
        
        $arProducts = \CCrmDeal::LoadProductRows($dealId);
        if(!is_array($arProducts) || count($arProducts) <= 0)
            return false;

        foreach ($arProducts as $key => $product) {
            if($product['PRICE'] == 0 || $product['QUANTITY'] == 0) {
               return false;
            }
        }
        
        return true;       
       
    }

    /*
    * Проверка выгрузки в 1С
    */
    public function checkExport1C($id) {

        $company = current(Deal::search(['ID' => $id], ['ID', Common::getOption('DEAL_EXPORT_IN_1С')]));  
        
        return (!empty($company[Common::getOption('DEAL_EXPORT_IN_1С')]) ? true : false);       

    }

    /*
    * Проверка выгрузки в 1С
    */
    public function checkStages($stage, $category, $arStage) {

        if(in_array($category, Common::getOption('DEAL_CATEGORY_FOR_EXPORT'))
            && in_array($stage, $arStage)
        )       
            return true;
        else
            return false;
        
            
    }

}