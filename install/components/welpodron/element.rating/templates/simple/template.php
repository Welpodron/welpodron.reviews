<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

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
/** @var WelpodronReviewsList $component */

// FUCKING css CACHE FIX 
?>

<link href="<?= $templateFolder . "/style.css" ?>" type="text/css" rel="stylesheet" />

<div class="rating-stars-container">
    <div class="rating-stars">
        <div class="rating-stars-current" style="width:calc(<?= $arResult['RATING_VALUE_CALCULATED'] ? $arResult['RATING_VALUE_CALCULATED'] : 0 ?> * 20%)"></div>
    </div>
    <? if ($arResult['RATINGS_TOTAL_AMOUNT']) : ?>
        <span>(<?= $arResult['RATINGS_TOTAL_AMOUNT'] ?>)</span>
    <? endif; ?>
</div>