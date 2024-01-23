<?

namespace Welpodron\Reviews\Controller;

use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Error;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Context;
use Bitrix\Main\Mail\Event;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Mail\Event as MailEvent;

use Bitrix\Main\UserConsent\Consent;
use Bitrix\Main\UserConsent\Agreement;

use Bitrix\Iblock\PropertyTable;

use Bitrix\Main\Type\DateTime;

// TODO: Добавить отправку письма

class Receiver extends Controller
{
    const DEFAULT_FIELD_VALIDATION_ERROR_CODE = "FIELD_VALIDATION_ERROR";
    const DEFAULT_FORM_GENERAL_ERROR_CODE = "FORM_GENERAL_ERROR";
    const DEFAULT_MODULE_ID = 'welpodron.reviews';
    const DEFAULT_GOOGLE_URL = "https://www.google.com/recaptcha/api/siteverify";

    const DEFAULT_ERROR_CONTENT = "При обработке Вашего запроса произошла ошибка, повторите попытку позже или свяжитесь с администрацией сайта";

    protected function getDefaultPreFilters()
    {
        return [];
    }

    private function validateRating($arDataRaw)
    {
        $ratingProp = trim(Option::get(self::DEFAULT_MODULE_ID, 'RATING_PROPERTY'));

        if (!$ratingProp) {
            throw new \Exception('Не задан код поля рейтинга в настройках модуля');
        }

        $rating = intval($arDataRaw[$ratingProp]);

        if ($rating < 1) {
            $error = 'Минимальное значение поля: 1';
            $this->addError(new Error($error, self::DEFAULT_FIELD_VALIDATION_ERROR_CODE, $ratingProp));
            return;
        }

        if ($rating > 5) {
            $error = 'Максимальное значение поля: 5';
            $this->addError(new Error($error, self::DEFAULT_FIELD_VALIDATION_ERROR_CODE, $ratingProp));
            return;
        }

        return true;
    }


    private function validateFile($arField, $arFile, $rawValue)
    {
        $maxFileSize = intval(Option::get(SELF::DEFAULT_MODULE_ID, 'MAX_FILE_SIZE')) * 1024 * 1024;

        if ($maxFileSize) {
            if ($arFile['size'] > $maxFileSize) {
                $error = 'Поле: "' . $arField['NAME'] . '"' . ' содержит файл: "' . $arFile['name'] . '" размером: ' . $arFile['size'] . ' байт, максимально допустимый размер файла: ' . $maxFileSize . ' байт';
                $this->addError(new Error($error, self::DEFAULT_FIELD_VALIDATION_ERROR_CODE, $arField['CODE']));
                return [
                    'FIELD_IBLOCK_ID' => $arField['IBLOCK_ID'],
                    'FIELD_ID' => $arField['ID'],
                    'FIELD_CODE' => $arField['CODE'],
                    'FIELD_VALUE' => $rawValue,
                    'FIELD_VALID' => false,
                    'FIELD_ERROR' => $error,
                ];
            }
        }

        $supportedTypes = [];
        $supportedTypesRaw = preg_split('/(\s*,*\s*)*,+(\s*,*\s*)*/', trim(strval($arField['FILE_TYPE'])));
        if ($supportedTypesRaw) {
            $arSupportedTypesRawFiltered = array_filter($supportedTypesRaw, function ($value) {
                return $value !== null && $value !== '';
            });
            $supportedTypes = array_values($arSupportedTypesRawFiltered);
        }

        if ($supportedTypes) {
            $currentFileExt = GetFileExtension($arFile['name']);
            if (!in_array($currentFileExt, $supportedTypes)) {
                $error = 'Поле: "' . $arField['NAME'] . '" содержит файл: "' . $arFile['name'] . '" неподдерживаемого типа: "' . $currentFileExt . '"' . ' поддерживаемые типы: "' . implode(' ', $supportedTypes) . '"';
                $this->addError(new Error($error, self::DEFAULT_FIELD_VALIDATION_ERROR_CODE, $arField['CODE']));
                return [
                    'FIELD_IBLOCK_ID' => $arField['IBLOCK_ID'],
                    'FIELD_ID' => $arField['ID'],
                    'FIELD_CODE' => $arField['CODE'],
                    'FIELD_VALUE' => $rawValue,
                    'FIELD_VALID' => false,
                    'FIELD_ERROR' => $error,
                ];
            }
        }

        return true;
    }

