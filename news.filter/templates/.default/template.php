<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
  die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
$this->setFrameMode(true);
?>
<div class="lubaro-news-filter-form-wrapper">
  <form id="lubaroNewsFilter" name="<?= $arResult["FILTER_NAME"] . "_form" ?>" action="
    <?= $arResult["FORM_ACTION"] ?>" method="get">
    <div class="inputs-block">
      <div>
        <lable><?= GetMessage("LUBARO_IBLOCK_FIELD_SECTION_ID") ?>:</lable>
        <select name="<?= $arResult["FILTER_NAME"] . "[SECTION_ID]" ?>">
          <option><?= GetMessage("LUBARO_SELECT_ALL_CATEGORIES") ?></option>
          <? foreach ($arResult["arrSection"] as $sec_id => $sec_name): ?>
            <option 
              value="<?= $sec_id ?>"
              <? if ($arResult["inputValues"]["section_id"] == $sec_id): ?>selected<? endif ?>
            >
              <?= $sec_name ?>
            </option>
          <? endforeach; ?>
        </select>
      </div>

      <div class="period-select-wrapper">
        <span><?= GetMessage("LUBARO_FILTER_DATE") ?>:</span>
          <input
            type="text"
            size="10"
            value="<?= $arResult["inputValues"]["preriod_start"] ?? '' ?>"
            name="<?= $arResult["FILTER_NAME"] . "[PERIOD][START]" ?>"
            placeholder="<?= GetMessage("LUBARO_INPUT_PERIOD_START") ?>"
            onclick="BX.calendar({node: this, field: this, bTime: false});"
          />&nbsp;&mdash;&nbsp;
          <input
            type="text"
            size="10"
            value="<?= $arResult["inputValues"]["preriod_end"] ?? '' ?>"
            name="<?= $arResult["FILTER_NAME"] . "[PERIOD][END]" ?>"
            placeholder="<?= GetMessage("LUBARO_INPUT_PERIOD_END") ?>"
            onclick="BX.calendar({node: this, field: this, bTime: false});"
          />
      </div>
    </div>
    <? if (sizeof($arParams["FILTER_DISPLAY_ERRORS"]) > 0): ?>
      <div class="filter-errors-wrapper">
      <? foreach ($arParams["FILTER_DISPLAY_ERRORS"] as $error): ?>
        <div class="filter-error"><?= $error ?></div>
      <? endforeach; ?>
      </div>
    <? endif ?>
    <div class="buttons-block">
      <div class="reset-filter">
        <a href="<?= $APPLICATION->GetCurPage() ?>">
          <?= GetMessage("LUBARO_FILTER_RESET") ?>
        </a>
      </div>
      <div class="set-filter">
        <button type="submit" form="lubaroNewsFilter"><?= GetMessage("LUBARO_FILTER_APPLY") ?></button>
      </div>
    </div>
  </form>
</div>