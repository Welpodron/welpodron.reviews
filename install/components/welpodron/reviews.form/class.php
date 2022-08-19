<?

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\UrlManager;

// TODO: Rework!
Loader::includeModule('welpodron.reviews');

class WelpodronReviewsForm extends CBitrixComponent
{
    const MODULE_ID = "welpodron.reviews";

    public function executeComponent()
    {
        // Кэширование данного компонента отключено по-умолчанию
        $this->arResult = $this->getParams();

        $this->includeComponentTemplate();

        return $this->arResult;
    }

    public function onPrepareComponentParams($arParams)
    {
        if (!Loader::includeModule('iblock')) {
            return [];
        }

        $arParams['ELEMENT_ID'] = intval($arParams['ELEMENT_ID']);
        $arParams['CACHE_TYPE'] = "N";
        $arParams['CACHE_TIME'] = "0";
        $arParams['CACHE_GROUPS'] = "N";

        return $arParams;
    }

    protected function getParams()
    {
        // По какой-то причине здесь нужно указывать id модуля через двоеточие
        $MODULE_ID = "welpodron:reviews";
        $CONTROLLER = "receiver";
        $CONTROLLER_ACTION = "save";
        $FORM_ACTION_URL = UrlManager::getInstance()->create($MODULE_ID . '.' . $CONTROLLER . '.' . $CONTROLLER_ACTION);

        return [
            'FORM_ID' => 'form_' . md5(uniqid('', false)),
            'DIALOG_ID' => 'dialog_' . md5(uniqid('', false)),
            'ACTION_URL' => $FORM_ACTION_URL,
            'BX_JS_ACTION' => 'welpodron:reviews.receiver.save',
            'MAX_FILES_ALLOWED' => intval(Option::get(self::MODULE_ID, 'MAX_FILES_AMOUNT')),
            'MAX_FILE_SIZE_MB' => intval(Option::get(self::MODULE_ID, 'MAX_FILE_SIZE')),
            'MAX_FILE_SIZE_BYTES' => intval(Option::get(self::MODULE_ID, 'MAX_FILE_SIZE')) * 1024 * 1024,
        ];
    }
}
