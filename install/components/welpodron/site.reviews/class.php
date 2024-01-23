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

class WelpodronSiteReviews extends CBitrixComponent
{
    const MODULE_ID = "welpodron.reviews";

    public function executeComponent()
    {
        $additionalCache = [];

        if ($this->arParams['USE_FILTER']) {
            $additionalCache['F'] = $this->getFilterArray();
        }

        if ($this->arParams['USE_PAGER']) {
            $additionalCache['N'] = $this->getNavigationArray();
        }

        if ($this->startResultCache($this->arParams['CACHE_TIME'] ? $this->arParams['CACHE_TIME'] : false, $additionalCache)) {
            $this->arResult = $this->getReviews();

            if ($this->arParams['IBLOCK_ID'] <= 0) {
                $this->AbortResultCache();
            }

            if ($additionalCache['N'] && $additionalCache['N']['CURRENT_PAGE'] && $this->arResult['PAGINATION'] && $this->arResult['PAGINATION']['TOTAL_PAGES']) {
                if ($additionalCache['N']['CURRENT_PAGE'] > $this->arResult['PAGINATION']['TOTAL_PAGES']) {
                    //! На самом деле тут неплохо было поменять ключи доп кэша а не просто запрещать его создавать 
                    $this->AbortResultCache();
                }
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

    protected function getFilterArray()
    {
        if (!$this->arParams['USE_FILTER']) {
            return;
        }

        $request = Bitrix\Main\Context::getCurrent()->getRequest();

        $filterRating = 0;
        $filterAr = $request->getQuery($this->arParams['FILTER_QUERY_PARAM']);

        $filterResult = [];

        // https://example.com/?filter[rating]=4&filter[img]=true
        if (is_array($filterAr)) {
            if ($this->arParams['PROPERTY_RATING_CODE']) {
                $filterRating = intval($filterAr[$this->arParams['PROPERTY_RATING_CODE']]);
                if ($filterRating <= 0 || $filterRating > 5) {
                    $filterRating = 0;
                }

                $filterResult[$this->arParams['PROPERTY_RATING_CODE']] = $filterRating;
            }


            // if ($filterAr['img'] === 'true') {
            //     $filterImg = true;
            // }
        }

        return $filterResult;
    }

    protected function getNavigationArray()
    {
        if (!$this->arParams['USE_PAGER']) {
            return;
        }

        $request = Bitrix\Main\Context::getCurrent()->getRequest();

        $navigationResult = [];

        $currentPage = intval($request->getQuery($this->arParams['CURRENT_PAGE_QUERY_PARAM']));
        if ($currentPage <= 0) {
            $currentPage = 1;
        }
        $navigationResult['CURRENT_PAGE'] = $currentPage;

        $perPage = intval($this->arParams['PAGER_COUNT']);

        $navigationResult['PER_PAGE'] = $perPage;

        return $navigationResult;
    }

    public function onPrepareComponentParams($arParams)
    {
        if (!Loader::includeModule('iblock')) {
            return [];
        }

        $arParams['IBLOCK_ID'] = intval($arParams['IBLOCK_ID']);

        if ($arParams['IBLOCK_ID'] <= 0) {
            return [];
        }

        //! Влияет на размер кэша 
        $arParams['USE_PAGER'] = $arParams['USE_PAGER'] == 'Y' ? true : false;
        $arParams['USE_FILTER'] = $arParams['USE_FILTER'] == 'Y' ? true : false;
        $arParams['USE_TEMPLATE'] = $arParams['USE_TEMPLATE'] == 'Y' ? true : false;

        $arParams["PAGER_COUNT"] = intval($arParams["PAGER_COUNT"]);
        if ($arParams["PAGER_COUNT"] <= 0) {
            $arParams["PAGER_COUNT"] = 5;
        }

        return $arParams;
    }

    protected function getReviews()
    {
        try {
            if (!Loader::includeModule('iblock')) {
                throw new Exception('Не удалось подключить модуль инфоблоков');
            }

            if ($this->arParams['IBLOCK_ID'] > 0) {
                $rating = 0;
                $counter = 0;

                $query = ElementTable::query();
                $query->setSelect(['ID']);
                $query->where('IBLOCK_ID', $this->arParams['IBLOCK_ID']);
                $query->where('ACTIVE', 'Y');

                if (!($this->arParams["USE_TEMPLATE"]) && $this->arParams["CACHE_TYPE"] != "N" && $this->arParams['CACHE_TIME']) {
                    $query->setCacheTtl($this->arParams['CACHE_TIME']);
                    $query->cacheJoins(true);
                }

                $dbItems  = $query->exec();

                $arResult = [];
                $arItems = [];

                $arRatingGroups = [
                    '5' => 0,
                    '4' => 0,
                    '3' => 0,
                    '2' => 0,
                    '1' => 0,
                ];

                //! FAKE FILTER INCOMING 

                // $filterImg = null;

                $arFilter = $this->getFilterArray();

                if ($arFilter) {
                    $arResult['FILTER'] = $arFilter;
                }

                $arNav = $this->getNavigationArray();

                if ($arNav) {
                    $arResult['PAGINATION'] = $arNav;
                }

                while ($dbItem = $dbItems->fetch()) {
                    $arFields = $dbItem;

                    $queryProps = ElementPropertyTable::query();
                    $queryProps->setSelect([
                        'CODE' => 'pt.CODE',
                        'VALUE',
                        'PROPERTY_TYPE' => 'pt.PROPERTY_TYPE',
                    ]);
                    $queryProps->registerRuntimeField(
                        new Reference(
                            'pt',
                            PropertyTable::class,
                            Join::on('this.IBLOCK_PROPERTY_ID', 'ref.ID')
                        )
                    );
                    $queryProps->where("IBLOCK_ELEMENT_ID", $arFields['ID']);

                    $dbProps = $queryProps->exec()->fetchAll();

                    $arProps = [];

                    $nestedPropFound = false;
                    foreach ($dbProps as &$arProp) {
                        if ($arProp['PROPERTY_TYPE'] === 'F') {
                            $arProp['VALUE'] = FileTable::getList(['select' => ['SUBDIR', 'FILE_NAME'], 'filter' => [
                                '=ID' => $arProp['VALUE']
                            ], 'limit' => 1, 'cache' => ['ttl' => 36000]])->fetch();
                        }

                        if ($this->arParams['PROPERTY_RATING_CODE'] && $arProp['CODE'] == $this->arParams['PROPERTY_RATING_CODE']) {
                            $rating += intval($arProp['VALUE']);

                            if (intval($arProp['VALUE']) > 0) {
                                $arRatingGroups[$arProp['VALUE']]++;
                            }
                        }

                        if (isset($arProps[$arProp['CODE']])) {
                            if ($nestedPropFound) {
                                $arProps[$arProp['CODE']][] = $arProp['VALUE'];
                            } else {
                                $nestedPropFound = true;
                                $arProps[$arProp['CODE']] = [$arProps[$arProp['CODE']], $arProp['VALUE']];
                            }
                        } else {
                            $arProps[$arProp['CODE']] = $arProp['VALUE'];
                        }
                    }
                    unset($arProp);
                    unset($nestedPropFound);

                    $counter++;

                    if ($arFilter && $arFilter[$this->arParams['PROPERTY_RATING_CODE']]) {
                        if ($arProps[$this->arParams['PROPERTY_RATING_CODE']] != $arFilter[$this->arParams['PROPERTY_RATING_CODE']]) {
                            continue;
                        }
                    }

                    $arItems[] = $arProps;
                }

                if ($counter > 0) {
                    $rating = $rating / $counter;
                }

                $arResult['ITEMS'] = $arItems;
                $arResult['TOTAL_RATING'] = $rating;
                $arResult['TOTAL_COUNTER'] = $counter;
                $arResult['TOTAL_COUNTER_BY_RATING_GROUPS'] = $arRatingGroups;

                if ($arNav) {
                    if ($arNav['PER_PAGE'] > 0) {
                        $totalPages = ceil(count($arItems) / $arNav['PER_PAGE']);

                        $currentPage = $arNav['CURRENT_PAGE'];

                        if ($currentPage > $totalPages) {
                            $currentPage = $totalPages;
                        }

                        $arResult['PAGINATION']['TOTAL_PAGES'] = $totalPages;

                        if (count($arItems) >= $arNav['PER_PAGE']) {
                            $arItemsPaginated = [];

                            for ($i = ($currentPage - 1) * $arNav['PER_PAGE']; $i < ($currentPage - 1) * $arNav['PER_PAGE'] + $arNav['PER_PAGE']; $i++) {
                                if ($i >= count($arItems)) {
                                    break;
                                }

                                $arItemsPaginated[] = $arItems[$i];
                            }

                            unset($i);

                            $arResult['ITEMS'] = $arItemsPaginated;
                        }
                    }
                }

                return $arResult;
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
