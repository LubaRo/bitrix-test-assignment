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
<div class="lubaro-news-detail">
	<? if (is_array($arResult["DETAIL_PICTURE"])): ?>
		<img class="detail_picture" border="0" src="<?= $arResult["DETAIL_PICTURE"]["SRC"] ?>"
			width="<?= $arResult["DETAIL_PICTURE"]["WIDTH"] ?>" height="<?= $arResult["DETAIL_PICTURE"]["HEIGHT"] ?>"
			alt="<?= $arResult["DETAIL_PICTURE"]["ALT"] ?>" title="<?= $arResult["DETAIL_PICTURE"]["TITLE"] ?>" />
	<? endif ?>
	<div class="top-block">
		<div>
			<? if ($arResult["DISPLAY_ACTIVE_FROM"]): ?>
				<span class="news-date-time">
					<?= $arResult["DISPLAY_ACTIVE_FROM"] ?>
				</span>
			<? endif; ?>
		</div>
		<div>
			<? if (sizeof($arResult["SECTION_LIST"]) > 0): ?>
				<? foreach ($arResult["SECTION_LIST"] as $i => $section_name): ?>
					<span class="lubaro-news-section">
						<?= $section_name; ?>
					</span>
				<? endforeach; ?>
			<? endif ?>
		</div>
	</div>
	<h3><?= $arResult["NAME"] ?></h3>
	<? echo $arResult["DETAIL_TEXT"]; ?>
	<div style="clear:both"></div>
	<br />

</div>