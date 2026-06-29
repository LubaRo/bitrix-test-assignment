<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use \Bitrix\Main\Loader;
use \Bitrix\Main\Type\DateTime;

class LubaroNewListIndexComponent extends \CBitrixComponent
{
    public function executeComponent()
    {
        try {
            if (!Loader::includeModule("iblock")) {
                throw new \Bitrix\Main\NotSupportedException(GetMessage("IBLOCK_MODULE_NOT_INSTALLED"));
            }
            $this->prepareCommonParams();

            if ($this->arParams['DETAIL_ELEMENT_ID']) {
                $this->includeComponentTemplate('detail');

            } else {
                $this->prepareParams();
                $this->prepareCachedData();

                if ($this->arResult["ID"]) {
                    $this->addMetaData();
                }
            }

        } catch (Exception $e) {
            $exceptionHandling = \Bitrix\Main\Config\Configuration::getValue("exception_handling");
            if ($exceptionHandling["debug"]) {
                throw $e;
            } else {
                ShowError($e->getMessage());
            }
        }
    }

    private function prepareCommonParams()
    {
        [$urlConf, $requesVariables] = $this->prepareUrlConfig();

        $this->arParams["DETAIL_URL"] = $urlConf["FOLDER"] . $urlConf["URL_TEMPLATES"]["detail"];
        $this->arParams["IBLOCK_URL"] = $urlConf["FOLDER"] . $urlConf["URL_TEMPLATES"]["news"];

        $elem_id = isset($requesVariables["ELEMENT_ID"]) ? intval($requesVariables["ELEMENT_ID"]) : 0;
        if ($elem_id > 0) {
            $this->arParams["DETAIL_ELEMENT_ID"] = $elem_id;
        }

        $this->arParams['SET_STATUS_404'] = true;
        $this->arParams['SHOW_404'] = true;
        $this->arParams['FILE_404'] = '';

        $this->arParams['IBLOCK_ID'] = (int) ($this->arParams['IBLOCK_ID'] ?? 0);
        $this->arParams["IBLOCK_TYPE"] = trim($this->arParams["IBLOCK_TYPE"] ?? '');

    }

