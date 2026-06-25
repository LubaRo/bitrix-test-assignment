<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
    die();

/** @var array $arCurrentValues */

use Bitrix\Main\Loader;

if (!Loader::includeModule('iblock')) {
    return;
}

$arTypesEx = CIBlockParameters::GetIBlockTypes();
$iblockFilter = [
    'ACTIVE' => 'Y',
];
if (!empty($arCurrentValues['IBLOCK_TYPE'])) {
    $iblockFilter['TYPE'] = $arCurrentValues['IBLOCK_TYPE'];
}
if (isset($_REQUEST['site'])) {
    $iblockFilter['SITE_ID'] = $_REQUEST['site'];
}
$db_iblock = CIBlock::GetList(["SORT" => "ASC"], $iblockFilter);

$arIBlocks = [];
while ($arRes = $db_iblock->Fetch()) {
    $arIBlocks[$arRes["ID"]] = "[" . $arRes["ID"] . "] " . $arRes["NAME"];
}

$arComponentParameters = [
    'PARAMETERS' => [
        "IBLOCK_TYPE" => [
            "PARENT" => "BASE",
            "NAME" => 'Тип информационного блока (используется только для проверки)',
            "TYPE" => "LIST",
            "VALUES" => $arTypesEx,
            "DEFAULT" => "news",
            "REFRESH" => "Y",
        ],
        "IBLOCK_ID" => [
            "PARENT" => "BASE",
            "NAME" => 'Код информационного блока',
            "TYPE" => "LIST",
            "VALUES" => $arIBlocks,
            "DEFAULT" => '={$_REQUEST["ID"]}',
            "ADDITIONAL_VALUES" => "Y",
            "REFRESH" => "Y",
        ],
        'NEWS_COUNT' => [
            'NAME' => 'Элементов на странице',
            'TYPE' => 'NUMBER',
            'DEFAULT' => '10',
            'PARENT' => 'BASE',
        ],
        "VARIABLE_ALIASES" => [
            "ELEMENT_ID" => ["NAME" => 'Идентификатор новости'],
        ],
        "SEF_MODE" => [
            "detail" => [
                "NAME" => "Страница детального просмотра",
                "DEFAULT" => "#ELEMENT_ID#/",
                "VARIABLES" => ["ELEMENT_ID", "SECTION_ID"],
            ],
        ],
        "CACHE_TIME" => ["DEFAULT" => 36000000],
        "SET_TITLE" => [],
        "SET_BROWSER_TITLE" => [
            "NAME" => 'Устанавливать заголовок окна браузера',
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
            "PARENT" => "ADDITIONAL_SETTINGS",
        ],
    ],
];
