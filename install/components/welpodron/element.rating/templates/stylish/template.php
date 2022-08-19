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

<? if ($arResult['RATINGS_TOTAL_AMOUNT']) : ?>
    <div class="element-rating">
        <p class="element-rating-calculated"><?= $arResult['RATING_VALUE_CALCULATED'] ?> <span class="element-rating-from">из 5</span></p>
        <div class="rating-stars">
            <div class="rating-stars-current" style="width:calc(<?= $arResult['RATING_VALUE_CALCULATED'] ?> * 20%)"></div>
        </div>
        <p class="element-rating-reviews-total">Рейтинг на основе <?= $arResult['RATINGS_TOTAL_AMOUNT'] ?> отзывов</p>
        <div class="element-rating-group">
            <span class="element-rating-group-counter">5</span>
            <div class="element-rating-group-line-progress">
                <div style="width: <?= (intval($arResult['RATINGS_BY_GROUPS']['5']) * 100 / intval($arResult['RATINGS_TOTAL_AMOUNT'])) ?>%" class="element-rating-group-line-progress-current"></div>
            </div>
        </div>
        <div class="element-rating-group">
            <span class="element-rating-group-counter">4</span>
            <div class="element-rating-group-line-progress">
                <div style="width: <?= (intval($arResult['RATINGS_BY_GROUPS']['4']) * 100 / intval($arResult['RATINGS_TOTAL_AMOUNT'])) ?>%" class="element-rating-group-line-progress-current"></div>
            </div>
        </div>
        <div class="element-rating-group">
            <span class="element-rating-group-counter">3</span>
            <div class="element-rating-group-line-progress">
                <div style="width: <?= (intval($arResult['RATINGS_BY_GROUPS']['3']) * 100 / intval($arResult['RATINGS_TOTAL_AMOUNT'])) ?>%" class="element-rating-group-line-progress-current"></div>
            </div>
        </div>
        <div class="element-rating-group">
            <span class="element-rating-group-counter">2</span>
            <div class="element-rating-group-line-progress">
                <div style="width: <?= (intval($arResult['RATINGS_BY_GROUPS']['2']) * 100 / intval($arResult['RATINGS_TOTAL_AMOUNT'])) ?>%" class="element-rating-group-line-progress-current"></div>
            </div>
        </div>
        <div class="element-rating-group">
            <span class="element-rating-group-counter">1</span>
            <div class="element-rating-group-line-progress">
                <div style="width: <?= (intval($arResult['RATINGS_BY_GROUPS']['1']) * 100 / intval($arResult['RATINGS_TOTAL_AMOUNT'])) ?>%" class="element-rating-group-line-progress-current"></div>
            </div>
        </div>
    </div>
<? endif; ?>