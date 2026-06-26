<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Type\Collection;
use \Bitrix\Main\Loader;

class LubaroNewsDetailIndexComponent extends \CBitrixComponent
{
    public function executeComponent()
    {
        try {
            if (!Loader::includeModule("iblock")) {
                throw new \Bitrix\Main\NotSupportedException(GetMessage("IBLOCK_MODULE_NOT_INSTALLED"));
            }
            $this->prepareParams();
            $this->prepareCachedData();

            if ($this->arResult["ID"]) {
                $this->addMetaData();
                $this->addBreadcrumbsInfo();
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

    private function prepareParams()
    {
        if (!$this->arParams['ELEMENT_ID']) {
            $this->process404();
        }

        $this->arParams["SET_TITLE"] ??= 'Y';
        $this->arParams["SET_BROWSER_TITLE"] ??= 'Y';
        $this->arParams["SET_META_KEYWORDS"] ??= 'Y';
        $this->arParams["SET_META_DESCRIPTION"] ??= 'Y';
        $this->arParams["SET_CANONICAL_URL"] ??= 'Y';

        $this->arParams["CACHE_TIME"] ??= '36000000';
        $this->arParams["CACHE_TYPE"] ??= 'A';
        $this->arParams['INCLUDE_IBLOCK_INTO_CHAIN'] ??= true;
        $this->arParams["ADD_SECTIONS_CHAIN"] ??= true;

        $this->arParams["ACTIVE_DATE_FORMAT"] ??= $this->getDB()->DateFormatToPHP(\CSite::GetDateFormat("SHORT"));
    }

    private function prepareCachedData()
    {
        $arFilter = [
            'ID' => $this->arParams["ELEMENT_ID"],
            'IBLOCK_ID' => $this->arParams["IBLOCK_ID"],
            'IBLOCK_LID' => SITE_ID,
            'IBLOCK_ACTIVE' => 'Y',
            'ACTIVE' => 'Y',
            'ACTIVE_DATE' => 'Y',
            'CHECK_PERMISSIONS' => 'Y',
            'SHOW_HISTORY' => 'N',
        ];
        if ($this->arParams['IBLOCK_ID'] > 0) {
            $arFilter['IBLOCK_ID'] = $this->arParams['IBLOCK_ID'];
        } else {
            $arFilter['=IBLOCK_TYPE'] = $this->arParams['IBLOCK_TYPE'];
        }
        if ($this->startResultCache(false, [$this->getUser()->GetGroups()])) {
            if (!Loader::includeModule("iblock")) {
                $this->abortResultCache();
                ShowError(GetMessage("IBLOCK_MODULE_NOT_INSTALLED"));
                return;
            }

            $arSelect = [
                "ID",
                "NAME",
                "IBLOCK_ID",
                "IBLOCK_SECTION_ID",
                "DETAIL_TEXT",
                "DETAIL_TEXT_TYPE",
                "PREVIEW_TEXT",
                "PREVIEW_TEXT_TYPE",
                "DETAIL_PICTURE",
                "TIMESTAMP_X",
                "ACTIVE_FROM",
                "LIST_PAGE_URL",
                "DETAIL_PAGE_URL",
            ];
            if (isset($this->arParams['SET_CANONICAL_URL']) && $this->arParams['SET_CANONICAL_URL'] === 'Y') {
                $arSelect[] = 'CANONICAL_PAGE_URL';
            }

            $rsElement = CIBlockElement::GetList([], $arFilter, false, false, $arSelect);
            $rsElement->SetUrlTemplates($this->arParams["DETAIL_URL"] ?? '', '', $this->arParams["IBLOCK_URL"]);
            if ($obElement = $rsElement->GetNextElement()) {
                $this->arResult = $obElement->GetFields();

                $this->arResult["DISPLAY_ACTIVE_FROM"] = $this->arResult["ACTIVE_FROM"] <> ''
                    ? $this->arResult["DISPLAY_ACTIVE_FROM"] = CIBlockFormatProperties::DateFormat(
                        $this->arParams["ACTIVE_DATE_FORMAT"],
                        MakeTimeStamp(
                            $this->arResult["ACTIVE_FROM"],
                            CSite::GetDateFormat()
                        )
                    )
                    : '';

                $ipropValues = new \Bitrix\Iblock\InheritedProperty\ElementValues($this->arResult["IBLOCK_ID"], $this->arResult["ID"]);
                $this->arResult["IPROPERTY_VALUES"] = $ipropValues->getValues();

                \Bitrix\Iblock\Component\Tools::getFieldImageData(
                    $this->arResult,
                    array('PREVIEW_PICTURE', 'DETAIL_PICTURE'),
                    \Bitrix\Iblock\Component\Tools::IPROPERTY_ENTITY_ELEMENT,
                    'IPROPERTY_VALUES'
                );

                $rsSectionsData = CIBlockElement::GetElementGroups($this->arResult["ID"], true, ['ID', 'NAME']);
                $sectionsList = [];
                while ($_sectionData = $rsSectionsData->GetNext()) {
                    $sectionsList[$_sectionData['ID']] = $_sectionData['NAME'];
                }
                $this->arResult['SECTION_LIST'] = $sectionsList;

                if ($this->arParams["ADD_SECTIONS_CHAIN"] && $this->arResult["IBLOCK_SECTION_ID"] > 0) {
                    $rsPath = CIBlockSection::GetNavChain(
                        $this->arResult["IBLOCK_ID"],
                        $this->arResult["IBLOCK_SECTION_ID"],
                        array(
                            "ID",
                            "CODE",
                            "XML_ID",
                            "EXTERNAL_ID",
                            "IBLOCK_ID",
                            "IBLOCK_SECTION_ID",
                            "SORT",
                            "NAME",
                            "ACTIVE",
                            "DEPTH_LEVEL",
                            "SECTION_PAGE_URL"
                        )
                    );
                    $rsPath->SetUrlTemplates("", $this->arParams["SECTION_URL"]);
                    while ($arPath = $rsPath->GetNext()) {
                        $ipropValues = new \Bitrix\Iblock\InheritedProperty\SectionValues($this->arParams["IBLOCK_ID"], $arPath["ID"]);
                        $arPath["IPROPERTY_VALUES"] = $ipropValues->getValues();
                        $this->arResult["SECTION"]["PATH"][] = $arPath;
                        $this->arResult["SECTION_URL"] = $arPath["~SECTION_PAGE_URL"];
                    }
                }

                $this->arResult["IBLOCK"] = GetIBlock($this->arResult["IBLOCK_ID"], $this->arResult["IBLOCK_TYPE_ID"]);

                $this->prepareMetaData();

                $this->includeComponentTemplate();
            } else {
                $this->abortResultCache();
                $this->process404();
            }
        }
    }

    private function addBreadcrumbsInfo()
    {
        $showBreadCrumbs = false;
        $listPageURL = $this->arParams['LIST_PAGE_URL'] ??= '';
        if ($listPageURL <> '' && $this->arParams["INCLUDE_IBLOCK_INTO_CHAIN"] && isset($this->arResult["IBLOCK"]["NAME"])) {
            $showBreadCrumbs = true;
            $this->getApplication()->AddChainItem($this->arResult["IBLOCK"]["NAME"], $listPageURL);
        }

        if ($showBreadCrumbs && $this->arParams["ADD_SECTIONS_CHAIN"] && is_array($this->arResult["SECTION"])) {
            foreach ($this->arResult["SECTION"]["PATH"] as $arPath) {
                if ($arPath["IPROPERTY_VALUES"]["SECTION_PAGE_TITLE"] != "")
                    $this->getApplication()->AddChainItem($arPath["IPROPERTY_VALUES"]["SECTION_PAGE_TITLE"], $arPath["~SECTION_PAGE_URL"]);
                else
                    $this->getApplication()->AddChainItem($arPath["NAME"], $arPath["~SECTION_PAGE_URL"]);
            }
        }
    }
    private function prepareMetaData()
    {
        $this->arResult["META_TAGS"] = [];

        if ($this->arParams["SET_TITLE"] === 'Y') {
            $this->arResult["META_TAGS"]["TITLE"] = (
                (string) ($this->arResult["IPROPERTY_VALUES"]["ELEMENT_PAGE_TITLE"] ?? '') !== ''
                ? $this->arResult["IPROPERTY_VALUES"]["ELEMENT_PAGE_TITLE"]
                : $this->arResult["NAME"]
            );
        }

        if ($this->arParams["SET_BROWSER_TITLE"] === 'Y') {
            $browserTitle = Collection::firstNotEmpty(
                $this->arResult["PROPERTIES"],
                array($this->arParams["BROWSER_TITLE"], "VALUE")
                ,
                $this->arResult,
                $this->arParams["BROWSER_TITLE"]
                ,
                $this->arResult["IPROPERTY_VALUES"],
                "ELEMENT_META_TITLE"
            );
            $this->arResult["META_TAGS"]["BROWSER_TITLE"] = (
                is_array($browserTitle)
                ? implode(" ", $browserTitle)
                : $browserTitle
            );
            unset($browserTitle);
        }
        if ($this->arParams["SET_META_KEYWORDS"] === 'Y') {
            $metaKeywords = Collection::firstNotEmpty(
                $this->arResult["PROPERTIES"],
                array($this->arParams["META_KEYWORDS"], "VALUE")
                ,
                $this->arResult["IPROPERTY_VALUES"],
                "ELEMENT_META_KEYWORDS"
            );
            $this->arResult["META_TAGS"]["KEYWORDS"] = (
                is_array($metaKeywords)
                ? implode(" ", $metaKeywords)
                : $metaKeywords
            );
            unset($metaKeywords);
        }
        if ($this->arParams["SET_META_DESCRIPTION"] === 'Y') {
            $metaDescription = Collection::firstNotEmpty(
                $this->arResult["PROPERTIES"],
                array($this->arParams["META_DESCRIPTION"], "VALUE")
                ,
                $this->arResult["IPROPERTY_VALUES"],
                "ELEMENT_META_DESCRIPTION"
            );
            $this->arResult["META_TAGS"]["DESCRIPTION"] = (
                is_array($metaDescription)
                ? implode(" ", $metaDescription)
                : $metaDescription
            );
            unset($metaDescription);
        }

    }

    private function addMetaData()
    {
        $arTitleOptions = [];
        if (isset($this->arParams['SET_CANONICAL_URL']) && $this->arParams['SET_CANONICAL_URL'] === 'Y' && $this->arResult["CANONICAL_PAGE_URL"]) {
            $this->getApplication()->SetPageProperty('canonical', $this->arResult["CANONICAL_PAGE_URL"]);
        }

        if ($this->arParams["SET_TITLE"])
            $this->getApplication()->SetTitle($this->arResult["META_TAGS"]["TITLE"], $arTitleOptions);

        if ($this->arParams["SET_BROWSER_TITLE"] === 'Y') {
            if ($this->arResult["META_TAGS"]["BROWSER_TITLE"] !== '')
                $this->getApplication()->SetPageProperty("title", $this->arResult["META_TAGS"]["BROWSER_TITLE"], $arTitleOptions);
        }

        if ($this->arParams["SET_META_KEYWORDS"] === 'Y') {
            if ($this->arResult["META_TAGS"]["KEYWORDS"] !== '')
                $this->getApplication()->SetPageProperty("keywords", $this->arResult["META_TAGS"]["KEYWORDS"], $arTitleOptions);
        }

        if ($this->arParams["SET_META_DESCRIPTION"] === 'Y') {
            if ($this->arResult["META_TAGS"]["DESCRIPTION"] !== '')
                $this->getApplication()->SetPageProperty("description", $this->arResult["META_TAGS"]["DESCRIPTION"], $arTitleOptions);
        }
    }
    private function process404($message = '')
    {
        \Bitrix\Iblock\Component\Tools::process404(
            $message,
            $this->arParams["SET_STATUS_404"],
            $this->arParams["SET_STATUS_404"],
            $this->arParams["SHOW_404"],
            $this->arParams["FILE_404"]
        );
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