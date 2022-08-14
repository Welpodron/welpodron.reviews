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


$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$postRating = $request->get('rating');
$postOrder = $request->get('order') ? $request->get('order') : $arParams['FIRST_SORT_FIELD'] . ':' . $arParams['FIRST_SORT_ORDER'];
?>

<form onchange="BX.ajax.submit(this);" method="POST">
    <input name="reviewsamount" value="0" type="hidden" />
    <label>
        Сортировать по:
        <select name="order">
            <option <?= $postOrder == "property_rating:desc" ? 'selected' : '' ?> value="property_rating:desc">Оценка по убыванию</option>
            <option <?= $postOrder == "property_rating:asc" ? 'selected' : '' ?> value="property_rating:asc">Оценка по возрастанию</option>
            <option <?= $postOrder == "created:desc" ? 'selected' : '' ?> value="created:desc">Новые</option>
            <option <?= $postOrder == "created:asc" ? 'selected' : '' ?> value="created:asc">Старые</option>
        </select>
    </label>
    <label>
        Оценка:
        <select name="rating">
            <option value="">Любая</option>
            <option <?= $postRating == "5" ? 'selected' : '' ?> value="5">5 звезд</option>
            <option <?= $postRating == "4" ? 'selected' : '' ?> value="4">4 звезды</option>
            <option <?= $postRating == "3" ? 'selected' : '' ?> value="3">3 звезды</option>
            <option <?= $postRating == "2" ? 'selected' : '' ?> value="2">2 звезды</option>
            <option <?= $postRating == "1" ? 'selected' : '' ?> value="1">1 звезда</option>
        </select>
    </label>
    <label>
        С фото:
        <input <?= $request->get('images') == "1" ? 'checked' : '' ?> type="checkbox" name="images" value="1" />
    </label>
</form>

<? foreach ($arResult['ITEMS'] as $arItem) : ?>
    <?
    $this->AddEditAction(
        $arItem['FIELDS']['ID'],
        $arItem['ACTIONS']['EDIT_LINK'],
        CIBlock::GetArrayByID($arItem['FIELDS']['IBLOCK_ID'], 'ELEMENT_EDIT')
    );
    $this->AddDeleteAction(
        $arItem['FIELDS']['ID'],
        $arItem['ACTIONS']['DELETE_LINK'],
        CIBlock::GetArrayByID($arItem['FIELDS']['IBLOCK_ID'], 'ELEMENT_DELETE'),
        ['CONFIRM' => 'Будет удалена вся информация, связанная с этой записью. Продолжить?']
    );
    ?>
    <div id="<?= $this->GetEditAreaId($arItem['FIELDS']['ID']); ?>">
        <p>Оценка: <?= $arItem['PROPS']['rating']['VALUE'] ?></p>
        <p>Автор: <?= $arItem['PROPS']['author']['VALUE'] ?></p>
        <p>Дата: <?= $arItem['FIELDS']['DATE_CREATE'] ?></p>
        <? if ($arItem['PROPS']['advantages']['VALUE']) : ?>
            <p>Достоинства: <?= $arItem['PROPS']['advantages']['VALUE'] ?></p>
        <? endif; ?>
        <? if ($arItem['PROPS']['disadvantages']['VALUE']) : ?>
            <p>Недостатки: <?= $arItem['PROPS']['disadvantages']['VALUE'] ?></p>
        <? endif; ?>
        <p>Комментарий: <?= $arItem['PROPS']['comment']['VALUE'] ?></p>
        <? if ($arItem['PROPS']['images']['VALUE']) : ?>
            <p>Изображения:
                <? foreach ($arItem['PROPS']['images']['VALUE'] as $reviewImage) : ?>
                    <img src="<?= $reviewImage['SRC'] ?>">
                <? endforeach; ?>
            </p>
        <? endif; ?>
    </div>
<? endforeach; ?>

<? if ($arResult['NAV_RESULT']["NavRecordCount"] > 1) : ?>
    <? if ($arResult['NAV_RESULT']["NavPageSize"] < $arResult['NAV_RESULT']["NavRecordCount"]) : ?>
        <form>
            <input name="order" value="<?= array_key_first($arResult['CURRENT_FILTER']['ORDER']) . ':' . $arResult['CURRENT_FILTER']['ORDER'][array_key_first($arResult['CURRENT_FILTER']['ORDER'])] ?>" type="hidden" />
            <input name="rating" value="<?= $arResult['CURRENT_FILTER']['FILTER']['PROPERTY_rating'] ?>" type="hidden" />
            <input name="images" value="<?= $arResult['CURRENT_FILTER']['FILTER']['PROPERTY_rating'] ?>" type="hidden" />
            <input name="reviewsamount" value="<?= $arResult['NAV_RESULT']["NavPageSize"] + intval($arParams['ELEMENTS_PER_PAGE']) ?>" type="hidden" />
            <button>Показать еще</button>
        </form>
    <? endif ?>
<? endif ?>