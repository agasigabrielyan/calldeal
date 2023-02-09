<?php
defined('B_PROLOG_INCLUDED') || die;

use Bitrix\Main\EventManager;
use Pusk\Handler\Deal;
use Pusk\Handler\Main;
use Pusk\UserTypeField\Address;
use Pusk\UserTypeField\CustomEnum;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
$eventManager = EventManager::getInstance();

//region main
$eventManager->addEventHandler('main', 'OnUserTypeBuildList',
	[Address::class, 'GetUserTypeDescription']);
$eventManager->addEventHandler('main', 'OnUserTypeBuildList',
	[CustomEnum::class, 'GetUserTypeDescription']);

AddEventHandler("main", "OnBeforeProlog", "MyOnBeforePrologHandler", 50);

function MyOnBeforePrologHandler()
{
    CJSCore::RegisterExt(
        "call.deal",
        array(
            "js" => "/local/components/devconsult/call.deal/templates/.default/script.js",
            "css" => "/local/components/devconsult/call.deal/templates/.default/style.css",
            "rel" => ['popup', 'ajax', 'fx', 'ls', 'date', 'json', 'window','jquery'],
            "skip_core" => false,
        )
    );
    CJSCore::Init(['call.deal']);
}