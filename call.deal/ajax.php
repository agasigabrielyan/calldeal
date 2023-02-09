<?php
define('PUBLIC_AJAX_MODE', true);
define('STOP_STATISTICS', true);
define('NO_AGENT_CHECK', true);

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Loader;
use Bitrix\Crm\Service;

CModule::IncludeModule("iblock");
CModule::IncludeModule("catalog");
CModule::IncludeModule("sale");
CModule::includeModule('crm');

// обязательно проверяем сессию
check_bitrix_sessid() || die();

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$requestAction = $request->getPost("action");

switch ( $requestAction ) {
    case "get-catalog-products":
        $result = getCatalogProducts();
        break;
    case "get-catalog-item":
        $ID = $request->getPost( 'id' );
        $result = getCatalogItem( $ID );
        break;
    case "create-deal":
        $requestData = \Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getPostList()->toArray();
        $result = createDeal( $requestData );
        break;
}

echo json_encode( $result );










// 1) метод получает все товары каталога
function getCatalogProducts() {
    $goods = \Bitrix\Iblock\Elements\ElementCatalogcrmTable::getList([
        'select' => ['ID','NAME'],
        'filter' => ['ACTIVE' => 'Y']
    ])->fetchAll();
    return $goods;
}

// 2) метод получает конкретный товар по его идентификатору
function getCatalogItem( $ID ) {
    $good = \Bitrix\Iblock\Elements\ElementCatalogcrmTable::getList([
        'select' => ['*'],
        'filter' => ['ID' => $ID]
    ])->fetch();

    $price = getFinalPriceCurrency( $good['ID'] );

    $good['PRICE'] = $price;

    return $good;
}

// 3) метод получает стоимость товара по его идентификатору
function getFinalPriceCurrency($item_id,$sale_currency = 'RUB'){
    // простой товар для количества 1
    global $USER;
    $price = CCatalogProduct::GetOptimalPrice(
        $item_id,
        1,
        $USER->GetUserGroupArray(),
        'N'
    );

    if(!$price || !isset($price['PRICE']))
    {
        return false;
    }

// меняем код валюты если нашли
    if(isset($price['CURRENCY']))
    {
        $currency_code = $price['CURRENCY'];
    }

    if(isset($price['PRICE']['CURRENCY']))
    {
        $currency_code = $price['PRICE']['CURRENCY'];
    }

// получаем итоговую цену
    $final_price = $price['PRICE']['PRICE'];

// ищем скидки и пересчитываем цену товара с их учетом
    $arDiscounts = CCatalogDiscount::GetDiscountByProduct(
        $item_id,
        $USER->GetUserGroupArray(),
        "N",
        2
    );

    if(is_array($arDiscounts) && sizeof($arDiscounts)>0)
    {
        $final_price = CCatalogProduct::CountPriceWithDiscount(
            $final_price,
            $currency_code,
            $arDiscounts
        );
    }

// если необходимо конвертируем в нужную валюту
    if($currency_code != $sale_currency)
    {
        $final_price = CCurrencyRates::ConvertCurrency(
            $final_price,
            $currency_code,
            $sale_currency
        );
    }

    return $final_price;
}