    private function prepareParams()
    {
        CPageOption::SetOptionString("main", "nav_page_in_session", "N");

        $this->arParams['FILTER_NAME'] = 'newsFilter';

        $this->arParams['DISPLAY_NAME'] = 'Y';
        $this->arParams['DISPLAY_PREVIEW_TEXT'] = 'Y';
        $this->arParams['DISPLAY_DATE'] = 'Y';
        $this->arParams['DISPLAY_PICTURE'] = 'Y';
        $this->arParams['DISPLAY_TOP_PAGER'] = false;
        $this->arParams['DISPLAY_BOTTOM_PAGER'] = true;
        $this->arParams['USE_FILTER'] = true;

        $this->arParams["PREVIEW_TRUNCATE_LEN"] = 200;
        $this->arParams['NEWS_COUNT'] = (int) ($this->arParams['NEWS_COUNT'] ?? 10);
        $this->arParams["ACTIVE_DATE_FORMAT"] = $this->getDB()->DateFormatToPHP(\CSite::GetDateFormat("SHORT"));

        $this->arParams["SET_TITLE"] ??= 'Y';
        $this->arParams["INCLUDE_IBLOCK_INTO_CHAIN"] = true;
        $this->arParams["PAGER_TITLE"] = '';
        $this->arParams["PAGER_SHOW_ALWAYS"] = false;
        $this->arParams["PAGER_TEMPLATE"] = '';
        $this->arParams["PAGER_DESC_NUMBERING"] = false;
        $this->arParams["PAGER_DESC_NUMBERING_CACHE_TIME"] = 0;
        $this->arParams["PAGER_SHOW_ALL"] = false;
        $this->arParams["PAGER_BASE_LINK_ENABLE"] ??= 'N';
        $this->arParams["PAGER_BASE_LINK"] ??= '';

    }
    private function prepareCachedData()
    {
        //SELECT
        $arSelect = [
            "ID",
            "IBLOCK_ID",
            "IBLOCK_SECTION_ID",
            "IBLOCK_SECTION",
            "NAME",
            "ACTIVE_FROM",
            "TIMESTAMP_X",
            "DETAIL_PAGE_URL",
            "LIST_PAGE_URL",
            "PREVIEW_TEXT",
            "PREVIEW_TEXT_TYPE",
            "PREVIEW_PICTURE",
        ];

        $context = Bitrix\Main\Context::getCurrent();
        $request = $context->getRequest();
        $filterRequest = $request->getQuery($this->arParams["FILTER_NAME"]) ?? [];

        $additionalFilter = [];
        $filterErrors = [];
        foreach ($filterRequest as $_filterId => $_filterValue) {
            if ($_filterId == "SECTION_ID" && (int) $_filterValue > 0) {
                $additionalFilter["SECTION_ID"] = (int) $_filterValue;
            } elseif ($_filterId == "PERIOD") {
                ['START' => $_start, 'END' => $_end] = $_filterValue;
                [$parsedStart, $_e1] = $this->parseDate($_start);
                [$parsedEnd, $_e2] = $this->parseDate($_end);

                if ($parsedStart) {
                    $additionalFilter[">=DATE_ACTIVE_FROM"] = $parsedStart;
                }
                if ($parsedEnd) {
                    $additionalFilter["<=DATE_ACTIVE_FROM"] = $parsedEnd;
                }

                if ($_e1) {
                    $filterErrors[] = GetMessage('INCORRECT_PERIOD_START');
                }
                if ($_e2) {
                    $filterErrors[] = GetMessage('INCORRECT_PERIOD_END');
                }
                if ($parsedStart && $parsedEnd && $parsedStart > $parsedEnd) {
                    $filterErrors[] = GetMessage('PERIOD_START_LARGER_THAN_END');
                }
            }
        }

        //WHERE
        $arFilter = [
            "IBLOCK_ID" => $this->arParams["IBLOCK_ID"],
            "IBLOCK_LID" => SITE_ID,
            "ACTIVE" => "Y",
            "CHECK_PERMISSIONS" => "Y",
            "ACTIVE_DATE" => "Y",
        ];

        $arFilter = array_merge($arFilter, $additionalFilter);

        //ORDER BY
        $arSort = [
            'ACTIVE_FROM' => 'DESC',
            'SORT' => 'ASC',
        ];

        $arNavParams = [
            "nPageSize" => $this->arParams["NEWS_COUNT"],
            "bDescPageNumbering" => $this->arParams["PAGER_DESC_NUMBERING"],
            "bShowAll" => $this->arParams["PAGER_SHOW_ALL"],
        ];
        $navComponentParameters = [];
        $arNavigation = CDBResult::GetNavParams($arNavParams);
        if ((int) $arNavigation["PAGEN"] === 0 && $this->arParams["PAGER_DESC_NUMBERING_CACHE_TIME"] > 0) {
            $this->arParams["CACHE_TIME"] = $this->arParams["PAGER_DESC_NUMBERING_CACHE_TIME"];
        }
        if (
            $this->startResultCache(
                false,
                [
                    $this->getUser()->GetGroups(),
                    $arNavigation,
                    $arFilter,
                ]
            )
        ) {

            // проверяем существование указанного инф. блока
            $rsIBlock = CIBlock::GetList([], [
                "ACTIVE" => "Y",
                "ID" => $this->arParams["IBLOCK_ID"],
            ]);
            $this->arResult = $rsIBlock->GetNext();
            if (!$this->arResult) {
                $this->abortResultCache();
                $this->process404(GetMessage("T_NEWS_NEWS_NA"));
                return;
            }

            $this->arResult["ITEMS"] = [];
            $this->arResult["ELEMENTS"] = [];
            $this->arResult["FILTER_DISPLAY_ERRORS"] = $filterErrors;

            $rsElement = CIBlockElement::GetList($arSort, $arFilter, false, $arNavParams, $arSelect);
            while ($row = $rsElement->Fetch()) {
                $id = (int) $row['ID'];
                $this->arResult["ITEMS"][$id] = $row;
                $this->arResult["ELEMENTS"][] = $id;
            }
            unset($row);

            $rsSectionsData = CIBlockElement::GetElementGroups($this->arResult["ELEMENTS"], true, ['ID', 'NAME', 'IBLOCK_ELEMENT_ID']);
            $sectionsByElement = [];
            while ($_sectionData = $rsSectionsData->GetNext()) {
                $sectionsByElement[$_sectionData['IBLOCK_ELEMENT_ID']][$_sectionData['ID']] = $_sectionData['NAME'];
            }

            if (!empty($this->arResult['ITEMS'])) {
                $elementFilter = [
                    "IBLOCK_ID" => $this->arResult["ID"],
                    "IBLOCK_LID" => SITE_ID,
                    "ID" => $this->arResult["ELEMENTS"]
                ];
                $obParser = new CTextParser;
                $iterator = CIBlockElement::GetList([], $elementFilter, false, false, $arSelect);
                $iterator->SetUrlTemplates($this->arParams["DETAIL_URL"], '', ($this->arParams["IBLOCK_URL"] ?? ''));
                while ($arItem = $iterator->GetNext()) {
                    $id = (int) $arItem["ID"];
                    $arItem["PREVIEW_TEXT"] = $obParser->html_cut($arItem["PREVIEW_TEXT"], $this->arParams["PREVIEW_TRUNCATE_LEN"]);
                    $arItem["DISPLAY_ACTIVE_FROM"] = $this->formatDate($arItem["ACTIVE_FROM"]);
                    $arItem["SECTION_LIST"] = $sectionsByElement[$id] ?? [];

                    $this->arResult["ITEMS"][$id] = $arItem;
                }
            }
            $this->arResult['ITEMS'] = array_values($this->arResult['ITEMS']);
            $this->arResult["NAV_STRING"] = $rsElement->GetPageNavStringEx(
                $navComponentObject,
                $this->arParams["PAGER_TITLE"],
                $this->arParams["PAGER_TEMPLATE"],
                $this->arParams["PAGER_SHOW_ALWAYS"],
                $this,
                $navComponentParameters
            );
            $this->arResult["NAV_RESULT"] = $rsElement;
            $this->arResult["NAV_PARAM"] = $navComponentParameters;

            $this->includeComponentTemplate('');
        }
    }

