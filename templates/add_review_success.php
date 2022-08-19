<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$dialogId = 'dialog_' . md5(uniqid('', false));
?>

<dialog data-once data-force-show-modal id="<?= $dialogId ?>" class="reviews-form-add-dialog-success" data-dialog-native>
    <div class="reviews-form-add-dialog-success-body">
        <svg width="65" height="65" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="#55bc51">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
        </svg>
        <p>Отзыв успешно сохранен!</p>
        <p>Благодарим вас за оставленный отзыв, в ближайшее время он будет опубликован на сайте после прохождения проверки</p>
    </div>
    <div class="reviews-form-add-dialog-success-footer">
        <button data-dialog-native-action="close" data-dialog-native-id="<?= $dialogId ?>" class="reviews-form-add-dialog-success-btn-close" type="button">Хорошо</button>
    </div>
    <script>
        new welpodon.dialogNative(document.querySelector("#<?= $dialogId ?>"));
    </script>
    <style>
        .reviews-form-add-dialog-success {
            position: fixed;
            margin: auto;
            inset: 0;
            border: 0;
            box-shadow: 0 1px 5px rgba(107, 114, 128, 0.25);
            overflow-y: auto;
            padding: 0;
            border-radius: 0;
            background: #fff;
            display: block;
            border-radius: 4px;
            max-width: 380px;
        }

        .reviews-form-add-dialog-success-body {
            display: grid;
            padding: 20px;
            place-items: center;
            text-align: center;
        }

        .reviews-form-add-dialog-success-footer {
            padding: 20px;
            display: grid;
            background: rgba(107, 114, 128, 0.05);
        }

        .reviews-form-add-dialog-success-btn-close {
            cursor: pointer;
            padding: 15px;
            border: 0;
            font: inherit;
            background: #55bc51;
            color: #fff;
            border-radius: 4px;
        }
    </style>
</dialog>