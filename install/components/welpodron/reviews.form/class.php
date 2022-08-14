<?

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;

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

        return $arParams;
    }

    protected function getParams()
    {
        return ['FORM_ID' => 'form_' . md5(uniqid('', false)), 'JS_ACTION' => 'welpodron:reviews.receiver.save', 'MAX_FILES_ALLOWED' => Option::get(self::MODULE_ID, 'MAX_FILES_AMOUNT')];
    }
}
