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

$arPropsCodes = [];
$arPropsIds = [];

$dbProps = PropertyTable::getList([
    'select' => ['NAME', 'ID', 'CODE'],
    'filter' => [
        '=IBLOCK_ID' => $arCurrentValues['IBLOCK_ID'] ?? 0
    ],
])->fetchAll();

foreach ($dbProps as $arProp) {
    $arPropsIds[$arProp['ID']] = '[' . $arProp['ID'] . '] ' . $arProp['NAME'];
    if ($arProp['CODE']) {
        $arPropsCodes[$arProp['CODE']] = '[' . $arProp['CODE'] . '] ' . $arProp['NAME'];
    }
}

$arComponentParameters = [
    'GROUPS' => [
        'PAGER_SETTINGS' => [
            'NAME' => 'Настройки постраничной навигации',
        ],
        'FILTER_SETTINGS' => [
            'NAME' => 'Настройки фильтрации',
        ],
    ],
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
        'PRODUCT_NUMBER' => [
            'PARENT' => 'DATA_SOURCE',
            'NAME' => 'Артикул элемента',
            'TYPE' => 'STRING',
        ],
        'PROPERTY_PRODUCT_NUMBER_ID' => [
            'PARENT' => 'DATA_SOURCE',
            'NAME' => 'Id свойства артикул элемента из инфоблока отзывов',
            'TYPE' => 'LIST',
            "SIZE" => 10,
            'VALUES' => $arPropsIds,
        ],
        'PROPERTY_RATING_CODE' => [
            'PARENT' => 'DATA_SOURCE',
            'NAME' => 'Код свойства из инфоблоков отзывов, которое хранит рейтинг отзыва',
            'TYPE' => 'LIST',
            "SIZE" => 10,
            'VALUES' => $arPropsCodes,
        ],
        'PROPERTY_IMAGES_CODE' => [
            'PARENT' => 'DATA_SOURCE',
            'NAME' => 'Код свойства из инфоблоков отзывов, которое хранит изображения отзыва',
            'TYPE' => 'LIST',
            "SIZE" => 10,
            'VALUES' => $arPropsCodes,
        ],
        'USE_PAGER' => [
            'PARENT' => 'PAGER_SETTINGS',
            'NAME' => 'Использовать постраничную навигацию',
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'Y',
            'REFRESH' => 'Y'
        ],
        'USE_FILTER' => [
            'PARENT' => 'FILTER_SETTINGS',
            'NAME' => 'Использовать фильтрацию',
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'Y',
            'REFRESH' => 'Y'
        ],
    ]
];

if ($arCurrentValues['USE_FILTER'] == 'Y') {
    $arComponentParameters['PARAMETERS']['FILTER_QUERY_PARAM'] = [
        'PARENT' => 'FILTER_SETTINGS',
        'NAME' => 'Query параметр фильтра',
        'TYPE' => 'STRING',
        'DEFAULT' => 'filter',
    ];
}


if ($arCurrentValues['USE_PAGER'] == 'Y') {
    $arComponentParameters['PARAMETERS']['PAGER_COUNT'] = [
        'PARENT' => 'PAGER_SETTINGS',
        'NAME' => 'Количество элементов на странице',
        'TYPE' => 'STRING',
        'DEFAULT' => '5',
    ];
    $arComponentParameters['PARAMETERS']['CURRENT_PAGE_QUERY_PARAM'] = [
        'PARENT' => 'PAGER_SETTINGS',
        'NAME' => 'Query параметр текущей страницы',
        'TYPE' => 'STRING',
        'DEFAULT' => 'page',
    ];
}