    private function addMetaData()
    {
        if ($this->arParams["INCLUDE_IBLOCK_INTO_CHAIN"] && isset($this->arResult["NAME"])) {
            $this->getApplication()->AddChainItem($this->arResult["NAME"]);
        }

        $arTitleOptions = [];
        $ipropertyExists = (!empty($this->arResult["IPROPERTY_VALUES"]) && is_array($this->arResult["IPROPERTY_VALUES"]));
        $iproperty = ($ipropertyExists ? $this->arResult["IPROPERTY_VALUES"] : array());

        if ($this->arParams["SET_TITLE"] == 'Y' && isset($this->arResult["NAME"])) {
            $this->getApplication()->SetTitle($this->arResult["NAME"], $arTitleOptions);
        }

        if ($ipropertyExists) {
            if ($this->arParams["SET_BROWSER_TITLE"] === 'Y' && $iproperty["SECTION_META_TITLE"] != "")
                $this->getApplication()->SetPageProperty("title", $iproperty["SECTION_META_TITLE"], $arTitleOptions);

            if ($this->arParams["SET_META_KEYWORDS"] === 'Y' && $iproperty["SECTION_META_KEYWORDS"] != "")
                $this->getApplication()->SetPageProperty("keywords", $iproperty["SECTION_META_KEYWORDS"], $arTitleOptions);

            if ($this->arParams["SET_META_DESCRIPTION"] === 'Y' && $iproperty["SECTION_META_DESCRIPTION"] != "")
                $this->getApplication()->SetPageProperty("description", $iproperty["SECTION_META_DESCRIPTION"], $arTitleOptions);
        }
    }
    private function prepareUrlConfig()
    {
        $arComponentVariables = ["ELEMENT_ID", "ELEMENT_CODE"];
        $this->arParams["SEF_URL_TEMPLATES"] ??= [];
        $arVariables = [];
        if ($this->arParams["SEF_MODE"] == "Y") {
            $arDefaultVariableAliases404 = [
                "ELEMENT_ID" => "ELEMENT_ID",
                "ELEMENT_CODE" => "ELEMENT_CODE",
            ];
            $arDefaultUrlTemplates404 = [];
            $arUrlTemplates = CComponentEngine::makeComponentUrlTemplates($arDefaultUrlTemplates404, $this->arParams["SEF_URL_TEMPLATES"]);
            $arVariableAliases = CComponentEngine::makeComponentVariableAliases($arDefaultVariableAliases404, $this->arParams["VARIABLE_ALIASES"]);

            $engine = new CComponentEngine($this);

            $componentPage = $engine->guessComponentPath(
                $this->arParams["SEF_FOLDER"],
                $arUrlTemplates,
                $arVariables
            );

            $b404 = false;
            if (!$componentPage) {
                $componentPage = "";
                $b404 = true;
            }
            if ($b404 && CModule::IncludeModule('iblock')) {
                $folder404 = str_replace("\\", "/", $this->arParams["SEF_FOLDER"]);
                if ($folder404 != "/")
                    $folder404 = "/" . trim($folder404, "/ \t\n\r\0\x0B") . "/";
                if (mb_substr($folder404, -1) == "/")
                    $folder404 .= "index.php";

                if ($folder404 != $this->getApplication()->GetCurPage(true)) {
                    $this->process404('');
                }
            }

            CComponentEngine::initComponentVariables($componentPage, $arComponentVariables, $arVariableAliases, $arVariables);

            $urlConf = [
                "FOLDER" => $this->arParams["SEF_FOLDER"],
                "URL_TEMPLATES" => $arUrlTemplates,
                "VARIABLES" => $arVariables,
                "ALIASES" => $arVariableAliases,
            ];
        } else {
            $arVariableAliases = CComponentEngine::makeComponentVariableAliases([], $this->arParams["VARIABLE_ALIASES"]);
            CComponentEngine::initComponentVariables(false, $arComponentVariables, $arVariableAliases, $arVariables);

            $currentPage = $this->getApplication()->GetCurPage();
            $urlConf = [
                "FOLDER" => "",
                "URL_TEMPLATES" => [
                    "detail" => htmlspecialcharsbx($currentPage . "?" . $arVariableAliases["ELEMENT_ID"] . "=#ELEMENT_ID#"),
                ],
                "VARIABLES" => $arVariables,
                "ALIASES" => $arVariableAliases
            ];
        }

        return [$urlConf, $arVariables];
    }

    private function formatDate($date)
    {
        if (empty($date)) {
            return '';
        }
        return CIBlockFormatProperties::DateFormat(
            $this->arParams["ACTIVE_DATE_FORMAT"],
            MakeTimeStamp(
                $date,
                CSite::GetDateFormat()
            )
        );
    }

    private function process404($message)
    {
        \Bitrix\Iblock\Component\Tools::process404(
            $message,
            $this->arParams["SET_STATUS_404"],
            $this->arParams["SET_STATUS_404"],
            $this->arParams["SHOW_404"],
            $this->arParams["FILE_404"]
        );
    }
    private function parseDate($date)
    {
        $result = null;
        $hasErrors = false;
        if (empty($date)) {
            return $result;
        }
        try {
            $result = new DateTime($date, 'd.m.Y');
        } catch (Exception $e) {
            $hasErrors = true;
        }
        return [$result, $hasErrors];
    }
    private function getApplication()
    {
        global $APPLICATION;
        return $APPLICATION;
    }

    private function getDB()
    {
        global $DB;
        return $DB;
    }

    private function getUser()
    {
        global $USER;
        return $USER;
    }
}