    private function validateField($arField, $_value, $bannedSymbols = [])
    {
        if (is_array($_value)) {
            $value = $_value;
        } else {
            $value = trim(strval($_value));
        }

        // Проверка на обязательность заполнения
        if ($arField['IS_REQUIRED'] == 'Y' && empty($value)) {
            $error = 'Поле: "' . $arField['NAME'] . '" является обязательным для заполнения';
            $this->addError(new Error($error, self::DEFAULT_FIELD_VALIDATION_ERROR_CODE, $arField['CODE']));
            return [
                'FIELD_IBLOCK_ID' => $arField['IBLOCK_ID'],
                'FIELD_ID' => $arField['ID'],
                'FIELD_CODE' => $arField['CODE'],
                'FIELD_VALUE' => $value,
                'FIELD_VALID' => false,
                'FIELD_ERROR' => $error,
            ];
        }

        if ($arField['PROPERTY_TYPE'] === "F") {
            $maxFilesAmount = intval(Option::get(self::DEFAULT_MODULE_ID, 'MAX_FILES_AMOUNT'));

            if ($arField['MULTIPLE'] !== "Y") {
                $maxFilesAmount = 1;
            }

            if ($maxFilesAmount) {
                if (count($value) > $maxFilesAmount) {
                    $error = 'Поле: "' . $arField['NAME'] . '"' . ' содержит ' . count($value) . ' файлов, максимально допустимое количество файлов: ' . $maxFilesAmount;
                    $this->addError(new Error($error, self::DEFAULT_FIELD_VALIDATION_ERROR_CODE, $arField['CODE']));
                    return [
                        'FIELD_IBLOCK_ID' => $arField['IBLOCK_ID'],
                        'FIELD_ID' => $arField['ID'],
                        'FIELD_CODE' => $arField['CODE'],
                        'FIELD_VALUE' => $value,
                        'FIELD_VALID' => false,
                        'FIELD_ERROR' => $error,
                    ];
                }
            }

            $maxFilesSize = intval(Option::get(self::DEFAULT_MODULE_ID, 'MAX_FILES_SIZES')) * 1024 * 1024;

            $currentTotalSize = 0;

            foreach ($value as $file) {
                if (!$this->validateFile($arField, $file, $value)) {
                    return;
                }

                $currentTotalSize += $file['size'];

                if ($maxFilesSize) {
                    if ($currentTotalSize > $maxFilesSize) {
                        $error = 'Поле: "' . $arField['NAME'] . '"' . ' содержит файлы суммарным размером: ' . $currentTotalSize . ' байт, максимально допустимый суммарный размер файлов: ' . $maxFilesSize . ' байт';
                        $this->addError(new Error($error, self::DEFAULT_FIELD_VALIDATION_ERROR_CODE, $arField['CODE']));
                        return [
                            'FIELD_IBLOCK_ID' => $arField['IBLOCK_ID'],
                            'FIELD_ID' => $arField['ID'],
                            'FIELD_CODE' => $arField['CODE'],
                            'FIELD_VALUE' => $value,
                            'FIELD_VALID' => false,
                            'FIELD_ERROR' => $error,
                        ];
                    }
                }

                if ($arField['MULTIPLE'] !== "Y") {
                    break;
                }
            }
        } elseif ($arField['PROPERTY_TYPE'] === "N") {
            if (!is_numeric($value)) {
                $error = 'Поле: "' . $arField['NAME'] . '" должно быть числом';
                $this->addError(new Error($error, self::DEFAULT_FIELD_VALIDATION_ERROR_CODE, $arField['CODE']));
                return [
                    'FIELD_IBLOCK_ID' => $arField['IBLOCK_ID'],
                    'FIELD_ID' => $arField['ID'],
                    'FIELD_CODE' => $arField['CODE'],
                    'FIELD_VALUE' => $value,
                    'FIELD_VALID' => false,
                    'FIELD_ERROR' => $error,
                ];
            }
        } else {
            // Проверка на наличие запрещенных символов
            if (!is_array($value) && strlen($value)) {
                if ($bannedSymbols) {
                    foreach ($bannedSymbols as $bannedSymbol) {
                        if (strpos($value, $bannedSymbol) !== false) {
                            $error = 'Поле: "' . $arField['NAME'] . '" содержит один из запрещенных символов: "' . implode(' ', $bannedSymbols) . '"';
                            $this->addError(new Error($error, self::DEFAULT_FIELD_VALIDATION_ERROR_CODE, $arField['CODE']));
                            return [
                                'FIELD_IBLOCK_ID' => $arField['IBLOCK_ID'],
                                'FIELD_ID' => $arField['ID'],
                                'FIELD_CODE' => $arField['CODE'],
                                'FIELD_VALUE' => $value,
                                'FIELD_VALID' => false,
                                'FIELD_ERROR' => $error,
                            ];
                        }
                    }
                }
            }
        }

        return [
            'FIELD_IBLOCK_ID' => $arField['IBLOCK_ID'],
            'FIELD_ID' => $arField['ID'],
            'FIELD_CODE' => $arField['CODE'],
            'FIELD_VALUE' => $value,
            'FIELD_VALID' => true,
            'FIELD_ERROR' => '',
        ];
    }

