<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
    die();
/** @var CBitrixComponent $this */
/** @var array $arParams */
/** @var array $arResult */
/** @var string $componentPath */
/** @var string $componentName */
/** @var string $componentTemplate */
/** @global CUser $USER */
global $USER;

if (!isset($arParams["CACHE_TIME"]))
    $arParams["CACHE_TIME"] = 36000000;

$arParams["IBLOCK_ID"] = intval($arParams["IBLOCK_ID"]);

$FILTER_NAME = $arParams["FILTER_NAME"];
$arParams['FILTER_DISPLAY_ERRORS'] ??= [];

if (!CModule::IncludeModule("iblock")) {
    ShowError(GetMessage("CC_BCF_MODULE_NOT_INSTALLED"));
    return 0;
}

if ($this->StartResultCache(false, ($arParams["CACHE_GROUPS"] === "N" ? false : $USER->GetGroups()))) {
    $arResult["arrSection"] = [];
    $rsSection = CIBlockSection::GetList(
        ["name" => "asc"],
        [
            "IBLOCK_ID" => $arParams["IBLOCK_ID"],
            "ACTIVE" => "Y",
            "!==NAME" => null,
            ">DEPTH_LEVEL" => '0'
        ],
        false
    );
    while ($arSection = $rsSection->Fetch()) {
        $arResult["arrSection"][$arSection["ID"]] = $arSection["NAME"];
    }
    $this->EndResultCache();
}

$arResult["FORM_ACTION"] = isset($_SERVER['REQUEST_URI']) ? htmlspecialcharsbx($_SERVER['REQUEST_URI']) : "";
$arResult["FILTER_NAME"] = $FILTER_NAME;

$filters = ['SECTION_ID', 'PERIOD'];
$inputValues = $_REQUEST[$FILTER_NAME] ?? [];

$arResult["ITEMS"] = [];
$arResult['inputValues'] = [];

foreach ($filters as $f_id) {
    $input_name = $FILTER_NAME . "[" . $f_id . "]";
    $input_value = $inputValues[$f_id] ?? '';

    if ($f_id == 'SECTION_ID') {
        $arResult['inputValues']['section_id'] = $input_value ?? '';

    } elseif ($f_id == 'PERIOD') {
        $arResult['inputValues']['preriod_start'] = $input_value['START'] ?? '';
        $arResult['inputValues']['preriod_end'] = $input_value['END'] ?? '';
    }
}

$arResult["arrInputNames"]["set_filter"] = true;
$arResult["arrInputNames"]["del_filter"] = true;


$this->IncludeComponentTemplate();