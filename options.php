<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Context;
use Bitrix\Main\Config\Option;

$moduleId = 'welpodron.reviews'; //обязательно, иначе права доступа не работают!

Loader::includeModule($moduleId);
Loader::includeModule("iblock");

$request = Context::getCurrent()->getRequest();
// welpodron_reviews::$DEFAULT_OPTIONS['WTF'] = 1;
// var_dump(welpodron_reviews::$DEFAULT_OPTIONS);

$dbIblocks = CIBlock::GetList([], ['TYPE' => welpodron_reviews::DEFAULT_IBLOCK_TYPE]);

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
                Option::get($moduleId, 'IBLOCK_ID'), // selected value
                [
                    'selectbox',
                    $arIblocks
                ]
            ],
            [
                'MAX_FILES_AMOUNT',
                'Максимальное количество файлов за загрузку:',
                Option::get($moduleId, 'MAX_FILES_AMOUNT'),
                ['text']
            ],
            [
                'MAX_FILE_SIZE',
                'Максимальный размер одного файла в МБ:',
                Option::get($moduleId, 'MAX_FILE_SIZE'),
                ['text']
            ],
            [
                'BANNED_SYMBOLS',
                'Список запрещенных символов (через запятую):',
                Option::get($moduleId, 'BANNED_SYMBOLS'),
                ['textarea']
            ]
        ]
    ],
    [
        'DIV' => 'edit2',
        'TAB' => 'Настройки уведомлений',
        'OPTIONS' => [
            [
                'USE_NOTIFY',
                'Отправлять сообщение об отзыве менеджеру сайта',
                Option::get($moduleId, 'USE_NOTIFY'),
                ['checkbox']
            ],
            [
                'NOTIFY_TYPE',
                'Тип почтового события',
                Option::get($moduleId, 'NOTIFY_TYPE'),
                ['text', 40]
            ],
            [
                'NOTIFY_EMAIL',
                'Email менеджера сайта',
                Option::get($moduleId, 'NOTIFY_EMAIL'),
                ['text', 40]
            ],
        ]
    ],
    [
        'DIV' => 'edit3',
        'TAB' => 'Настройки Google reCAPTCHA v2 (Бета)',
        'OPTIONS' => [
            [
                'USE_CAPTCHA',
                'Использовать Google reCAPTCHA v2',
                Option::get($moduleId, 'USE_CAPTCHA'),
                ['checkbox']
            ],
            [
                'GOOGLE_CAPTCHA_SECRET_KEY',
                'Секретный ключ',
                Option::get($moduleId, 'GOOGLE_CAPTCHA_SECRET_KEY'),
                ['text', 40]
            ],
            [
                'GOOGLE_CAPTCHA_PUBLIC_KEY',
                'Публичный ключ',
                Option::get($moduleId, 'GOOGLE_CAPTCHA_PUBLIC_KEY'),
                ['text', 40]
            ],
        ]
    ],
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