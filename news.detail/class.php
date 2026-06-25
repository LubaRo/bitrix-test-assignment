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
            $this->prepareData();
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
        $this->arParams["CACHE_TIME"] ??= '36000000';
        $this->arParams["CACHE_TYPE"] ??= 'A';

        $this->arParams["ACTIVE_DATE_FORMAT"] ??= $this->getDB()->DateFormatToPHP(\CSite::GetDateFormat("SHORT"));
    }


    private function prepareData()
    {
        //Handle case when ELEMENT_CODE used
        // if ($this->arParams["ELEMENT_ID"] <= 0) {
        //     $this->arParams["ELEMENT_ID"] = CIBlockFindTools::GetElementID(
        //         $this->arParams["ELEMENT_ID"],
        //         $this->arParams["~ELEMENT_CODE"],
        //         false,
        //         false,
        //         $arFilter
        //     );
        // }

        $arFilter = [
            'ID' => $this->arParams["ELEMENT_ID"],
            'IBLOCK_ID' => $this->arParams["IBLOCK_ID"],
            'IBLOCK_LID' => SITE_ID,
            'IBLOCK_ACTIVE' => 'Y',
            'ACTIVE' => 'Y',
            'CHECK_PERMISSIONS' => 'Y',
            'SHOW_HISTORY' => 'N',
        ];
        if ($this->arParams['CHECK_DATES']) {
            $arFilter['ACTIVE_DATE'] = 'Y';
        }
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

                $this->includeComponentTemplate();
            } else {
                $this->abortResultCache();
                $this->process404();
            }
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