    private function validateCaptcha($token)
    {
        if (!$token) {
            throw new \Exception('Ожидался токен от капчи. Запрос должен иметь заполненное POST поле: "g-recaptcha-response"');
        }

        $secretCaptchaKey = Option::get(self::DEFAULT_MODULE_ID, 'GOOGLE_CAPTCHA_SECRET_KEY');

        $httpClient = new HttpClient();
        $googleCaptchaResponse = Json::decode($httpClient->post(self::DEFAULT_GOOGLE_URL, ['secret' => $secretCaptchaKey, 'response' => $token], true));

        if (!$googleCaptchaResponse['success']) {
            throw new \Exception('Произошла ошибка при попытке обработать ответ от сервера капчи, проверьте задан ли параметр "GOOGLE_CAPTCHA_SECRET_KEY" в настройках модуля');
        }
    }

    private function validateAgreement($arDataRaw)
    {
        $agreementProp = Option::get(self::DEFAULT_MODULE_ID, 'AGREEMENT_PROPERTY');

        $agreementId = intval($arDataRaw[$agreementProp]);

        if ($agreementId <= 0) {
            $error = 'Поле является обязательным для заполнения';
            $this->addError(new Error($error, self::DEFAULT_FIELD_VALIDATION_ERROR_CODE, $agreementProp));
            return;
        }

        $agreement = new Agreement($agreementId);

        if (!$agreement->isExist() || !$agreement->isActive()) {
            throw new \Exception('Соглашение c id ' . $agreementId . ' не найдено или не активно');
        }

        return true;
    }

    private function validateIblock($arDataRaw)
    {
        $iblockProp = trim(Option::get(self::DEFAULT_MODULE_ID, 'IBLOCK_PROPERTY'));

        if (!$iblockProp) {
            throw new \Exception('Не задан код поля инфоблока в настройках модуля');
        }

        $iblockId = intval($arDataRaw[$iblockProp]);

        if ($iblockId <= 0) {
            $error = 'Поле является обязательным для заполнения';
            $this->addError(new Error($error, self::DEFAULT_FIELD_VALIDATION_ERROR_CODE, $iblockProp));
            return;
        }

        if (!\CIBlock::GetList([], ['ID' => $iblockId])->Fetch()) {
            throw new \Exception('Инфоблок c id ' . $iblockId . ' не найден');
        }

        $arAllowedIblocks = explode(',', Option::get(self::DEFAULT_MODULE_ID, 'RESTRICTIONS_IBLOCK_ID'));

        if (!is_array($arAllowedIblocks) || empty($arAllowedIblocks)) {
            throw new \Exception('Не заданы разрешенные инфоблоки в настройках модуля');
        }

        if (!in_array($arDataRaw[$iblockProp], $arAllowedIblocks)) {
            throw new \Exception('Инфоблок c id ' . $iblockId . ' не разрешен для использования');
        }

        return true;
    }

