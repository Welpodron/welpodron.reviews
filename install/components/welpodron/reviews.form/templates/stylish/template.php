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

// FUCKING css CACHE FIX 
?>
<link href="<?= $templateFolder . "/style.css" ?>" type="text/css" rel="stylesheet" />
<script src="<?= $templateFolder . "/script.js" ?>"></script>
<?

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
    }
}
?>

<? if (!$postAjaxCall) : ?>
    <div class="reviews-list" id="comp_<?= $bxajaxid ?>">
    <? endif; ?>
    <form class="reviews-list-filter" id="<?= $formFilterId ?>" method="POST">
        <input type="hidden" name="bxajaxid" value="<?= $bxajaxid ?>">
        <input type="hidden" name="AJAX_CALL" value="Y">
        <input name="reviewsamount" value="0" type="hidden" />
        <label class="reviews-list-filter-field">
            <span class="reviews-list-filter-field-name">Сортировать по:</span>
            <select class="reviews-list-filter-field-select" name="order">
                <option <?= $postOrder == "property_rating:desc" ? 'selected' : '' ?> value="property_rating:desc">Оценка по убыванию</option>
                <option <?= $postOrder == "property_rating:asc" ? 'selected' : '' ?> value="property_rating:asc">Оценка по возрастанию</option>
                <option <?= $postOrder == "property_date:desc" ? 'selected' : '' ?> value="property_date:desc">Новые</option>
                <option <?= $postOrder == "property_date:asc" ? 'selected' : '' ?> value="property_date:asc">Старые</option>
            </select>
        </label>
        <label class="reviews-list-filter-field">
            <span class="reviews-list-filter-field-name">Оценка:</span>
            <select class="reviews-list-filter-field-select" name="rating">
                <option value="">Любая</option>
                <option <?= $postRating == "5" ? 'selected' : '' ?> value="5">5 звезд</option>
                <option <?= $postRating == "4" ? 'selected' : '' ?> value="4">4 звезды</option>
                <option <?= $postRating == "3" ? 'selected' : '' ?> value="3">3 звезды</option>
                <option <?= $postRating == "2" ? 'selected' : '' ?> value="2">2 звезды</option>
                <option <?= $postRating == "1" ? 'selected' : '' ?> value="1">1 звезда</option>
            </select>
        </label>
        <label class="reviews-list-filter-field">
            <span class="reviews-list-filter-field-name">С фото:</span>
            <input class="reviews-list-filter-field-checkbox" <?= $request->get('images') == "1" ? 'checked' : '' ?> type="checkbox" name="images" value="1" />
        </label>
    </form>

    <script>
        document.querySelector('#<?= $formFilterId ?>').onchange = (evt) => {
            BX.ajax({
                url: `${window.location.pathname}?&bxajaxid=<?= $bxajaxid ?>`,
                data: Object.fromEntries(new FormData(evt.currentTarget)),
                method: 'POST',
                dataType: 'html',
                timeout: 0,
                async: true,
                preparePost: true,
                lsTimeout: 30,
                processData: false,
                scriptsRunFirst: false,
                emulateOnload: true,
                start: true,
                cache: false,
                onsuccess: (data) => {
                    responseObj = BX.processHTML(data);
                    document.querySelector('#comp_<?= $bxajaxid ?>').innerHTML = responseObj.HTML;
                    BX.ajax.processScripts(responseObj.SCRIPT, true);
                    BX.ajax.processScripts(responseObj.SCRIPT, false);
                },
                onfailure: (err) => {
                    console.log(err);
                }
            });
        }
    </script>
    <div class="reviews-list-items">
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
            <div class="reviews-list-items-item" itemprop="review" itemscope itemtype="https://schema.org/Review" id="<?= $this->GetEditAreaId($arItem['FIELDS']['ID']); ?>">
                <div class="rating-stars">
                    <span itemprop="reviewRating" style="display: none;"><?= $arItem['PROPS']['rating']['VALUE'] ?></span>
                    <div class="rating-stars-current" style="width:calc(<?= $arItem['PROPS']['rating']['VALUE'] ?> * 20%)"></div>
                </div>
                <p itemprop="author"><?= $arItem['PROPS']['author']['VALUE'] ?></p>
                <p itemprop="datePublished" content="<?= $arItem['PROPS']['date']['VALUE'] ?>"><?= $arItem['PROPS']['date']['VALUE'] ?></p>
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
                        <div class="reviews-list-items-item-images">
                            <? foreach ($arItem['PROPS']['images']['VALUE'] as $reviewImage) : ?>
                                <? $imgId = 'img_' . md5(uniqid('', false)); ?>
                                <img id="<?= $imgId ?>" data-preview-img="" data-preview-dialog-class="reviews-preview-img-dialog" data-preview-dialog-btn-close-class="reviews-preview-img-dialog-btn-close" data-preview-dialog-img-class="reviews-preview-img-dialog-img" data-preview-src="<?= $reviewImage['ORIGINAL_SRC'] ?>" class="reviews-list-items-item-images-img" src="<?= $reviewImage['SRC'] ?>" />
                                <script>
                                    new welpodron.imgPreview(document.querySelector('#<?= $imgId ?>'));
                                </script>
                            <? endforeach; ?>
                        </div>
                    </div>
                <? endif; ?>
                <? if ($arItem['PROPS']['responce_text']['VALUE']) : ?>
                    <div class="reviews-list-items-item-responce">
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
                <input type="hidden" name="bxajaxid" value="<?= $bxajaxid ?>">
                <input type="hidden" name="AJAX_CALL" value="Y">
                <input name="order" value="<?= array_key_first($arResult['CURRENT_FILTER']['ORDER']) . ':' . $arResult['CURRENT_FILTER']['ORDER'][array_key_first($arResult['CURRENT_FILTER']['ORDER'])] ?>" type="hidden" />
                <input name="rating" value="<?= $arResult['CURRENT_FILTER']['FILTER']['PROPERTY_rating'] ?>" type="hidden" />
                <input name="images" value="<?= $arResult['CURRENT_FILTER']['FILTER']['PROPERTY_images'] ?>" type="hidden" />
                <input name="reviewsamount" value="<?= $arResult['NAV_RESULT']["NavPageSize"] + intval($arParams['ELEMENTS_PER_PAGE']) ?>" type="hidden" />
                <button class="reviews-list-btn-show-more">Показать еще</button>
            </form>
            <script>
                document.querySelector('#<?= $formPaginationId ?>').onsubmit = (evt) => {
                    evt.preventDefault();
                    BX.ajax({
                        url: `${window.location.pathname}?&bxajaxid=<?= $bxajaxid ?>`,
                        data: Object.fromEntries(new FormData(evt.currentTarget)),
                        method: 'POST',
                        dataType: 'html',
                        timeout: 0,
                        async: true,
                        preparePost: true,
                        lsTimeout: 30,
                        processData: false,
                        scriptsRunFirst: false,
                        emulateOnload: true,
                        start: true,
                        cache: false,
                        onsuccess: (data) => {
                            responseObj = BX.processHTML(data);
                            document.querySelector('#comp_<?= $bxajaxid ?>').innerHTML = responseObj.HTML;
                            BX.ajax.processScripts(responseObj.SCRIPT, true);
                            BX.ajax.processScripts(responseObj.SCRIPT, false);
                        },
                        onfailure: (err) => {
                            console.log(err);
                        }
                    });
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