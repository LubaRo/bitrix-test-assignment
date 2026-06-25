<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
  die();
$this->setFrameMode(true);
?>
<?php
if ($arParams["USE_FILTER"] == "Y"):
  $APPLICATION->IncludeComponent(
    "lubaro:news.filter",
    "",
    [
      "NEWS_PERIOD" => $arResult['NEWS_PERIOD'],
      "FILTER_NAME" => $arParams["FILTER_NAME"],
      "IBLOCK_ID" => $arParams["IBLOCK_ID"],
      "CACHE_GROUPS" => "Y",
      "CACHE_TIME" => "36000000",
      "CACHE_TYPE" => "A",
    ],
    $component
  );
endif;
?>
<div class="news-list">
  <? if ($arParams["DISPLAY_TOP_PAGER"]): ?>
    <?= $arResult["NAV_STRING"] ?><br />
  <? endif; ?>
  <? if (empty($arResult["ITEMS"])): ?>
    <?= GetMessage('LUBARO_NEWS_EMPTY') ?>
  <? endif; ?>
  <? foreach ($arResult["ITEMS"] as $arItem): ?>
    <div class="news-item" id="<?= $this->GetEditAreaId($arItem['ID']); ?>">
      <? if ($arParams["DISPLAY_PICTURE"] != "N" && is_array($arItem["PREVIEW_PICTURE"])): ?>
        <a href="<?= $arItem["DETAIL_PAGE_URL"] ?>"><img class="preview_picture" border="0"
            src="<?= $arItem["PREVIEW_PICTURE"]["SRC"] ?>" width="<?= $arItem["PREVIEW_PICTURE"]["WIDTH"] ?>"
            height="<?= $arItem["PREVIEW_PICTURE"]["HEIGHT"] ?>" alt="<?= $arItem["PREVIEW_PICTURE"]["ALT"] ?>"
            title="<?= $arItem["PREVIEW_PICTURE"]["TITLE"] ?>" style="float:left" /></a>
      <? endif ?>

      <? if ($arParams["DISPLAY_NAME"] != "N" && $arItem["NAME"]): ?>
        <p><a href="<? echo $arItem["DETAIL_PAGE_URL"] ?>"><b><? echo $arItem["NAME"] ?></b></a></p>
      <? endif; ?>

      <? if ($arParams["DISPLAY_DATE"] != "N" && $arItem["DISPLAY_ACTIVE_FROM"]): ?>
        <p class="lubaro-news-date-time"><? echo $arItem["DISPLAY_ACTIVE_FROM"] ?></p>
      <? endif ?>

      <? if ($arParams["DISPLAY_PREVIEW_TEXT"] != "N" && $arItem["PREVIEW_TEXT"]): ?>
        <? echo $arItem["PREVIEW_TEXT"]; ?>
      <? endif; ?>
      <? if ($arParams["DISPLAY_PICTURE"] != "N" && is_array($arItem["PREVIEW_PICTURE"])): ?>
        <div style="clear:both"></div>
      <? endif ?>

      <? if (sizeof($arItem["SECTION_LIST"]) > 0): ?>
        <p class="lubaro-news-section-list">
          <? foreach ($arItem["SECTION_LIST"] as $i => $section_name): ?>
            <span class="lubaro-news-section">
              <?= $section_name; ?>
            </span>
          <? endforeach; ?>
        </p>
      <? endif ?>
    </div>
  <? endforeach; ?>
  <? if ($arParams["DISPLAY_BOTTOM_PAGER"]): ?>
    <br /><?= $arResult["NAV_STRING"] ?>
  <? endif; ?>
</div>