<?php if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/*
 * @var $componentPath
 * @var $templateFolder
 */
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\ActionFilter;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Loader;
Loader::includeModule("iblock");


class CallDeal extends \CBitrixComponent implements Controllerable {

    public function configureActions()
    {

    }

    /**
     * Метод получает данные сделки по ее идентификатору
     *
     * @param $dealId
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getDealData( $dealId ) {
        $allFieldsOfMultiTableOfContact = \Bitrix\Crm\ContactTable::getMap();
        $selectedFields = [
            '*',
            'UF_*',
        ];
        // с помощью метода getMap мы получили все поля контакта, которые хранятся в таблице b_crm_field_multi
        foreach( $allFieldsOfMultiTableOfContact as $key => $value ) {
            $selectedFields[] = 'contacttable.' . $value->getName();
        }

        // получаем данные сделки
        $dealData = \Bitrix\Crm\DealTable::getList([
            'select' => $selectedFields,
            'filter' => ['ID' => intval( $dealId )],
            'runtime' => [
                'contacttable' => [
                    'data_type' => \Bitrix\Crm\ContactTable::getEntity(),
                    'reference' => [
                        '=this.CONTACT_ID' => 'ref.ID'
                    ]
                ]
            ]
        ])->fetchAll()[0];

        $result = $dealData;

        return $result;
    }

    /**
     * Метод получает документы
     *
     * @param $dealId
     */
    private function getDocuments( $dealId ) {
        $documents = [];
        $dbDocuments = CIBlockElement::GetList(
            array('NAME' => 'DESC'),
            array('IBLOCK_ID' => 32,'PROPERTY_CRM_MAIN' => $dealId),
        );

        while($objRow = $dbDocuments -> GetNextElement()) {
            $row = array_merge(
                $objRow -> getFields(),
                array('PROPERTIES' => $objRow -> getProperties()));
            $documents[] = $row;
        }

        return $documents;
    }

    /**
     * Метод получает все товары сделки
     *
     * @param $dealId
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    private function getDealProducts( $dealId ) {
        // сделаем JOIN с инфоблоком каталога, так как в таблице crm_catalog_product_row нет имени у товаров метро-доставка
        $products = \Bitrix\Crm\ProductRowTable::getList([
            'select' => ['*', 'CATALOG_NAME' => 'catalog.NAME'],
            'filter' => ['OWNER_ID' => $dealId],
            'runtime' => [
                'catalog' => [
                    'data_type' => \Bitrix\Iblock\Elements\ElementCatalogcrmTable::getEntity(),
                    'reference' => [
                        'this.PRODUCT_ID' => 'ref.ID'
                    ]
                ]
            ]
        ])->fetchAll();

        foreach( $products as $key => $value ) {
            if( $value['PRODUCT_NAME'] == "" ) {
                $products[$key]['PRODUCT_NAME'] = $value['CATALOG_NAME'];
            }
            $dbMetroStationProperty = \CIBlockElement::GetProperty(
                26, // идентификатор инфоблока товаров
                $value['PRODUCT_ID'],
                [],
                ['CODE' => 'METRO_STATION']
            );
            if( $metroStation = $dbMetroStationProperty->Fetch()) {
                $products[$key]['METRO_STATION'] = $metroStation['VALUE'];
            }
        }

        return $products;
    }

    public function executeComponent()
    {
        // получим данные сделки и запишем в $arResult
        $dealId = \Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getPost('identifier');
        if( $dealId > 0 ) {
            $this->arResult['DEAL_DATA'] = $this->getDealData( $dealId );
            $this->arResult['DEAL_ID'] = $dealId;
            $this->arResult['PRODUCTS'] = $this->getDealProducts( $dealId );
            $this->arResult['DOCUMENTS'] = $this->getDocuments($dealId);
        }
        $this->includeComponentTemplate();
    }

}