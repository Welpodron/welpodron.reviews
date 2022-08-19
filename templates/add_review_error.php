<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$dialogId = 'dialog_' . md5(uniqid('', false));
?>

<dialog data-once data-force-show-modal id="<?= $dialogId ?>" class="reviews-form-add-dialog-error" data-dialog-native>
    <div class="reviews-form-add-dialog-error-body">
        <svg width="65" height="65" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="#dc2626">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
        </svg>
        <p>Ошибка!</p>
        <p>При обработке вашего запроса произошла ошибка, повторите попытку позже или свяжитесь с администрацией сайта</p>
    </div>
    <div class="reviews-form-add-dialog-error-footer">
        <button data-dialog-native-action="close" data-dialog-native-id="<?= $dialogId ?>" class="reviews-form-add-dialog-error-btn-close" type="button">Закрыть</button>
    </div>

    <script>
        new welpodon.dialogNative(document.querySelector("#<?= $dialogId ?>"));
    </script>

    <style>
        .reviews-form-add-dialog-error {
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

        .reviews-form-add-dialog-error:not([open]) {
            opacity: 0;
        }

        @media (prefers-reduced-motion: no-preference) {
            .reviews-form-add-dialog-error {
                transition: opacity 0.5s cubic-bezier(0.25, 0, 0.3, 1);
            }
        }

        .reviews-form-add-dialog-error-body {
            display: grid;
            padding: 20px;
            place-items: center;
            text-align: center;
        }

        .reviews-form-add-dialog-error-footer {
            padding: 20px;
            display: grid;
            background: rgba(107, 114, 128, 0.05);
        }

        .reviews-form-add-dialog-error-btn-close {
            cursor: pointer;
            padding: 15px;
            border: 0;
            font: inherit;
            background: #dc2626;
            color: #fff;
            border-radius: 4px;
        }
    </style>
</dialog>