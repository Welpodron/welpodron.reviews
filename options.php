<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Context;
use Bitrix\Main\Config\Option;

$moduleId = 'welpodron.reviews'; //обязательно, иначе права доступа не работают!

Loader::includeModule($moduleId);
Loader::includeModule("iblock");

$request = Context::getCurrent()->getRequest();

$dbIblocks = CIBlock::GetList([], ['TYPE' => welpodron_reviews::IBLOCK_TYPE]);

$arIblocks['-1'] = 'Выберете инфоблок';

while ($arIblock = $dbIblocks->Fetch()) {
    $arIblocks[$arIblock['ID']] = '[' . $arIblock['ID'] . '] ' . $arIblock["NAME"];
}

#Описание опций

$aTabs = [
    [
        'DIV' => 'edit1',
        'TAB' => 'Основные настройки',
        'OPTIONS' => [
            [
                'IBLOCK_ID',
                'Инфоблок:',
                Option::get($moduleId, 'IBLOCK_ID', array_key_first($arIblocks)), // selected value
                [
                    'selectbox',
                    $arIblocks
                ]
            ],
            [
                'MAX_FILES_AMOUNT',
                'Максимальное количество файлов за загрузку:',
                Option::get($moduleId, 'MAX_FILES_AMOUNT', '3'),
                ['text']
            ],
            [
                'MAX_FILE_SIZE',
                'Максимальный размер одного файла в МБ:',
                Option::get($moduleId, 'MAX_FILE_SIZE', '5'),
                ['text']
            ],
            [
                'BANNED_SYMBOLS',
                'Список запрещенных символов (через запятую):',
                Option::get($moduleId, 'BANNED_SYMBOLS', '<,>,&,*,^,%,$'),
                ['textarea']
            ]
        ]
    ]
];
#Сохранение

if ($request->isPost() && $request['save'] && check_bitrix_sessid()) {
    foreach ($aTabs as $aTab) {
        __AdmSettingsSaveOptions($moduleId, $aTab['OPTIONS']);
    }

    LocalRedirect($APPLICATION->GetCurPage() . '?lang=' . LANGUAGE_ID . '&mid_menu=1&mid=' . urlencode($moduleId) .
        '&tabControl_active_tab=' . urlencode($request['tabControl_active_tab']));
}

#Визуальный вывод

$tabControl = new CAdminTabControl('tabControl', $aTabs);
?>
<form method='post' name='welpodron_form_settings'>
    <? $tabControl->Begin(); ?>
    <? foreach ($aTabs as $aTab) : ?>
        <?
        $tabControl->BeginNextTab();
        __AdmSettingsDrawList($moduleId, $aTab['OPTIONS']);
        ?>
    <? endforeach; ?>
    <? $tabControl->Buttons(['btnApply' => false, 'btnCancel' => false, 'btnSaveAndAdd' => false]); ?>
    <?= bitrix_sessid_post(); ?>
    <? $tabControl->End(); ?>
</form>