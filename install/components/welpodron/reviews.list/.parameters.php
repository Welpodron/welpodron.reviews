<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

// TODO: Добавить ресайз из medialib.element

$arSortDirections = [
    'asc' => 'По возрастанию',
    'desc' => 'По убыванию'
];
$arSortFields = [
    'created' => 'По времени создания',
    'property_rating' => 'По рейтингу',
];

$arComponentParameters = [
    'PARAMETERS' => [
        'AJAX_MODE' => [],
        'ELEMENTS_PER_PAGE' => [
            'PARENT' => 'BASE',
            'NAME' => 'Количетсво элементов на страницу',
            'TYPE' => 'TEXT',
            'DEFAULT' => 3,
        ],
        'ELEMENT_ID' => [
            'PARENT' => 'DATA_SOURCE',
            'NAME' => 'ID Элемента',
            'TYPE' => 'TEXT',
        ],
        'FIRST_SORT_FIELD' => [
            'PARENT' => 'DATA_SOURCE',
            'NAME' => 'Первое поле для сортировки',
            'TYPE' => 'LIST',
            'DEFAULT' => 'created',
            'VALUES' => $arSortFields,
            'ADDITIONAL_VALUES' => 'Y'
        ],
        'FIRST_SORT_ORDER' => [
            'PARENT' => 'DATA_SOURCE',
            'NAME' => 'Направление первого поля для сортировки',
            'TYPE' => 'LIST',
            'DEFAULT' => 'desc',
            'VALUES' => $arSortDirections
        ],
    ]
];
