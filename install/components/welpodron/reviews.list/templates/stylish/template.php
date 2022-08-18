<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var WelpodronReviewsList $component */

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$postRating = $request->get('rating');
$postOrder = $request->get('order') ? $request->get('order') : $arParams['FIRST_SORT_FIELD'] . ':' . $arParams['FIRST_SORT_ORDER'];

// AJAX v2
CJSCore::Init(['ajax']);
$bxajaxid = CAjax::GetComponentID($component->__name, $component->__template->__name, '');
$postAjaxCall = $request->get('AJAX_CALL') == 'Y';
$postBxAjaxId = $request->get('bxajaxid');

$formFilterId = 'form_' . md5(uniqid('', false));
$formPaginationId = 'form_' . md5(uniqid('', false));
?>

<?
if ($postAjaxCall && $postBxAjaxId && $postBxAjaxId == $bxajaxid) {
    $APPLICATION->RestartBuffer();
    while (@ob_end_clean()) {
        // FUCK YOU BITRIX  
    }
}
?>

<? if (!$postAjaxCall) : ?>
    <div class="reviews-list" id="comp_<?= $bxajaxid ?>">
    <? endif; ?>
    <form class="reviews-list__filter" id="<?= $formFilterId ?>" method="POST">
        <!-- AJAX v2 -->
        <input type="hidden" name="bxajaxid" value="<?= $bxajaxid ?>">
        <input type="hidden" name="AJAX_CALL" value="Y">
        <!-- AJAX v2 -->
        <input name="reviewsamount" value="0" type="hidden" />
        <label class="reviews-list__filter-field">
            <span class="reviews-list__filter-field-name">Сортировать по:</span>
            <select class="reviews-list__select" name="order">
                <option <?= $postOrder == "property_rating:desc" ? 'selected' : '' ?> value="property_rating:desc">Оценка по убыванию</option>
                <option <?= $postOrder == "property_rating:asc" ? 'selected' : '' ?> value="property_rating:asc">Оценка по возрастанию</option>
                <option <?= $postOrder == "created:desc" ? 'selected' : '' ?> value="created:desc">Новые</option>
                <option <?= $postOrder == "created:asc" ? 'selected' : '' ?> value="created:asc">Старые</option>
            </select>
        </label>
        <label class="reviews-list__filter-field">
            <span class="reviews-list__filter-field-name">Оценка:</span>
            <select class="reviews-list__select" name="rating">
                <option value="">Любая</option>
                <option <?= $postRating == "5" ? 'selected' : '' ?> value="5">5 звезд</option>
                <option <?= $postRating == "4" ? 'selected' : '' ?> value="4">4 звезды</option>
                <option <?= $postRating == "3" ? 'selected' : '' ?> value="3">3 звезды</option>
                <option <?= $postRating == "2" ? 'selected' : '' ?> value="2">2 звезды</option>
                <option <?= $postRating == "1" ? 'selected' : '' ?> value="1">1 звезда</option>
            </select>
        </label>
        <label class="reviews-list__filter-field">
            <span class="reviews-list__filter-field-name">С фото:</span>
            <input class="reviews-list__checkbox" <?= $request->get('images') == "1" ? 'checked' : '' ?> type="checkbox" name="images" value="1" />
        </label>
    </form>

    <script>
        document.querySelector('#<?= $formFilterId ?>').onchange = (evt) => {
            fetch(window.location.href, {
                method: 'POST',
                body: new FormData(evt.currentTarget)
            }).then((response) => {
                return response.text();
            }).then((html) => {
                responseObj = BX.processHTML(html);
                document.querySelector('#comp_<?= $bxajaxid ?>').innerHTML = responseObj.HTML;
                BX.ajax.processScripts(responseObj.SCRIPT, true);
                BX.ajax.processScripts(responseObj.SCRIPT, false);
            }).catch((err) => {
                console.error(err);
            })
        }
    </script>

    <div class="reviews-list__items">
        <? foreach ($arResult['ITEMS'] as $arItem) : ?>
            <?
            $this->AddEditAction(
                $arItem['FIELDS']['ID'],
                $arItem['ACTIONS']['EDIT_LINK'],
                CIBlock::GetArrayByID($arItem['FIELDS']['IBLOCK_ID'], 'ELEMENT_EDIT')
            );
            $this->AddDeleteAction(
                $arItem['FIELDS']['ID'],
                $arItem['ACTIONS']['DELETE_LINK'],
                CIBlock::GetArrayByID($arItem['FIELDS']['IBLOCK_ID'], 'ELEMENT_DELETE'),
                ['CONFIRM' => 'Будет удалена вся информация, связанная с этой записью. Продолжить?']
            );
            ?>
            <div class="reviews-list__item" itemprop="review" itemscope itemtype="https://schema.org/Review" id="<?= $this->GetEditAreaId($arItem['FIELDS']['ID']); ?>">
                <div class="reviews-list__rating">
                    <span itemprop="reviewRating" style="display: none;"><?= $arItem['PROPS']['rating']['VALUE'] ?></span>
                    <div class="reviews-list__rating-current" style="width:calc(<?= $arItem['PROPS']['rating']['VALUE'] ?> * 20%)"></div>
                </div>
                <p itemprop="author"><?= $arItem['PROPS']['author']['VALUE'] ?></p>
                <p itemprop="datePublished" content="<?= $arItem['FIELDS']['DATE_CREATE'] ?>"><?= $arItem['FIELDS']['DATE_CREATE'] ?></p>
                <? if ($arItem['PROPS']['advantages']['VALUE']) : ?>
                    <div>
                        <p>Достоинства:</p>
                        <p itemprop="positiveNotes"><?= $arItem['PROPS']['advantages']['VALUE'] ?></p>
                    </div>
                <? endif; ?>
                <? if ($arItem['PROPS']['disadvantages']['VALUE']) : ?>
                    <div>
                        <p>Недостатки:</p>
                        <p itemprop="negativeNotes"><?= $arItem['PROPS']['disadvantages']['VALUE'] ?></p>
                    </div>
                <? endif; ?>
                <div>
                    <p>Комментарий:</p>
                    <p itemprop="reviewBody"><?= $arItem['PROPS']['comment']['VALUE'] ?></p>
                </div>
                <? if ($arItem['PROPS']['images']['VALUE']) : ?>
                    <div>
                        <p>Фотографии:</p>
                        <div class="reviews-list__images">
                            <? foreach ($arItem['PROPS']['images']['VALUE'] as $reviewImage) : ?>
                                <img class="reviews-list__img" src="<?= $reviewImage['SRC'] ?>" />
                            <? endforeach; ?>
                        </div>
                    </div>
                <? endif; ?>
                <? if ($arItem['PROPS']['responce_text']['VALUE']) : ?>
                    <div class="reviews-list__responce">
                        <p>Менеджер Ольга</p>
                        <p><?= $arItem['PROPS']['responce_text']['VALUE'] ?></p>
                    </div>
                <? endif; ?>
            </div>
        <? endforeach; ?>
    </div>

    <? if ($arResult['NAV_RESULT']["NavRecordCount"] > 1) : ?>
        <? if ($arResult['NAV_RESULT']["NavPageSize"] < $arResult['NAV_RESULT']["NavRecordCount"]) : ?>
            <form id="<?= $formPaginationId ?>" method="POST">
                <!-- AJAX v2 -->
                <input type="hidden" name="bxajaxid" value="<?= $bxajaxid ?>">
                <input type="hidden" name="AJAX_CALL" value="Y">
                <!-- AJAX v2 -->
                <input name="order" value="<?= array_key_first($arResult['CURRENT_FILTER']['ORDER']) . ':' . $arResult['CURRENT_FILTER']['ORDER'][array_key_first($arResult['CURRENT_FILTER']['ORDER'])] ?>" type="hidden" />
                <input name="rating" value="<?= $arResult['CURRENT_FILTER']['FILTER']['PROPERTY_rating'] ?>" type="hidden" />
                <input name="images" value="<?= $arResult['CURRENT_FILTER']['FILTER']['PROPERTY_rating'] ?>" type="hidden" />
                <input name="reviewsamount" value="<?= $arResult['NAV_RESULT']["NavPageSize"] + intval($arParams['ELEMENTS_PER_PAGE']) ?>" type="hidden" />
                <button class="reviews-list__show-more">Показать еще</button>
            </form>
            <script>
                document.querySelector('#<?= $formPaginationId ?>').onsubmit = (evt) => {
                    evt.preventDefault();
                    fetch(window.location.href, {
                        method: 'POST',
                        body: new FormData(evt.currentTarget)
                    }).then((response) => {
                        return response.text();
                    }).then((html) => {
                        responseObj = BX.processHTML(html);
                        document.querySelector('#comp_<?= $bxajaxid ?>').innerHTML = responseObj.HTML;
                        BX.ajax.processScripts(responseObj.SCRIPT, true);
                        BX.ajax.processScripts(responseObj.SCRIPT, false);
                    }).catch((err) => {
                        console.error(err);
                    })
                }
            </script>
        <? endif ?>
    <? endif ?>

    <? if (!$postAjaxCall) : ?>
    </div>
<? endif; ?>

<?
if ($postAjaxCall && $postBxAjaxId && $postBxAjaxId == $bxajaxid) {
    die();
}
?>