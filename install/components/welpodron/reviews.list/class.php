<?

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\FileTable;
use Bitrix\Main\Context;
use Bitrix\Main\Config\Option;

class WelpodronReviewsList extends CBitrixComponent
{
    const MODULE_ID = "welpodron.reviews";

    public function executeComponent()
    {
        // Кэширование данного компонента отключено по-умолчанию
        $this->arResult = $this->getElements();

        $this->includeComponentTemplate();

        return $this->arResult;
    }

    public function onPrepareComponentParams($arParams)
    {
        if (!Loader::includeModule('iblock')) {
            return [];
        }

        $arParams['ELEMENT_ID'] = intval($arParams['ELEMENT_ID']);
        $arParams['IBLOCK_ID'] = intval(Option::get(self::MODULE_ID, 'IBLOCK_ID'));

        $arParams['FIRST_SORT_FIELD'] = strtolower(trim($arParams['FIRST_SORT_FIELD']));
        $arParams['FIRST_SORT_ORDER'] = strtolower(trim($arParams['FIRST_SORT_ORDER']));

        $arParams['ELEMENTS_PER_PAGE'] = intval($arParams['ELEMENTS_PER_PAGE']);

        return $arParams;
    }

    protected function getElements()
    {
        if ($this->arParams['IBLOCK_ID'] > 0 && $this->arParams['ELEMENT_ID'] > 0) {
            $arFilter = [
                'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
                'SITE_ID' => Context::getCurrent()->getSite(),
                'CHECK_PERMISSIONS' => 'N',
                'ACTIVE' => 'Y',
                'PROPERTY_element' => $this->arParams['ELEMENT_ID']
            ];
            $arOrder = [];
            $arGroup = false;

            $postReviewsAmount = intval($this->request->get('reviewsamount'));

            $arNav = [
                'nPageSize' => $postReviewsAmount > 0 ? $postReviewsAmount : $this->arParams['ELEMENTS_PER_PAGE'],
                'iNumPage' =>  1,
                'bShowAll' => false
            ];
            $arSelect = [];

            if (is_array($this->arParams['FILTER']) && !empty($this->arParams['FILTER'])) {
                $arFilter = array_merge($this->arParams['FILTER'], $arFilter);
            }

            $postRating =  $this->request->get('rating') ?: "";
            $postImages = $this->request->get('images') ?: "";

            $arFilter["PROPERTY_rating"] = $postRating;

            if ($postImages) {
                // Найти не пустые, подробнее: Примеры Несколько частных случаев фильтрации https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&LESSON_ID=2683 
                $arFilter["!PROPERTY_images"] = false;
            }

            if ($this->arParams['FIRST_SORT_FIELD'] && $this->arParams['FIRST_SORT_ORDER']) {
                $arOrder[$this->arParams['FIRST_SORT_FIELD']] = $this->arParams['FIRST_SORT_ORDER'];
            }

            $postOrder = $this->request->get('order') ? explode(':', $this->request->get('order')) : null;

            if (is_array($postOrder)) {
                $arOrder = [];
                $arOrder[$postOrder[0]] = $postOrder[1];
            }

            $arSelect = [];

            $arCurrentFilter = ['ORDER' => $arOrder, 'FILTER' => $arFilter, 'NAV' => $arNav];

            $dbElements = CIBlockElement::GetList($arOrder, $arFilter, $arGroup, $arNav, $arSelect);
            // Кастомная упрощенная навигация 
            // Подробнее: bitrix\components\bitrix\system.pagenavigation\component.php
            // Реализация похожа на результат метода GetPageNavStringEx
            $arNavResult["NavRecordCount"] = $dbElements->NavRecordCount;
            $arNavResult["NavPageCount"] = $dbElements->NavPageCount;
            $arNavResult["NavPageNomer"] = $dbElements->NavPageNomer;
            $arNavResult["NavPageSize"] = $dbElements->NavPageSize;
            $arNavResult["bShowAll"] = $dbElements->bShowAll;
            $arNavResult["NavShowAll"] = $dbElements->NavShowAll;
            $arNavResult["NavNum"] = $dbElements->NavNum;
            $arNavResult["bDescPageNumbering"] = $dbElements->bDescPageNumbering;
            $arNavResult["add_anchor"] = $dbElements->add_anchor;
            $arNavResult["nPageWindow"] = $nPageWindow = $dbElements->nPageWindow;

            if ($dbElements->NavPageNomer > floor($nPageWindow / 2) + 1 && $dbElements->NavPageCount > $nPageWindow)
                $nStartPage = $dbElements->NavPageNomer - floor($nPageWindow / 2);
            else
                $nStartPage = 1;

            if ($dbElements->NavPageNomer <= $dbElements->NavPageCount - floor($nPageWindow / 2) && $nStartPage + $nPageWindow - 1 <= $dbElements->NavPageCount)
                $nEndPage = $nStartPage + $nPageWindow - 1;
            else {
                $nEndPage = $dbElements->NavPageCount;
                if ($nEndPage - $nPageWindow + 1 >= 1)
                    $nStartPage = $nEndPage - $nPageWindow + 1;
            }

            $arNavResult["nStartPage"] = $dbElements->nStartPage = $nStartPage;
            $arNavResult["nEndPage"] = $dbElements->nEndPage = $nEndPage;

            $NavFirstRecordShow = ($dbElements->NavPageNomer - 1) * $dbElements->NavPageSize + 1;

            if ($dbElements->NavPageNomer != $dbElements->NavPageCount)
                $NavLastRecordShow = $dbElements->NavPageNomer * $dbElements->NavPageSize;
            else
                $NavLastRecordShow = $dbElements->NavRecordCount;

            $arNavResult["NavFirstRecordShow"] = $NavFirstRecordShow;
            $arNavResult["NavLastRecordShow"] = $NavLastRecordShow;

            while ($ob = $dbElements->GetNextElement(true, false)) {
                $arFields = $ob->GetFields();
                $arProps = $ob->GetProperties();

                if ($arFields['PREVIEW_PICTURE']) {
                    $id = $arFields['PREVIEW_PICTURE'];

                    $file = FileTable::getList(['select' => ['ID', 'WIDTH', 'HEIGHT', 'CONTENT_TYPE', 'FILE_SIZE', 'DESCRIPTION'], 'filter' => [
                        '=ID' => $id
                    ], 'limit' => 1])->fetch();
                    $arFields['PREVIEW_PICTURE'] = $file;
                    $arFields['PREVIEW_PICTURE']['SRC'] = CFile::GetPath($id);
                }

                foreach ($arProps as &$arProp) {
                    if ($arProp['PROPERTY_TYPE'] === 'F') {
                        $id = $arProp['VALUE'];
                        if (is_array($id)) {
                            foreach ($id as $fileIndex => $_id) {
                                $file = FileTable::getList(['select' => ['ID', 'WIDTH', 'HEIGHT', 'CONTENT_TYPE', 'FILE_SIZE', 'DESCRIPTION'], 'filter' => [
                                    '=ID' => $_id
                                ], 'limit' => 1])->fetch();

                                if ($file) {
                                    $arProp['VALUE'][$fileIndex] = $file;
                                    $arProp['VALUE'][$fileIndex]['SRC'] = CFile::GetPath($_id);
                                }
                            }
                        } else {
                            $file = FileTable::getList(['select' => ['ID', 'WIDTH', 'HEIGHT', 'CONTENT_TYPE', 'FILE_SIZE', 'DESCRIPTION'], 'filter' => [
                                '=ID' => $id
                            ], 'limit' => 1])->fetch();

                            if ($file) {
                                $arProp['VALUE'] = $file;
                                $arProp['VALUE']['SRC'] = CFile::GetPath($id);
                            }
                        }
                    }
                }

                unset($arProp);

                $dbActions = CIBlock::GetPanelButtons($arFields['IBLOCK_ID'], $arFields['ID'], $arFields['SECTION_ID'], []);
                $arElement = ['FIELDS' => $arFields, 'PROPS' => $arProps];
                $arElement['ACTIONS']['EDIT_LINK'] = $dbActions['edit']['edit_element']['ACTION_URL'];
                $arElement['ACTIONS']['DELETE_LINK'] = $dbActions['edit']['delete_element']['ACTION_URL'];
                $arElements[] = $arElement;
            }

            $arButtons = CIBlock::GetPanelButtons(
                $this->arParams['IBLOCK_ID'],
                0,
                0,
                ['SECTION_BUTTONS' => false]
            );

            // TODO: Rework to D7!

            global $APPLICATION;

            if ($APPLICATION->GetShowIncludeAreas()) {
                $this->addIncludeAreaIcons(CIBlock::GetComponentMenu($APPLICATION->GetPublicShowMode(), $arButtons));
            }

            return ['ITEMS' => $arElements, 'CURRENT_FILTER' => $arCurrentFilter, 'NAV_RESULT' => $arNavResult];
        }
    }
}
