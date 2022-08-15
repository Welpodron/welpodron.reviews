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
?>

<p>Текущий рейтинг: <?= $arResult['RATING_VALUE_CALCULATED'] ?></p>
<p>Рейтинг на основе: <?= $arResult['RATINGS_TOTAL_AMOUNT'] ?> отзывов</p>
<p>5: <?= $arResult['RATINGS_BY_GROUPS']['5'] ?></p>
<p>4: <?= $arResult['RATINGS_BY_GROUPS']['4'] ?></p>
<p>3: <?= $arResult['RATINGS_BY_GROUPS']['3'] ?></p>
<p>2: <?= $arResult['RATINGS_BY_GROUPS']['2'] ?></p>
<p>1: <?= $arResult['RATINGS_BY_GROUPS']['1'] ?></p>