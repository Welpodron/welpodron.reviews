<?
if (!defined('B_PROLOG_INCLUDED') || constant('B_PROLOG_INCLUDED') !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\UserFieldTable;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\Config\Option;

if (!Loader::includeModule('iblock')) {
    return;
}

$moduleId = 'welpodron.reviews';

if (!Loader::includeModule($moduleId)) {
    return;
}

$arIblocks = ['-' => '-'];

$dbIblocks = IblockTable::getList([
    'select' => ['ID', 'NAME'],
    'filter' => ['ACTIVE' => 'Y', 'ID' => explode(',', Option::get($moduleId, 'RESTRICTIONS_IBLOCK_ID'))],
])->fetchAll();

foreach ($dbIblocks as $arIblock) {
    $arIblocks[$arIblock['ID']] = '[' . $arIblock['ID'] . '] ' . $arIblock['NAME'];
}

$arProps = [];

$dbProps = PropertyTable::getList([
    'select' => ['NAME', 'ID'],
    'filter' => [
        '=IBLOCK_ID' => $arCurrentValues['IBLOCK_ID'] ?? 0
    ],
])->fetchAll();

foreach ($dbProps as $arProp) {
    $arProps[$arProp['ID']] = '[' . $arProp['ID'] . '] ' . $arProp['NAME'];
}

$arComponentParameters = [
    'PARAMETERS' => [
        'CACHE_TIME' => ['DEFAULT' => 36000],
        'USE_TEMPLATE' => [
            'PARENT' => 'BASE',
            'NAME' => 'Использовать шаблон компонента',
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'Y',
        ],
        'IBLOCK_ID' => [
            'PARENT' => 'DATA_SOURCE',
            'NAME' => 'Инфоблок отзывов',
            'TYPE' => 'LIST',
            'VALUES' => $arIblocks,
            'REFRESH' => 'Y'
        ],
        'USE_TEMPLATE' => [
            'PARENT' => 'BASE',
            'NAME' => 'Использовать шаблон компонента',
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'Y',
        ],
        'PRODUCT_NUMBER' => [
            'PARENT' => 'DATA_SOURCE',
            'NAME' => 'Артикул элемента',
            'TYPE' => 'STRING',
        ],
        'PROPERTY_RATING_ID' => [
            'PARENT' => 'DATA_SOURCE',
            'NAME' => 'Id свойства рейтинг элемента из инфоблока отзывов',
            'TYPE' => 'LIST',
            "SIZE" => 10,
            'VALUES' => $arProps,
        ],
        'PROPERTY_PRODUCT_NUMBER_ID' => [
            'PARENT' => 'DATA_SOURCE',
            'NAME' => 'Id свойства артикул элемента из инфоблока отзывов',
            'TYPE' => 'LIST',
            "SIZE" => 10,
            'VALUES' => $arProps,
        ],
    ]
];