    public function addAction()
    {
        global $APPLICATION;

        try {
            // В этой версии модуль использует инфоблок как основное хранилище данных
            if (!Loader::includeModule('iblock')) {
                throw new \Exception('Модуль инфоблоков не установлен');
            }

            $request = $this->getRequest();

            $arDataRaw = $request->getPostList()->toArray();

            // Проверка что данные отправлены используя сайт с которого была отправлена форма
            // Данные должны содержать идентификатор сессии битрикса
            if ($arDataRaw['sessid'] !== bitrix_sessid()) {
                throw new \Exception('Неверный идентификатор сессии');
            }

            if (!$_SERVER['HTTP_USER_AGENT']) {
                throw new \Exception('Поисковые боты не могут оставлять отзывы');
            } elseif (preg_match('/bot|crawl|curl|dataprovider|search|get|spider|find|java|majesticsEO|google|yahoo|teoma|contaxe|yandex|libwww-perl|facebookexternalhit/i', $_SERVER['HTTP_USER_AGENT'])) {
                throw new \Exception('Поисковые боты не могут оставлять отзывы');
            }

            // Проверка капчи если она включена
            $useCaptcha = Option::get(self::DEFAULT_MODULE_ID, 'USE_CAPTCHA') == "Y";

            if ($useCaptcha) {
                $this->validateCaptcha($arDataRaw['g-recaptcha-response']);
            }

            // v2 пользовательское соглашение
            $useCheckAgreement = Option::get(self::DEFAULT_MODULE_ID, 'USE_AGREEMENT_CHECK') == "Y";

            if ($useCheckAgreement) {
                if (!$this->validateAgreement($arDataRaw)) {
                    return;
                }
            }

            $bannedSymbols = [];
            $bannedSymbolsRaw = preg_split('/(\s*,*\s*)*,+(\s*,*\s*)*/', trim(strval(Option::get(self::DEFAULT_MODULE_ID, 'BANNED_SYMBOLS'))));
            if ($bannedSymbolsRaw) {
                $bannedSymbolsRawFiltered = array_filter($bannedSymbolsRaw, function ($value) {
                    return $value !== null && $value !== '';
                });
                $bannedSymbols = array_values($bannedSymbolsRawFiltered);
            }

            if (!$this->validateRating($arDataRaw)) {
                return;
            }

            if (!$this->validateIblock($arDataRaw)) {
                return;
            }

            $iblockProp = trim(Option::get(self::DEFAULT_MODULE_ID, 'IBLOCK_PROPERTY'));

            if (!$iblockProp) {
                throw new \Exception('Не задан код поля инфоблока в настройках модуля');
            }

            $iblockId = intval($arDataRaw[$iblockProp]);

            $query = PropertyTable::query();
            $query->setSelect(['ID', 'IBLOCK_ID', 'CODE', 'NAME', 'PROPERTY_TYPE', 'IS_REQUIRED', 'MULTIPLE', 'FILE_TYPE']);
            $query->where('IBLOCK_ID', $iblockId);
            $query->where('ACTIVE', 'Y');
            $query->where('CODE', '!=', '');

            $arProps = $query->exec()->fetchAll();

            $arDataValid = [];

            $postValue = [];

            foreach ($arProps as $arProp) {
                // Поддержка только полей имеющий символьный код
                if ($arProp['CODE']) {
                    if ($arProp['PROPERTY_TYPE'] === "F") {
                        $postRawValue = $request->getFile($arProp['CODE']);

                        $postValue = [];

                        if (is_array($postRawValue)) {
                            foreach ($postRawValue['size'] as $key => $size) {
                                if ($size <= 0) {
                                    continue;
                                }
                                $postValue[] = [
                                    'name' => $postRawValue['name'][$key],
                                    'type' => $postRawValue['type'][$key],
                                    'tmp_name' => $postRawValue['tmp_name'][$key],
                                    'error' => $postRawValue['error'][$key],
                                    'size' => $postRawValue['size'][$key],
                                ];
                            }
                        }

                        $arResult = $this->validateField($arProp, $postValue, $bannedSymbols);
                    } else {
                        $arResult = $this->validateField($arProp, $arDataRaw[$arProp['CODE']], $bannedSymbols);
                    }

                    if ($arResult['FIELD_VALID']) {
                        if ($arProp['PROPERTY_TYPE'] === "F") {
                            $arDataValid[$arProp['CODE']] = $postValue;
                        } else {
                            $arDataValid[$arProp['CODE']] = $arDataRaw[$arProp['CODE']];
                        }
                    } else {
                        return;
                    }
                }
            }

            $arDataValid['date'] = (new DateTime())->format("d.m.Y H:i:s");

            $arFields = [
                'IBLOCK_ID' => $iblockId,
                'NAME' => 'Отзыв ' . (new DateTime())->format("d.m.Y H:i:s"),
                'ACTIVE' => 'N',
                'PROPERTY_VALUES' => $arDataValid
            ];

            $dbEl = new \CIBlockElement;

            $dbElResult = $dbEl->Add($arFields);

            if (!$dbElResult) {
                throw new \Exception($dbEl->LAST_ERROR);
            }

            // Нотификация администратора
            $useNotify = Option::get(self::DEFAULT_MODULE_ID, 'USE_NOTIFY') == "Y";

            if ($useNotify) {
                $server = Context::getCurrent()->getServer();
                $userAgent = $request->getUserAgent();
                $userId = CurrentUser::get()->getId();
                $userIp = $request->getRemoteAddress();
                $page = $server->get('HTTP_REFERER');
                $sessionId = bitrix_sessid();

                $arDataUser = [
                    'USER_ID' => intval($userId),
                    'SESSION_ID' => strval($sessionId),
                    'IP' => strval($userIp),
                    'PAGE' => strval($page),
                    'USER_AGENT' => strval($userAgent),
                ];

                $arDataMerged = array_merge($arDataValid, $arDataUser);

                $notifyEvent = Option::get(self::DEFAULT_MODULE_ID, 'NOTIFY_TYPE');
                $notifyEmail = Option::get(self::DEFAULT_MODULE_ID, 'NOTIFY_EMAIL');
                $notifyResult = MailEvent::send([
                    'EVENT_NAME' => $notifyEvent,
                    'LID' => Context::getCurrent()->getSite(),
                    'C_FIELDS' => array_merge($arDataMerged, ['EMAIL_TO' => $notifyEmail]),
                ]);

                if (!$notifyResult->isSuccess()) {
                    throw new \Exception(implode(", ", $notifyResult->getErrorMessages()));
                }
            }

            // v2 Добавление в список согласий
            if ($useCheckAgreement) {
                $agreementId = null;

                $agreementProp = Option::get(self::DEFAULT_MODULE_ID, 'AGREEMENT_PROPERTY');

                if (isset($arDataValid[$agreementProp])) {
                    $agreementId = intval($arDataValid[$agreementProp]);
                } else {
                    $agreementId = intval($arDataRaw[$agreementProp]);
                }

                if ($agreementId > 0) {
                    Consent::addByContext($agreementId, null, null, [
                        'URL' => Context::getCurrent()->getServer()->get('HTTP_REFERER'),
                    ]);
                }
            }

            $useSuccessContent = Option::get(self::DEFAULT_MODULE_ID, 'USE_SUCCESS_CONTENT');

            $templateIncludeResult = "";

            if ($useSuccessContent == 'Y') {
                $templateIncludeResult = Option::get(self::DEFAULT_MODULE_ID, 'SUCCESS_CONTENT_DEFAULT');

                $successFile = Option::get(self::DEFAULT_MODULE_ID, 'SUCCESS_FILE');

                if ($successFile) {
                    ob_start();
                    $APPLICATION->IncludeFile($successFile, [
                        'arMutation' => [
                            'PATH' => $successFile,
                            'PARAMS' => $arDataValid,
                        ]
                    ], ["SHOW_BORDER" => false, "MODE" => "php"]);
                    $templateIncludeResult = ob_get_contents();
                    ob_end_clean();
                }
            }

            return $templateIncludeResult;
        } catch (\Throwable $th) {
            $this->addError(new Error($th->getMessage(), $th->getCode()));
            return;

            try {
                $useErrorContent = Option::get(self::DEFAULT_MODULE_ID, 'USE_ERROR_CONTENT');

                if ($useErrorContent == 'Y') {
                    $errorFile = Option::get(self::DEFAULT_MODULE_ID, 'ERROR_FILE');

                    if (!$errorFile) {
                        $this->addError(new Error(Option::get(self::DEFAULT_MODULE_ID, 'ERROR_CONTENT_DEFAULT'), self::DEFAULT_FORM_GENERAL_ERROR_CODE));
                        return;
                    }

                    ob_start();
                    $APPLICATION->IncludeFile($errorFile, [
                        'arMutation' => [
                            'PATH' => $errorFile,
                            'PARAMS' => [],
                        ]
                    ], ["SHOW_BORDER" => false, "MODE" => "php"]);
                    $templateIncludeResult = ob_get_contents();
                    ob_end_clean();
                    $this->addError(new Error($templateIncludeResult));
                    return;
                }

                $this->addError(new Error(self::DEFAULT_ERROR_CONTENT, self::DEFAULT_FORM_GENERAL_ERROR_CODE));
                return;
            } catch (\Throwable $th) {
                if (CurrentUser::get()->isAdmin()) {
                    $this->addError(new Error($th->getMessage(), $th->getCode()));
                    return;
                } else {
                    $this->addError(new Error(self::DEFAULT_ERROR_CONTENT, self::DEFAULT_FORM_GENERAL_ERROR_CODE));
                    return;
                }
            }
        }
    }
}