// 4) метод создает сделку с помощью смарт-процесса
function createDeal( $data ) {
    $result = [];

    // 0) Проверка существования товара доставка, удаляем переменную связанную с доставкой если доставка существует и она не изменена
    foreach( $data['ids'] as $productId) {
        $dbMetroStationProperty = \CIBlockElement::GetProperty(
            26, // идентификатор инфоблока товаров
            $productId,
            [],
            ['CODE' => 'METRO_STATION']
        );
        if( $metroStation = $dbMetroStationProperty->Fetch() ) {
            // по всей видимости товар типа метро/доставка существует, делаем unset переменной из select метродоставка если значения одинаковы
            // это будет означать, что в результате запуска бизнес-процессов не будет создаваться еще один товар метродоставка
            if( $metroStation['VALUE'] != null) {
                if( $data['UF_CRM_METRO_STATION'] == $metroStation['VALUE']) {
                    unset( $data['UF_CRM_METRO_STATION'] );
                }
            }
        }
    }


    // 1) создаем контакт - получаем CONTACT_ID созданного контакта
    global $USER;
    $arContactFields = array(
        'FULL_NAME' => trim( $data['name'] ),
        'ASSIGNED_BY_ID' => $USER->GetID()
    );
    if( strlen($data['CONTACT_ID']) > 0 ) {
        $CONTACT_ID = $data['CONTACT_ID'];
    } else {
        $CONTACT_ID = createNewContact( $arContactFields );
    }

    // 2) если есть есть поле STAGE_RADIO, тогда это не новая сделка
    $STAGE_ID = "C1:NEW";
    if( strlen($data['STAGE_RADIO']) > 0 ) {
        $STAGE_ID = $data['STAGE_RADIO'];
    }

    $arFields = [
        "MODIFY_BY_ID" => 1,
        "TITLE" => "Заявка клиента",
        "CATEGORY_ID" => 1,
        "CONTACT_ID" => $CONTACT_ID,
        "STAGE_ID" => $STAGE_ID,
        "OPENED" => "Y",
        "COMMENTS" => $data['COMMENTS'],
        'CURRENCY_ID'=>'RUB',
        'UF_CRM_1592552787' => $data['UF_CRM_1592552787'],
        'UF_CRM_1591010195' => $data['UF_CRM_1591010195'],
        'UF_CRM_METRO_STATION' => $data['UF_CRM_METRO_STATION'],
        'UF_CRM_1591011159' => $data['UF_CRM_1591011159'],
        'UF_CRM_ADRES_DOSTAVKI' => $data['UF_CRM_ADRES_DOSTAVKI'],
        'UF_CRM_RESPONSIBLE_CONTACT_NAME' => $data['UF_CRM_RESPONSIBLE_CONTACT_NAME'],
        'UF_CRM_RESPONSIBLE_CONTACT_PHONE' => $data['UF_CRM_RESPONSIBLE_CONTACT_PHONE']
    ];

    // 2) создаем сделку с помощью смарт-процесса
    $factory = Service\Container::getInstance()->getFactory(2);

    if( intval($data['dealidentifier']) > 0 ) {
        // обновим если поле сделки уже содержит в себе идентификатор, значит эта сделка уже существует, получим ее по идентификатору
        $item = $factory->getItem( intval( $data['dealidentifier'] ) );
    } else {
        // в противном случае создаем новую сделку
        $item = $factory->createItem();
    }

    $item->setFromCompatibleData( $arFields );

    $productRows=[];

    // добавляем товары
    foreach ( $data['goods'] as $key => $value ) {

        $discount = floatval($data['sale'][$key]) / floatval($data['quantity'][$key]);  // скидка на единицу товара попадает в поля продукта

        $productRows[] = [
            'PRODUCT_ID' => $data['ids'][$key],
            'PRODUCT_NAME' => $data['goods'][$key],
            'QUANTITY' => $data['quantity'][$key],
            'PRICE_EXCLUSIVE' => floatval($data['prices'][$key]) - floatval($discount), //цена без налога со скидкой
            'PRICE_NETTO' => floatval($data['prices'][$key]), // цена без налога и без скидки
            'PRICE_BRUTTO' => floatval($data['prices'][$key]), // цена с налогом и без скидки
            'PRICE' => floatval($data['prices'][$key]) - floatval($discount), // итоговая цена позиции с налогом и со скидкой
            'DISCOUNT_SUM' => floatval($discount),
            'DISCOUNT_TYPE_ID' => 1,
            'TAX_INCLUDED' => 'N',
        ];
    }

    $item->setProductRowsFromArrays( $productRows );

    if( intval($data['dealidentifier']) > 0 ) {
        // обновим если поле сделки уже содержит в себе идентификатор, значит эта сделка уже существует
        $opper = $factory->getUpdateOperation( $item );
    } else {
        // в противном случае создаем новую сделку
        $opper = $factory->getAddOperation( $item );
    }

    // запускаем все бизнес-процессы
    $opper->launch();

    if($item->getId() > 0) $result['DEAL_ID'] = $item->getId();



    // сделка создана или обновлена, теперь наша задача получить документы вывести формы для этих документов
    $documents = [];
    $dbDocuments = CIBlockElement::GetList(
        array('NAME' => 'DESC'),
        array('IBLOCK_ID' => 32,'PROPERTY_CRM_MAIN' => intval($item->getId())),
    );

    while($objRow = $dbDocuments -> GetNextElement()) {
        $row = array_merge(
            $objRow -> getFields(),
            array('PROPERTIES' => $objRow -> getProperties()));
        $documents[] = $row;
    }

    $result['DOCUMENTS'] = $documents;


    // сделка создана или обновлена, получим так же все товары этой сделки
    $result['PRODUCTS'] = getDealProducts( $item->getId() );

    return $result;
}

