<?php if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
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