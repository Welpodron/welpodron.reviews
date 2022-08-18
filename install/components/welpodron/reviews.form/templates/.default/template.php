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

?>

<form id="<?= $arResult['FORM_ID'] ?>">
    <input type="hidden" name="element" value="<?= $arParams['ELEMENT_ID'] ?>">
    <div>
        Оценка:
        <input type="radio" name="rating" value="1" aria-label="1 Звезда" checked="">
        <input type="radio" name="rating" value="2" aria-label="2 Звезды">
        <input type="radio" name="rating" value="3" aria-label="3 Звезды">
        <input type="radio" name="rating" value="4" aria-label="4 Звезды">
        <input type="radio" name="rating" value="5" aria-label="5 Звезд">
    </div>
    <label>
        Автор:
        <input autocomplete="name" name="author">
    </label>
    <label>
        Преимущества:
        <textarea name="advantages"></textarea>
    </label>
    <label>
        Недостатки:
        <textarea name="disadvantages"></textarea>
    </label>
    <label>
        Комментарий:
        <textarea name="comment"></textarea>
    </label>
    <div data-files-dropzone>
        <div data-files-dropzone-showcase></div>
        <label data-files-dropzone-zone>
            <input multiple accept=".jpg, .jpeg, .png" type="file" name="images[]">
            <span data-files-dropzone-drop-display>
                <span>
                    <span>Выберите фотографии<br> или перетащите изображения в эту область</span>
                </span>
            </span>
            <span data-files-dropzone-dropping-display>
                <span>
                    <span>Отпустите файл для его загрузки</span>
                </span>
            </span>
        </label>
    </div>
    <button>Отправить отзыв</button>
</form>
<script>
    document.querySelector('#<?= $arResult['FORM_ID'] ?>').addEventListener('submit', (evt) => {
        evt.preventDefault();
        BX.ajax.runAction('<?= $arResult['JS_ACTION'] ?>', {
                data: new FormData(evt.currentTarget)
            })
            .then(function(data) {
                console.log(data)
            }).catch((err) => {
                console.error(err);
            });
    })
</script>