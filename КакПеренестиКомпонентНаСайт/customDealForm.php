<?php require_once ($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
/**
 * @var $APPLICATION
 * @var $arResult
 */
?>
<?php
    if( \Bitrix\Main\Application::getInstance()->getContext()->getRequest()->isAjaxRequest() ) {
        $IDENTIFIER = \Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getPost('identifier');
        $APPLICATION->RestartBuffer();
            $APPLICATION->IncludeComponent(
                "devconsult:call.deal",
                ".default",
                [
                    'IDENTIFIER' => $IDENTIFIER
                ],
                false
            );
        die();
    } else {
        LocalRedirect("/");
    }
?>
<?php
/*    $APPLICATION->IncludeComponent(
        "devconsult:call.deal",
        ".default",
        [],
        false
    );
*/?>
