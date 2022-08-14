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

foreach ($arResult['ITEMS'] as &$arItem) {
    foreach ($arItem['PROPS']['images']['VALUE'] as &$arImage) {
        $arSmallImage = CFile::ResizeImageGet(
            $arImage['ID'],
            ['width' => 200, 'height' => 200],
            BX_RESIZE_IMAGE_PROPORTIONAL,
            true,
            []
        );

        $arImage['SRC'] = $arSmallImage['src'];
        $arImage['WIDTH'] = $arSmallImage['width'];
        $arImage['HEIGHT'] = $arSmallImage['height'];
        $arImage['SIZE'] = $arSmallImage['size'];
    }

    unset($arImage);
}

unset($arItem);
