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

class WelpodronElementReviews extends CBitrixComponent
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
            //! ТODO: Ахтунг в ORM нет поддержки тегированного кэша по умолчанию
            //! Необходима собственная реализация если она вообще нужна
            //! Подробнее : https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&LESSON_ID=2978&LESSON_PATH=3913.3516.4790.2127.2978
            //! Cм пример iblockresult.php метод Fetch 
            if ($this->arParams["CACHE_TYPE"] != "N" && $this->arParams['CACHE_TIME'] && $this->arParams['IBLOCK_ID'] > 0) {
                if (defined("BX_COMP_MANAGED_CACHE")) {
                    \CIBlock::registerWithTagCache($this->arParams['IBLOCK_ID']);
                }
            }

            $this->arResult = $this->getReviews();

            if (!($this->arParams['PRODUCT_NUMBER']) || $this->arParams['PROPERTY_PRODUCT_NUMBER_ID'] <= 0) {
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

        if (!$arParams['PRODUCT_NUMBER']) {
            return [];
        }

        $arParams['PRODUCT_NUMBER'] = strval($arParams['PRODUCT_NUMBER']);

        //! Id свойства артикул элемента из инфоблока ОТЗЫВОВ!
        $arParams['PROPERTY_PRODUCT_NUMBER_ID'] = intval($arParams['PROPERTY_PRODUCT_NUMBER_ID']);

        if ($arParams['PROPERTY_PRODUCT_NUMBER_ID'] <= 0) {
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

            if ($this->arParams['PRODUCT_NUMBER'] && $this->arParams['PROPERTY_PRODUCT_NUMBER_ID'] > 0) {
                $rating = 0;
                $counter = 0;

                $query = ElementPropertyTable::query();

                $query->setSelect(['IBLOCK_ELEMENT_ID', 'VALUE', 'CODE' => 'p_t.CODE']);
                $query->registerRuntimeField(
                    new Reference(
                        'ep_t',
                        ElementPropertyTable::class,
                        Join::on('this.IBLOCK_ELEMENT_ID', 'ref.IBLOCK_ELEMENT_ID')
                    )
                );
                $query->registerRuntimeField(
                    new Reference(
                        'p_t',
                        PropertyTable::class,
                        Join::on('this.IBLOCK_PROPERTY_ID', 'ref.ID')
                    )
                );
                $query->where("ELEMENT.ACTIVE", 'Y');
                $query->where("ep_t.VALUE", $this->arParams['PRODUCT_NUMBER']);
                //! Id свойства артикул элемента из инфоблока ОТЗЫВОВ!
                $query->where("ep_t.IBLOCK_PROPERTY_ID", $this->arParams['PROPERTY_PRODUCT_NUMBER_ID']);

                $query->setOrder(['IBLOCK_ELEMENT_ID' => 'DESC']);

                if (!($this->arParams["USE_TEMPLATE"]) && $this->arParams["CACHE_TYPE"] != "N" && $this->arParams['CACHE_TIME']) {
                    $query->setCacheTtl($this->arParams['CACHE_TIME']);
                    $query->cacheJoins(true);
                }

                $queryResult = $query->exec();

                $arResult = [];

                $arReviews = [];
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

                while ($queryRow = $queryResult->fetch()) {
                    if ($this->arParams['PROPERTY_RATING_CODE'] && $queryRow['CODE'] == $this->arParams['PROPERTY_RATING_CODE']) {
                        $rating += intval($queryRow['VALUE']);

                        if (intval($queryRow['VALUE']) > 0) {
                            $arRatingGroups[$queryRow['VALUE']]++;
                        }
                    }

                    if (is_array($arReviews[$queryRow['IBLOCK_ELEMENT_ID']])) {
                        if (isset($arReviews[$queryRow['IBLOCK_ELEMENT_ID']][$queryRow['CODE']])) {
                            if (is_array($arReviews[$queryRow['IBLOCK_ELEMENT_ID']][$queryRow['CODE']])) {
                                $arReviews[$queryRow['IBLOCK_ELEMENT_ID']][$queryRow['CODE']][] = $queryRow['VALUE'];
                            } else {
                                $arReviews[$queryRow['IBLOCK_ELEMENT_ID']][$queryRow['CODE']] = [$arReviews[$queryRow['IBLOCK_ELEMENT_ID']][$queryRow['CODE']], $queryRow['VALUE']];
                            }
                        } else {
                            $arReviews[$queryRow['IBLOCK_ELEMENT_ID']][$queryRow['CODE']] = $queryRow['VALUE'];
                        }
                    } else {
                        $arReviews[$queryRow['IBLOCK_ELEMENT_ID']] = [$queryRow['CODE'] => $queryRow['VALUE']];
                    }
                }

                $arItems = [];

                foreach ($arReviews as $arReview) {
                    $counter++;

                    if ($arFilter && $arFilter[$this->arParams['PROPERTY_RATING_CODE']]) {
                        if ($arReview[$this->arParams['PROPERTY_RATING_CODE']] != $arFilter[$this->arParams['PROPERTY_RATING_CODE']]) {
                            continue;
                        }
                    }

                    if ($this->arParams['PROPERTY_IMAGES_CODE'] && $arReview[$this->arParams['PROPERTY_IMAGES_CODE']]) {
                        $arReview[$this->arParams['PROPERTY_IMAGES_CODE']] = FileTable::getList([
                            'select' => ['SUBDIR', 'FILE_NAME'],
                            'filter' => ['=ID' => $arReview[$this->arParams['PROPERTY_IMAGES_CODE']]],
                            'cache' => ['ttl' => 36000]
                        ])->fetchAll();

                        // if ($filterImg && !$arReview[$this->arParams['PROPERTY_IMAGES_CODE']]) {
                        //     continue;
                        // }
                    }

                    $arItems[] = $arReview;
                }
                unset($arReview);

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
