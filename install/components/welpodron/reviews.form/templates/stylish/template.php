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
/** @var WelpodronReviewsForm $component */

$ratingFieldId = 'field_' . md5(uniqid('', false));
$photosFieldId = 'field_' . md5(uniqid('', false));
?>

<button class="reviews-form-add-dialog-btn-open" type="button" data-dialog-native-id="<?= $arResult['DIALOG_ID'] ?>" data-dialog-native-action="showModal">Оставить заявку</button>

<dialog id="<?= $arResult['DIALOG_ID'] ?>" class="reviews-form-add-dialog" data-dialog-native>
    <form action="<?= $arResult['ACTION_URL'] ?>" class="reviews-form-add" id="<?= $arResult['FORM_ID'] ?>">
        <div class="reviews-form-add-dialog-header">
            <p class="reviews-form-add-dialog-title">Оставить отзыв</p>
            <button data-dialog-native-id="<?= $arResult['DIALOG_ID'] ?>" data-dialog-native-action="close" class="reviews-form-add-dialog-btn-close" type="button" aria-label="Закрыть окно">
                <svg width="30" height="30" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
        <div data-form-fieldset class="reviews-form-add-dialog-body">
            <div data-form-errors></div>
            <div data-form-success></div>
            <div data-form-field-type="hidden" data-form-field-name="sessid" data-form-field>
                <input type="hidden" name="sessid" value="<?= bitrix_sessid() ?>">
            </div>
            <div data-form-field-type="hidden" data-form-field-name="element" data-form-field>
                <input type="hidden" name="element" value="<?= $arParams['ELEMENT_ID'] ?>">
            </div>
            <div class="reviews-form-add-input-group">
                <label data-form-field-type="text" data-form-field-name="author" data-form-field class="reviews-form-add-input-field">
                    <span>Ваше имя:*</span>
                    <input required type="text" placeholder="Заполните ваше имя" class="reviews-form-add-input-text" autocomplete="name" name="author">
                </label>
                <div data-form-field-type="radios" data-form-field-name="rating" data-form-field class="reviews-form-add-input-field">
                    <label for="<?= $ratingFieldId ?>">Оценка:*</label>
                    <div class="reviews-form-add-rating-input-stars">
                        <input required id="<?= $ratingFieldId ?>" class="reviews-form-add-rating-input-star" type="radio" name="rating" value="1" aria-label="1 Звезда" checked="">
                        <input class="reviews-form-add-rating-input-star" type="radio" name="rating" value="2" aria-label="2 Звезды">
                        <input class="reviews-form-add-rating-input-star" type="radio" name="rating" value="3" aria-label="3 Звезды">
                        <input class="reviews-form-add-rating-input-star" type="radio" name="rating" value="4" aria-label="4 Звезды">
                        <input class="reviews-form-add-rating-input-star" type="radio" name="rating" value="5" aria-label="5 Звезд">
                    </div>
                </div>
            </div>
            <label data-form-field-type="textarea" data-form-field-name="comment" data-form-field class="reviews-form-add-input-field">
                <span>Отзыв:*</span>
                <textarea required rows="6" placeholder="Ваш отзыв" class="reviews-form-add-input-text" name="comment"></textarea>
            </label>
            <label data-form-field-type="textarea" data-form-field-name="advantages" data-form-field class="reviews-form-add-input-field">
                <span>Преимущества:</span>
                <textarea rows="4" placeholder="Преимущества" class="reviews-form-add-input-text" name="advantages"></textarea>
            </label>
            <label data-form-field-type="textarea" data-form-field-name="disadvantages" data-form-field class="reviews-form-add-input-field">
                <span>Недостатки:</span>
                <textarea rows="4" placeholder="Недостатки" class="reviews-form-add-input-text" name="disadvantages"></textarea>
            </label>
            <div class="reviews-form-add-input-field">
                <span>Фотографии:</span>
                <div data-file-max-size="<?= $arResult['MAX_FILE_SIZE_BYTES'] ?>" data-files-supported="image/jpeg,image/jpg,image/png" data-files-max-amount="<?= $arResult['MAX_FILES_ALLOWED'] ?>" data-form-field-type="filesDropzone" data-form-field-name="images[]" data-form-field data-files-dropzone>
                    <div class="reviews-form-add-input-dropzone-showcase" data-files-dropzone-showcase></div>
                    <label class="reviews-form-add-input-dropzone-zone" data-files-dropzone-zone>
                        <input class="reviews-form-add-input-dropzone-input" multiple accept=".jpg, .jpeg, .png" type="file" name="images[]">
                        <span class="reviews-form-add-input-dropzone-display" data-files-dropzone-drop-display>
                            <span class="reviews-form-add-input-dropzone-display-group">
                                <svg width="40" height="40" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="#c4c7cc" stroke-width="1">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span>Выберете фотографии<br> или перетащите изображения в эту область</span>
                            </span>
                        </span>
                        <span class="reviews-form-add-input-dropzone-display" data-files-dropzone-dropping-display>
                            <span class="reviews-form-add-input-dropzone-display-group">
                                <svg width="40" height="40" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="#c4c7cc" stroke-width="1">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                <span>Отпустите файл для его загрузки</span>
                            </span>
                        </span>
                    </label>
                </div>
                <p>Не более <?= $arResult['MAX_FILES_ALLOWED'] ?>-х фото формата: .jpg, .jpeg, .png. Максимальный размер одного фото - <?= $arResult['MAX_FILE_SIZE_MB'] ?> МБ</p>
            </div>
            <p>* - обязательные для заполнения поля</p>
            <p>Нажимая на кнопку "Отправить отзыв", вы соглашаетесь с условиями <a href="https://centr-mebel.com/publichnaya-oferta/">Обработки персональных данных</a></p>
        </div>
        <div class="reviews-form-add-dialog-footer">
            <button type="submit" class="reviews-form-add-btn-send">Отправить отзыв</button>
        </div>
    </form>
    <script>
        new WelpodronDialogNative(document.querySelector('#<?= $arResult['DIALOG_ID'] ?>'));
        new WelpodronForm(document.querySelector('#<?= $arResult['FORM_ID'] ?>'));
    </script>
</dialog>