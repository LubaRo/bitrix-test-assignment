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
	<form id="lubaroNewsFilter" name="<? echo $arResult["FILTER_NAME"] . "_form" ?>" action="
		<? echo $arResult["FORM_ACTION"] ?>" method="get">
		<div class="inputs-block">
			<? foreach ($arResult["ITEMS"] as $arItem): ?>
				<? if (!array_key_exists("HIDDEN", $arItem)): ?>
					<div>
						<div>
							<?= $arItem["NAME"] ?>:
						</div>
						<div>
							<?= $arItem["INPUT"] ?>
						</div>
					</div>
				<? endif ?>
			<? endforeach; ?>
		</div>
		<div class="buttons-block">
			<div class="reset-filter">
				<a href="<? echo $APPLICATION->GetCurPage() ?>">Сбросить</a>
			</div>
			<div class="set-filter">
				<button type="submit" form="lubaroNewsFilter">Найти</button>
			</div>
		</div>
	</form>

</div>