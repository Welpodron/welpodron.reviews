<?php

namespace Welpodron\Reviews\Controller;

use Bitrix\Main\Loader;
use Bitrix\Main\Error;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\CurrentUser;

// TODO: Добавить отправку письма

class Receiver extends Controller
{
    const FIELD_VALIDATION_ERROR_CODE = "FIELD_VALIDATION_ERROR";
    const FORM_GENERAL_ERROR_CODE = "FORM_GENERAL_ERROR";

    protected function getDefaultPreFilters()
    {
        return [new ActionFilter\Csrf()];
    }

    public function saveAction()
    {
        global $APPLICATION;
        try {
            $MODULE_ID = "welpodron.reviews";

            $maxFileSize = intval(Option::get($MODULE_ID, 'MAX_FILE_SIZE')) * 1024 * 1024;
            $maxFilesAmount = intval(Option::get($MODULE_ID, 'MAX_FILES_AMOUNT'));
            $iblock = intval(Option::get($MODULE_ID, 'IBLOCK_ID'));

            $bannedSymbols = [];
            $bannedSymbolsRaw = preg_split('/(\s*,*\s*)*,+(\s*,*\s*)*/', trim(strval(Option::get($MODULE_ID, 'BANNED_SYMBOLS'))));
            if ($bannedSymbolsRaw) {
                $bannedSymbolsRawFiltered = array_filter($bannedSymbolsRaw, function ($value) {
                    return $value !== null && $value !== '';
                });
                $bannedSymbols = array_values($bannedSymbolsRawFiltered);
            }

            $request = $this->getRequest();

            $postRawValue = $request->getFile('images');

            if (!Loader::includeModule("iblock")) {
                throw new \Exception('Произошла ошибка при попытке подключения модуля: "iblock"');
            };

            if (!$iblock) {
                throw new \Exception('Неверно указано значение ID инфоблока в настройках модуля "' . $MODULE_ID . '" , текущее значение: "' . $iblock . '" ожидалось число большее нуля');
            }

            $res = \CIBlock::GetProperties($iblock, [], ["CHECK_PERMISSIONS" => "N"]);

            $arValidProps = [];

            while ($res_arr = $res->Fetch()) {
                // Внимание! На данный момент предусмотрена проверка полей только имеющих CODE
                if ($res_arr['CODE']) {
                    // Внимание! Проверка MULTIPLE пока не предусмотрена
                    if ($res_arr['PROPERTY_TYPE'] != 'F') {
                        $postValue = trim(strval($request->getPost($res_arr['CODE'])));
                        // Проверка на наличие обязательных полей (в принципе не обязательно, так как CIBlockElement::Add проверяет поля)
                        if ($res_arr['IS_REQUIRED'] == 'Y') {
                            if (!strlen($postValue)) {
                                $this->addError(new Error('Поле: "' . $res_arr['NAME'] . '" является обязательным для заполнения', self::FIELD_VALIDATION_ERROR_CODE, $res_arr['CODE']));
                                return;
                            }
                        }
                        // Проверка на наличие запрещенных символов 
                        if (strlen($postValue)) {
                            if ($bannedSymbols) {
                                foreach ($bannedSymbols as $bannedSymbol) {
                                    if (strpos($postValue, $bannedSymbol) !== false) {
                                        $this->addError(new Error('Поле: "' . $res_arr['NAME'] . '" содержит один из запрещенных символов: "' . implode(' ', $bannedSymbols) . '"', self::FIELD_VALIDATION_ERROR_CODE, $res_arr['CODE']));
                                        return;
                                    }
                                }
                            }
                        }

                        // Кастомная проверка поля rating 
                        if ($res_arr['CODE'] == 'rating') {
                            $postValue = intval($postValue);

                            if ($postValue < 1) {
                                $this->addError(new Error('Поле: "' . $res_arr['NAME'] . '" не может быть меньше 1', self::FIELD_VALIDATION_ERROR_CODE, $res_arr['CODE']));
                                return;
                            }

                            if ($postValue > 5) {
                                $this->addError(new Error('Поле: "' . $res_arr['NAME'] . '" не может быть больше 5', self::FIELD_VALIDATION_ERROR_CODE, $res_arr['CODE']));
                                return;
                            }
                        }

                        $arValidProps[$res_arr['CODE']] = $postValue;
                    } else {
                        // Проверка пришедших файлов
                        // Проверка на количество по какой-то причине не предусмотрена битриксом у типа файл?
                        // Можно попробовать использовать MULTIPLE_CNT: https://dev.1c-bitrix.ru/api_help/iblock/fields.php#fproperty
                        $postRawValue = $request->getFile($res_arr['CODE']);
                        $postValue = [];
                        foreach ($postRawValue['size'] as $key => $size) {
                            // Только не пустые
                            if ($size) {
                                // Проверка на размеры
                                if ($maxFileSize) {
                                    if ($size > $maxFileSize) {
                                        $this->addError(
                                            new Error(
                                                'Загруженный файл: "' . $postRawValue['name'][$key] . '" для поля: "' . $res_arr['NAME'] . '" имеет размер - "' . round($size / 1024 / 1024, 2) . 'МБ" превышающий максимально допустимый размер - "' . round($maxFileSize / 1024 / 1024, 2) . 'МБ"',
                                                self::FIELD_VALIDATION_ERROR_CODE,
                                                $res_arr['CODE']
                                            )
                                        );
                                        return;
                                    }
                                }

                                // Проверка на поддерживаемые типы
                                $supportedTypes = [];
                                $supportedTypesRaw = preg_split('/(\s*,*\s*)*,+(\s*,*\s*)*/', trim(strval($res_arr['FILE_TYPE'])));
                                if ($supportedTypesRaw) {
                                    $arSupportedTypesRawFiltered = array_filter($supportedTypesRaw, function ($value) {
                                        return $value !== null && $value !== '';
                                    });
                                    $supportedTypes = array_values($arSupportedTypesRawFiltered);
                                }

                                if ($supportedTypes) {
                                    $currentFileExt = GetFileExtension($postRawValue['name'][$key]);
                                    if (!in_array($currentFileExt, $supportedTypes)) {
                                        $this->addError(
                                            new Error(
                                                'Загруженный файл: "' . $postRawValue['name'][$key] . '" для поля: "' . $res_arr['NAME'] . '" имеет неподдерживаемый тип - "' . $currentFileExt . '" допустимые типы - "' . implode(', ', $supportedTypes) . '"',
                                                self::FIELD_VALIDATION_ERROR_CODE,
                                                $res_arr['CODE']
                                            )
                                        );
                                        return;
                                    }
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
                        // Проверка на наличие обязательных полей (в принципе не обязательно, так как CIBlockElement::Add проверяет поля)
                        if ($res_arr['IS_REQUIRED'] == 'Y') {
                            if (!$postValue) {
                                $this->addError(new Error('Поле: "' . $res_arr['NAME'] . '" является обязательным для заполнения', self::FIELD_VALIDATION_ERROR_CODE, $res_arr['CODE']));
                                return;
                            }
                        }
                        // Проверка на количество пришедших файлов
                        if ($maxFilesAmount) {
                            if (count($postValue) > $maxFilesAmount) {
                                $this->addError(new Error('Максимально допустимое количество файлов для поля: "' . $res_arr['NAME'] . '" - "' . $maxFilesAmount . '" загружено - "' . count($postValue) . '"', self::FIELD_VALIDATION_ERROR_CODE, $res_arr['CODE']));
                                return;
                            }
                        }

                        $arValidProps[$res_arr['CODE']] = $postValue;
                    }
                }
            }

            $arFields = [
                "IBLOCK_ID" => $iblock,
                'NAME' => 'Отзыв: ' . time(),
                'ACTIVE' => 'N',
                'PROPERTY_VALUES' => $arValidProps
            ];

            $el = new \CIBlockElement;

            $result = $el->Add($arFields);

            if (!$result) {
                throw new \Exception($el->LAST_ERROR);
            }

            ob_start();
            $APPLICATION->IncludeFile("/local/modules/welpodron.reviews/templates/add_review_success.php", [], ["SHOW_BORDER" => false, "MODE" => "php"]);
            $templateIncludeResult = ob_get_contents();
            ob_end_clean();

            return $templateIncludeResult;
        } catch (\Throwable $th) {
            if (CurrentUser::get()->isAdmin()) {
                $this->addError(new Error($th->getMessage(), $th->getCode()));
                return;
            }

            ob_start();
            $APPLICATION->IncludeFile("/local/modules/welpodron.reviews/templates/add_review_error.php", [], ["SHOW_BORDER" => false, "MODE" => "php"]);
            $templateIncludeResult = ob_get_contents();
            ob_end_clean();
            $this->addError(new Error($templateIncludeResult, self::FORM_GENERAL_ERROR_CODE));
            return;
        }
    }
}
