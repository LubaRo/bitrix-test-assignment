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


/*************************************************************************
    Processing of received parameters
*************************************************************************/
if (!isset($arParams["CACHE_TIME"]))
    $arParams["CACHE_TIME"] = 36000000;

$arParams["IBLOCK_ID"] = intval($arParams["IBLOCK_ID"]);

$FILTER_NAME = $arParams["FILTER_NAME"];


if (!CModule::IncludeModule("iblock")) {
    ShowError(GetMessage("CC_BCF_MODULE_NOT_INSTALLED"));
    return 0;
}

if ($this->StartResultCache(false, ($arParams["CACHE_GROUPS"] === "N" ? false : $USER->GetGroups()))) {
    $arResult["arrSection"] = [];

    // sections list
    $rsSection = CIBlockSection::GetList(
        array("name" => "asc"),
        array(
            "IBLOCK_ID" => $arParams["IBLOCK_ID"],
            "ACTIVE" => "Y",
            "!==NAME" => null,
            ">DEPTH_LEVEL" => '0'
        ),
        false
    );
    while ($arSection = $rsSection->Fetch()) {
        $arResult["arrSection"][$arSection["ID"]] = $arSection["NAME"];
    }
    // years list
    ['start' => $periodStart, 'end' => $periodEnd] = $arParams['NEWS_PERIOD'];
    $startYear = (int) CIBlockFormatProperties::DateFormat(
        "Y",
        MakeTimeStamp(
            $periodStart,
            CSite::GetDateFormat()
        )
    );
    $endYear = (int) CIBlockFormatProperties::DateFormat(
        "Y",
        MakeTimeStamp(
            $periodEnd,
            CSite::GetDateFormat()
        )
    );

    $arResult["arrYear"] = [];
    if ($startYear > 0 && $endYear > 0 && $endYear >= $startYear) {
        $currentYear = $endYear;
        while ($currentYear >= $startYear) {
            $arResult["arrYear"][] = $currentYear;
            $currentYear -= 1;
        }
    }

    $this->EndResultCache();
}

$arResult["FORM_ACTION"] = isset($_SERVER['REQUEST_URI']) ? htmlspecialcharsbx($_SERVER['REQUEST_URI']) : "";
$arResult["FILTER_NAME"] = $FILTER_NAME;

/*************************************************************************
        Adding the titles and input fields
*************************************************************************/

$inputValues = $_REQUEST[$FILTER_NAME] ?? [];

$filters = ['SECTION_ID', 'YEAR'];
$arResult["ITEMS"] = [];

foreach ($filters as $f_id) {
    $input_name = $FILTER_NAME . "[" . $f_id . "]";
    $input_value = $inputValues[$f_id] ?? '';

    if ($f_id == 'SECTION_ID') {
        $reference = ["reference" => array_values($arResult["arrSection"]), "reference_id" => array_keys($arResult["arrSection"])];
        $field_result = SelectBoxFromArray($input_name, $reference, $input_value, GetMessage("IBLOCK_All_CATEGORIES"), "");

        $label = "LUBARO_IBLOCK_FIELD_SECTION_ID";

        $field_type = 'SELECT';
        $field_list = $arResult["arrSection"];
    } elseif ($f_id == 'YEAR') {
        $reference = array("reference" => array_values($arResult["arrYear"]), "reference_id" => array_values($arResult["arrYear"]));
        $field_result = SelectBoxFromArray($input_name, $reference, $input_value, "-", "");

        $label = "LUBARO_IBLOCK_FIELD_YEAR";

        $field_type = 'SELECT';
        $field_list = $arResult["arrYear"];
    }

    $arResult["ITEMS"][$f_id] = [
        "NAME" => htmlspecialcharsbx(GetMessage($label)),
        "INPUT" => $field_result,
        "INPUT_NAME" => $input_name,
        "INPUT_VALUE" => is_array($input_value) ? array_map("htmlspecialcharsbx", $input_value) : htmlspecialcharsbx($input_value),
        "~INPUT_VALUE" => $input_value,
        "TYPE" => $field_type,
        "INPUT_NAMES" => '',
        "INPUT_VALUES" => '',
        "~INPUT_VALUES" => '',
        "LIST" => $field_list
    ];
}

$arResult["arrInputNames"]["set_filter"] = true;
$arResult["arrInputNames"]["del_filter"] = true;


$this->IncludeComponentTemplate();