<?

if (!defined('B_PROLOG_INCLUDED') || constant('B_PROLOG_INCLUDED') !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Context;
use Bitrix\Main\Config\Option;

use Bitrix\Iblock\ElementPropertyTable;
use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\FileTable;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Expression;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Engine\CurrentUser;

class WelpodronElementRating extends CBitrixComponent
{
    const MODULE_ID = "welpodron.reviews";

    public function executeComponent()
    {
        if ($this->startResultCache($this->arParams['CACHE_TIME'] ? $this->arParams['CACHE_TIME'] : false)) {
            $this->arResult = $this->getRating();

            if (!($this->arParams['PRODUCT_NUMBER']) || $this->arParams['PROPERTY_RATING_ID'] <= 0 || $this->arParams['PROPERTY_PRODUCT_NUMBER_ID'] <= 0) {
                $this->AbortResultCache();
            }

            if (!$this->arResult) {
                $this->AbortResultCache();
            }

            if (!($this->arParams["USE_TEMPLATE"]) && $this->arParams["CACHE_TYPE"] != "N" && $this->arParams['CACHE_TIME']) {
                $this->AbortResultCache();
            }

            if ($this->arParams["USE_TEMPLATE"]) {
                $this->includeComponentTemplate();
            }
        }

        return $this->arResult;
    }

    public function onPrepareComponentParams($arParams)
    {
        if (!Loader::includeModule('iblock')) {
            return [];
        }

        if (!$arParams['PRODUCT_NUMBER']) {
            return [];
        }

        $arParams['PRODUCT_NUMBER'] = strval($arParams['PRODUCT_NUMBER']);
        //! Id свойства рейтинг элемента из инфоблока ОТЗЫВОВ!
        $arParams['PROPERTY_RATING_ID'] = intval($arParams['PROPERTY_RATING_ID']);

        if ($arParams['PROPERTY_RATING_ID'] <= 0) {
            return [];
        }

        //! Id свойства артикул элемента из инфоблока ОТЗЫВОВ!
        $arParams['PROPERTY_PRODUCT_NUMBER_ID'] = intval($arParams['PROPERTY_PRODUCT_NUMBER_ID']);

        if ($arParams['PROPERTY_PRODUCT_NUMBER_ID'] <= 0) {
            return [];
        }

        return $arParams;
    }

    protected function getRating()
    {
        try {
            if (!Loader::includeModule('iblock')) {
                throw new Exception('Не удалось подключить модуль инфоблоков');
            }

            if ($this->arParams['PRODUCT_NUMBER'] && $this->arParams['PROPERTY_RATING_ID'] > 0 && $this->arParams['PROPERTY_PRODUCT_NUMBER_ID'] > 0) {
                $query = ElementPropertyTable::query();
                $query->setSelect([
                    'RATING',
                    'CNT'
                ]);
                $query->setGroup([
                    'IBLOCK_PROPERTY_ID'
                ]);
                $query->registerRuntimeField(
                    new ExpressionField(
                        'RATING',
                        'AVG(%s)',
                        'VALUE'
                    )
                );
                $query->registerRuntimeField(
                    new ExpressionField(
                        'CNT',
                        'COUNT(%s)',
                        'VALUE'
                    )
                );
                $query->registerRuntimeField(
                    new Reference(
                        'ep_t',
                        ElementPropertyTable::class,
                        Join::on('this.IBLOCK_ELEMENT_ID', 'ref.IBLOCK_ELEMENT_ID')
                    )
                );
                $query->registerRuntimeField(
                    new Reference(
                        'e_t',
                        ElementTable::class,
                        Join::on('this.IBLOCK_ELEMENT_ID', 'ref.ID')
                    )
                );
                $query->where("e_t.ACTIVE", 'Y');
                $query->where("ep_t.VALUE", $this->arParams['PRODUCT_NUMBER']);
                //! Id свойства артикул элемента из инфоблока ОТЗЫВОВ!
                $query->where("ep_t.IBLOCK_PROPERTY_ID", $this->arParams['PROPERTY_PRODUCT_NUMBER_ID']);
                //! Id свойства рейтинг элемента из инфоблока ОТЗЫВОВ!
                $query->where('IBLOCK_PROPERTY_ID', $this->arParams['PROPERTY_RATING_ID']);

                if (!($this->arParams["USE_TEMPLATE"]) && $this->arParams["CACHE_TYPE"] != "N" && $this->arParams['CACHE_TIME']) {
                    $query->setCacheTtl($this->arParams['CACHE_TIME']);
                    $query->cacheJoins(true);
                }

                $dbResult = $query->exec()->fetch();

                if ($dbResult) {
                    return [
                        'RATING' => floatval($dbResult['RATING']),
                        'CNT' => intval($dbResult['CNT'])
                    ];
                }
            }
        } catch (\Throwable $th) {
            if (CurrentUser::get()->isAdmin()) {
                $this->__showError($th->getMessage(), $th->getCode());
                echo '<br><br>';
                $this->__showError($th->getTraceAsString());
            }
        }

        return [];
    }
}
