<?

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Context;
use Bitrix\Main\Config\Option;

// TODO: Rework!
Loader::includeModule('welpodron.reviews');

class WelpodronElementRating extends CBitrixComponent
{
    const MODULE_ID = "welpodron.reviews";

    public function executeComponent()
    {
        // Кэширование данного компонента отключено по-умолчанию
        $this->arResult = $this->getRating();

        $this->includeComponentTemplate();

        return $this->arResult;
    }

    public function onPrepareComponentParams($arParams)
    {
        if (!Loader::includeModule('iblock')) {
            return [];
        }

        // $arParams['ELEMENT_ID'] = intval($arParams['ELEMENT_ID']);
        $arParams['ELEMENT_ARTIKUL'] = $arParams['ELEMENT_ARTIKUL'];
        $arParams['IBLOCK_ID'] = intval(Option::get(self::MODULE_ID, 'IBLOCK_ID'));

        $arParams['CACHE_TYPE'] = "N";
        $arParams['CACHE_TIME'] = "0";
        $arParams['CACHE_GROUPS'] = "N";

        return $arParams;
    }

    protected function getRating()
    {
        if ($this->arParams['IBLOCK_ID'] > 0 /* && $this->arParams['ELEMENT_ID'] > 0 */) {
            $arFilter = [
                'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
                'SITE_ID' => Context::getCurrent()->getSite(),
                'CHECK_PERMISSIONS' => 'N',
                'ACTIVE' => 'Y',
                // 'PROPERTY_element' => $this->arParams['ELEMENT_ID']
                'PROPERTY_artikul' => $this->arParams['ELEMENT_ARTIKUL']
            ];
            $arOrder = [];
            $arGroup = ['PROPERTY_rating'];
            $arNav = false;
            $arSelect = ['IBLOCK_ID', 'ID', 'PROPERTY_rating'];

            $dbElements = CIBlockElement::GetList($arOrder, $arFilter, $arGroup, $arNav, $arSelect);

            $totalRatingsAmount = 0; // Можно использовать CIBlockElement с параметром подсчета элементов вместо отдельной переменной
            $totalRatingsSum = 0;

            $arRatingsGroups = [
                '5' => 0,
                '4' => 0,
                '3' => 0,
                '2' => 0,
                '1' => 0
            ];

            while ($arObj = $dbElements->Fetch()) {
                $totalRatingsAmount += intval($arObj['CNT']);
                $totalRatingsSum += intval($arObj['PROPERTY_RATING_VALUE']) * intval($arObj['CNT']);
                $arRatingsGroups[$arObj['PROPERTY_RATING_VALUE']] = $arRatingsGroups[$arObj['PROPERTY_RATING_VALUE']] + intval($arObj['CNT']);
            }

            $ratingResult = null;

            if ($totalRatingsAmount && $totalRatingsSum) {
                $ratingResult = round($totalRatingsSum / $totalRatingsAmount, 1);
            }

            return [
                'RATINGS_BY_GROUPS' => $arRatingsGroups,
                'RATINGS_TOTAL_AMOUNT' => $totalRatingsAmount,
                'RATINGS_TOTAL_SUM' => $totalRatingsSum,
                'RATING_VALUE_CALCULATED' => $ratingResult
            ];
        }
    }
}