// 5) create new contact, return CONTACT_ID
function createNewContact( $arContactFields ) {

    /**
     * true, если нужно проверять права текущего пользователя.
     * Текущий пользователь определяется ID в ключе CURRENT_USER
     * $arOptions
     * @var boolean
     */
    $bCheckRight = true;

    /**
     * Поля добавляемого контакта
     * @var array
     */
    $contactFields = [

        // Основные поля
        'LAST_NAME'   => $arContactFields['FULL_NAME'],
        'NAME'        => $arContactFields['FULL_NAME'],
        'SECOND_NAME' => "",

        // Технические поля
        "OPENED" => "Y", // "Доступен для всех" = Да
        "ASSIGNED_BY_ID" => $arContactFields['ASSIGNED_BY_ID'],
    ];


    $contactEntity = new \CCrmContact( $bCheckRight );

    $contactId = $contactEntity->Add(
        $contactFields,
        $bUpdateSearch = true,
        $arOptions = [
            /**
             * ID пользователя, от лица которого выполняется действие
             * в том числе проверка прав
             * @var integer
             */
            'CURRENT_USER' => \CCrmSecurityHelper::GetCurrentUserID(),

            /**
             * Устанавливайте флаг, только если сущность проходит
             * процедуру восстановления. В случае если флаг есть
             * можно заполнять технические поля DATE_CREATE, DATE_MODIFY
             * @var boolean
             */
            // 'IS_RESTORATION' => true,
        ]
    );

    if ( !$contactId )
    {
        $some = "";
        /**
         * Произошла ошибка при добавлении контакта, посмотреть ее можно
         * через любой из способов ниже:
         * 1. $contactFields['RESULT_MESSAGE']
         * 2. $contactEntity->LAST_ERROR
         */
    }


    return $contactId;
}

// 5) метод получает все товары сделки
function getDealProducts( $dealId ) {
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

// 6) проверить существует ли товар метростанция, если он существует проверить наименование, не создавать если наминования совпадают,
function checkIfMetroDeliveryExists( $dealId, $metrostationvalue ) {
    $metroStationProductExists = false;

    $products = getDealProducts( $dealId );
    foreach ( $products as $productKey => $productValue ) {
        $metrostationProduct = \Bitrix\Iblock\Elements\ElementCatalogcrmTable::getList([
            'select' => [ 'ID','NAME','METRO_STATION_VALUE'=>'METRO_STATION.VALUE' ],
            'filter' => [ 'NAME' => $productValue['PRODUCT_NAME'] ]
        ])->fetchAll()[0]['METRO_STATION_VALUE'];

        if( $metrostationProduct ) {
            $metroStationProductExists = true;
        }
    }

    return $metroStationProductExists;
}

