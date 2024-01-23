<?

use Bitrix\Main\ModuleManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\Config\Option;
use Bitrix\Main\IO\Directory;

IncludeModuleLangFile(__FILE__);

class welpodron_reviews extends CModule
{
    var $MODULE_ID = 'welpodron.reviews';

    private $DEFAULT_OPTIONS = [];

    const DEFAULT_IBLOCK_TYPE = "welpodron_reviews";
    const DEFAULT_MAIL_EVENT_TYPE = 'WELPODRON_REVIEWS';

    public function InstallManagerMailEvents()
    {
        global $APPLICATION, $DB;

        try {
            $dbSites = CSite::GetList($by = "sort", $order = "desc");
            while ($arSite = $dbSites->Fetch()) {
                $arSites[] = $arSite["LID"];
            }

            foreach ($arSites as $siteId) {
                $dbEt = CEventType::GetByID(self::DEFAULT_MAIL_EVENT_TYPE, $siteId);
                $arEt = $dbEt->Fetch();

                if (!$arEt) {
                    $et = new CEventType;

                    $DB->StartTransaction();

                    $et = $et->Add([
                        'LID' => $siteId,
                        'EVENT_NAME' => self::DEFAULT_MAIL_EVENT_TYPE,
                        'NAME' => 'Добавление отзыва',
                        'EVENT_TYPE' => 'email',
                        'DESCRIPTION'  => '
                        #USER_ID# - ID Пользователя
                        #SESSION_ID# - Сессия пользователя
                        #IP# - IP Адрес пользователя
                        #PAGE# - Страница отправки
                        #USER_AGENT# - UserAgent
                        #author# - Автор отзыва
                        #review# - Текст отзыва
                        #rating# - Рейтинг отзыва
                        #product_number# - Артикул элемента
                        #EMAIL_TO# - Email получателя письма
                        '
                    ]);

                    if (!$et) {
                        $DB->Rollback();

                        $APPLICATION->ThrowException('Не удалось создать почтовое событие' . $APPLICATION->LAST_ERROR);

                        return false;
                    } else {
                        $DB->Commit();
                    }
                }

                $dbMess = CEventMessage::GetList($by = "id", $order = "desc", ['SITE_ID' => $siteId, 'TYPE_ID' => self::DEFAULT_MAIL_EVENT_TYPE]);
                $arMess = $dbMess->Fetch();

                if (!$arMess) {
                    $mess = new CEventMessage;

                    $DB->StartTransaction();

                    $messId = $mess->Add([
                        'ACTIVE' => 'Y',
                        'EVENT_NAME' => self::DEFAULT_MAIL_EVENT_TYPE,
                        'LID' => $siteId,
                        'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
                        'EMAIL_TO' => '#EMAIL_TO#',
                        'SUBJECT' => '#SITE_NAME#: Добавлен отзыв',
                        'BODY_TYPE' => 'html',
                        'MESSAGE' => '
                        <!DOCTYPE html>
                        <html lang="ru">
                        <head>
                        <meta charset="utf-8">
                        <title>Новый отзыв</title>
                        </head>
                        <body>
                        <p>
                        На сайте был оформлен новый отзыв, ожидающий проверки
                        </p>
                        <p>
                        Артикул товара:
                        </p>
                        <p>
                        #product_number#
                        </p>
                        <p>
                        Автор отзыва:
                        </p>
                        <p>
                        #author#
                        </p>
                        <p>
                        Содержимое отзыва:
                        </p>
                        <p>
                        #review#
                        </p>
                        <p>
                        Рейтинг отзыва:
                        </p>
                        <p>
                        #rating#
                        </p>
                        <p>
                        Отправлено пользователем: #USER_ID#
                        </p>
                        <p>
                        Сессия пользователя: #SESSION_ID#
                        </p>
                        <p>
                        IP адрес отправителя: #IP#
                        </p>
                        <p>
                        Страница отправки: <a href="#PAGE#">#PAGE#</a>
                        </p>
                        <p>
                        Используемый USER AGENT: #USER_AGENT#
                        </p>
                        <p>
                        Письмо сформировано автоматически.
                        </p>
                        </body>
                        </html>
                        '
                    ]);

                    if (!$messId) {
                        $DB->Rollback();

                        $APPLICATION->ThrowException('Произошла ошибка при создании почтового события' . $mess->LAST_ERROR);

                        return false;
                    } else {
                        $DB->Commit();
                    }
                }
            }

            $this->DEFAULT_OPTIONS['USE_NOTIFY'] = "Y";
            $this->DEFAULT_OPTIONS['NOTIFY_TYPE'] = self::DEFAULT_MAIL_EVENT_TYPE;
            $this->DEFAULT_OPTIONS['NOTIFY_EMAIL'] = Option::get('main', 'email_from');
        } catch (\Throwable $th) {
            $APPLICATION->ThrowException($th->getMessage() . '\n' . $th->getTraceAsString());
            return false;
        }

        return true;
    }

    public function UnInstallManagerMailEvents()
    {
        $dbSites = CSite::GetList($by = "sort", $order = "desc");
        while ($arSite = $dbSites->Fetch()) {
            $arSites[] = $arSite["LID"];
        }

        foreach ($arSites as $siteId) {
            $dbMess = CEventMessage::GetList($by = "id", $order = "desc", ['SITE_ID' => $siteId, 'TYPE_ID' => self::DEFAULT_MAIL_EVENT_TYPE]);
            $arMess = $dbMess->Fetch();
            CEventMessage::Delete($arMess['ID']);
        }

        CEventType::Delete(self::DEFAULT_MAIL_EVENT_TYPE);
    }

    public function DoInstall()
    {
        global $APPLICATION;

        if (!CheckVersion(ModuleManager::getVersion('main'), '14.00.00')) {
            $APPLICATION->ThrowException(GetMessage("WELPODRON_REVIEWS_INSTALL_ERROR_VERSION"));
            return false;
        }

        if (!Loader::includeModule('welpodron.core')) {
            $APPLICATION->ThrowException('Модуль welpodron.core не был найден');
            return false;
        }

        // FIX Ранней проверки еще то установки 
        if (!Loader::includeModule("iblock")) {
            $APPLICATION->ThrowException('Не удалось подключить модуль iblock нужный для работы модуля');
            return false;
        }

        if (!$this->InstallFiles()) {
            return false;
        }

        if (!$this->InstallDb()) {
            return false;
        }

        if (!$this->InstallManagerMailEvents()) {
            return false;
        }

        if (!$this->InstallOptions()) {
            return false;
        }

        ModuleManager::registerModule($this->MODULE_ID);

        $APPLICATION->IncludeAdminFile('Установка модуля ' . $this->MODULE_ID, __DIR__ . '/step.php');
    }

    public function DoUninstall()
    {
        global $APPLICATION;

        $request = Context::getCurrent()->getRequest();

        if ($request->get("step") < 2) {
            $APPLICATION->IncludeAdminFile('Деинсталляция модуля ' . $this->MODULE_ID, __DIR__ . '/unstep1.php');
        } elseif ($request->get("step") == 2) {
            $this->UnInstallFiles();
            $this->UnInstallOptions();
            $this->UnInstallManagerMailEvents();

            if ($request->get("savedata") != "Y") {
                $this->UnInstallDB();
            }

            ModuleManager::unRegisterModule($this->MODULE_ID);
            $APPLICATION->IncludeAdminFile('Деинсталляция модуля ' . $this->MODULE_ID, __DIR__ . '/unstep2.php');
        }
    }

    public function InstallOptions()
    {
        global $APPLICATION;

        try {
            foreach ($this->DEFAULT_OPTIONS as $optionName => $optionValue) {
                Option::set($this->MODULE_ID, $optionName, $optionValue);
            }
        } catch (\Throwable $th) {
            $APPLICATION->ThrowException($th->getMessage() . '\n' . $th->getTraceAsString());
            return false;
        }
        return true;
    }

    public function UnInstallOptions()
    {
        global $APPLICATION;

        try {
            Option::delete($this->MODULE_ID);
        } catch (\Throwable $th) {
            $APPLICATION->ThrowException($th->getMessage() . '\n' . $th->getTraceAsString());
            return false;
        }
        return true;
    }

    public function InstallDb()
    {
        global $APPLICATION, $DB;

        try {
            if (!Loader::includeModule("iblock")) {
                $APPLICATION->ThrowException(GetMessage("WELPODRON_REVIEWS_INSTALL_ERROR_LOADER_IBLOCK"));
                return false;
            };

            // Попытаться найти тип 
            $iblockType = CIBlockType::GetList([], ['=ID' => self::DEFAULT_IBLOCK_TYPE])->Fetch();

            if (!$iblockType) {
                $iblockType = new CIBlockType;

                $arFields = [
                    'ID' => self::DEFAULT_IBLOCK_TYPE,
                    'SECTIONS' => 'N',
                    'LANG' => [
                        'en' => [
                            'NAME' => 'Welpodron reviews',
                            'ELEMENT_NAME' => 'Reviews',
                        ],
                        'ru' => [
                            'NAME' => 'Welpodron отзывы',
                            'ELEMENT_NAME' => 'Отзывы'
                        ],
                    ]
                ];

                $DB->StartTransaction();

                $addResult = $iblockType->Add($arFields);

                if (!$addResult) {
                    $DB->Rollback();

                    $APPLICATION->ThrowException(GetMessage("WELPODRON_REVIEWS_INSTALL_ERROR_IBLOCK_TYPE_INSTALL") . $iblockType->LAST_ERROR);

                    return false;
                } else {
                    $DB->Commit();
                }
            }

            $dbSites = CSite::GetList($by = "sort", $order = "desc");
            while ($arSite = $dbSites->Fetch()) {
                $arSites[] = $arSite["LID"];
            }

            $arFoundIblocks = [];
            $dbIblocks = CIBlock::GetList([], ['TYPE' => self::DEFAULT_IBLOCK_TYPE]);

            while ($arIblock = $dbIblocks->Fetch()) {
                $arFoundIblocks[] = $arIblock['ID'];
            }

            if (empty($arFoundIblocks) || count($arFoundIblocks) == 0) {
                $firstIblock = new CIBlock;

                $arFields = [
                    "NAME" => 'Отзывы на товар',
                    "IBLOCK_TYPE_ID" => self::DEFAULT_IBLOCK_TYPE,
                    "ELEMENTS_NAME" => "Отзывы на товар",
                    "ELEMENT_NAME" => "Отзыв на товар",
                    "ELEMENT_ADD" => "Добавить отзыв на товар",
                    "ELEMENT_EDIT" => "Изменить отзыв на товар",
                    "ELEMENT_DELETE" => "Удалить отзыв на товар",
                    "SITE_ID" => $arSites,
                ];

                $DB->StartTransaction();

                $firstIblockId = $firstIblock->Add($arFields);

                if (!$firstIblockId) {
                    $DB->Rollback();

                    $APPLICATION->ThrowException(GetMessage("WELPODRON_REVIEWS_INSTALL_ERROR_IBLOCK_INSTALL") . $firstIblock->LAST_ERROR);

                    return false;
                } else {
                    $DB->Commit();
                }

                // TODO: 2 - Группа всех пользователей можно получать динамически ?
                CIBlock::SetPermission($firstIblockId, ["2" => "R"]);

                $arProps = [
                    [
                        "NAME" => "Артикул элемента",
                        "CODE" => "product_number",
                        "IS_REQUIRED" => "Y",
                        "IBLOCK_ID" => $firstIblockId
                    ],
                    [
                        "NAME" => "Дата публикации отзыва",
                        "CODE" => "date",
                        "PROPERTY_TYPE" => "S",
                        "USER_TYPE" => "DateTime",
                        "IBLOCK_ID" => $firstIblockId
                    ],
                    [
                        "NAME" => "Оценка",
                        "CODE" => "rating",
                        "PROPERTY_TYPE" => "N",
                        "IS_REQUIRED" => "Y",
                        "IBLOCK_ID" => $firstIblockId
                    ],
                    [
                        "NAME" => "Достоинства",
                        "CODE" => "advantages",
                        "USER_TYPE" => "",
                        "ROW_COUNT" => 3,
                        "IBLOCK_ID" => $firstIblockId
                    ],
                    [
                        "NAME" => "Недостатки",
                        "CODE" => "disadvantages",
                        "USER_TYPE" => "",
                        "ROW_COUNT" => 3,
                        "IBLOCK_ID" => $firstIblockId
                    ],
                    [
                        "NAME" => "Отзыв",
                        "CODE" => "review",
                        "USER_TYPE" => "",
                        "ROW_COUNT" => 3,
                        "IS_REQUIRED" => "Y",
                        "IBLOCK_ID" => $firstIblockId
                    ],
                    [
                        "NAME" => "Автор",
                        "CODE" => "author",
                        "IS_REQUIRED" => "Y",
                        "IBLOCK_ID" => $firstIblockId
                    ],
                    [
                        "NAME" => "Изображения",
                        "CODE" => "images",
                        "PROPERTY_TYPE" => "F",
                        "MULTIPLE" => "Y",
                        "FILE_TYPE" => "jpg, png, jpeg",
                        "IBLOCK_ID" => $firstIblockId
                    ],
                ];

                foreach ($arProps as $prop) {
                    $iblockProp = new CIBlockProperty;

                    $DB->StartTransaction();

                    $iblockPropId = $iblockProp->Add($prop);

                    if (!$iblockPropId) {
                        $DB->Rollback();

                        $APPLICATION->ThrowException(GetMessage("WELPODRON_REVIEWS_INSTALL_ERROR_IBLOCK_PROPERTY_INSTALL") . $iblockProp->LAST_ERROR);

                        return false;
                    } else {
                        $DB->Commit();
                    }
                }

                $secondIblock = new CIBlock;

                $arFields = [
                    "NAME" => 'Отзывы на сайт',
                    "IBLOCK_TYPE_ID" => self::DEFAULT_IBLOCK_TYPE,
                    "ELEMENTS_NAME" => "Отзывы на сайт",
                    "ELEMENT_NAME" => "Отзыв на сайт",
                    "ELEMENT_ADD" => "Добавить отзыв на сайт",
                    "ELEMENT_EDIT" => "Изменить отзыв на сайт",
                    "ELEMENT_DELETE" => "Удалить отзыв на сайт",
                    "SITE_ID" => $arSites,
                ];

                $DB->StartTransaction();

                $secondIblockId = $secondIblock->Add($arFields);

                if (!$secondIblockId) {
                    $DB->Rollback();

                    $APPLICATION->ThrowException(GetMessage("WELPODRON_REVIEWS_INSTALL_ERROR_IBLOCK_INSTALL") . $firstIblock->LAST_ERROR);

                    return false;
                } else {
                    $DB->Commit();
                }

                // TODO: 2 - Группа всех пользователей можно получать динамически ?
                CIBlock::SetPermission($secondIblockId, ["2" => "R"]);

                $arProps = [
                    [
                        "NAME" => "Дата публикации отзыва",
                        "CODE" => "date",
                        "PROPERTY_TYPE" => "S",
                        "USER_TYPE" => "DateTime",
                        "IBLOCK_ID" => $secondIblockId
                    ],
                    [
                        "NAME" => "Оценка",
                        "CODE" => "rating",
                        "PROPERTY_TYPE" => "N",
                        "IS_REQUIRED" => "Y",
                        "IBLOCK_ID" => $secondIblockId
                    ],
                    [
                        "NAME" => "Отзыв",
                        "CODE" => "review",
                        "USER_TYPE" => "",
                        "ROW_COUNT" => 3,
                        "IS_REQUIRED" => "Y",
                        "IBLOCK_ID" => $secondIblockId
                    ],
                    [
                        "NAME" => "Автор",
                        "CODE" => "author",
                        "IS_REQUIRED" => "Y",
                        "IBLOCK_ID" => $secondIblockId
                    ],
                    [
                        "NAME" => "Изображения",
                        "CODE" => "images",
                        "PROPERTY_TYPE" => "F",
                        "MULTIPLE" => "Y",
                        "FILE_TYPE" => "jpg, png, jpeg",
                        "IBLOCK_ID" => $secondIblockId
                    ],
                ];

                foreach ($arProps as $prop) {
                    $iblockProp = new CIBlockProperty;

                    $DB->StartTransaction();

                    $iblockPropId = $iblockProp->Add($prop);

                    if (!$iblockPropId) {
                        $DB->Rollback();

                        $APPLICATION->ThrowException(GetMessage("WELPODRON_REVIEWS_INSTALL_ERROR_IBLOCK_PROPERTY_INSTALL") . $iblockProp->LAST_ERROR);

                        return false;
                    } else {
                        $DB->Commit();
                    }
                }

                $this->DEFAULT_OPTIONS['RESTRICTIONS_IBLOCK_ID'] = implode(",", [$firstIblockId, $secondIblockId]);
            } else {
                $this->DEFAULT_OPTIONS['RESTRICTIONS_IBLOCK_ID'] = implode(",", $arFoundIblocks);
            }
        } catch (\Throwable $th) {
            $APPLICATION->ThrowException($th->getMessage() . '\n' . $th->getTraceAsString());
            return false;
        }

        return true;
    }

    public function UnInstallDB()
    {
        Loader::includeModule("iblock");
        // Удалить iblock_type
        CIBlockType::Delete(self::DEFAULT_IBLOCK_TYPE);
    }

    public function InstallFiles()
    {
        global $APPLICATION;

        try {
            if (!CopyDirFiles(__DIR__ . '/components/', Application::getDocumentRoot() . '/local/components', true, true)) {
                $APPLICATION->ThrowException('Не удалось скопировать компоненты модуля');
                return false;
            };
        } catch (\Throwable $th) {
            $APPLICATION->ThrowException($th->getMessage() . '\n' . $th->getTraceAsString());
            return false;
        }

        return true;
    }

    public function UnInstallFiles()
    {
        $arComponents = scandir(__DIR__ . '/components/welpodron');

        if ($arComponents) {
            $arComponents = array_diff($arComponents, ['..', '.']);

            foreach ($arComponents as $component) {
                Directory::deleteDirectory(Application::getDocumentRoot() . '/local/components/welpodron/' . $component);
            }
        }
    }

    public function __construct()
    {
        $this->MODULE_ID = 'welpodron.reviews';
        $this->MODULE_NAME = 'Отзывы (welpodron.reviews)';
        $this->MODULE_DESCRIPTION = 'Модуль для работы с отзывами';
        $this->PARTNER_NAME = 'Welpodron';
        $this->PARTNER_URI = 'https://github.com/Welpodron';

        $this->DEFAULT_OPTIONS = [
            'BANNED_SYMBOLS' => '<,>,&,*,^,%,$,`,~,#,href,eval,script,/,\\,=,!,?',
            'USE_AGREEMENT_CHECK' => 'N',
            'USE_CAPTCHA' => 'N',
            'USE_SUCCESS_CONTENT' => 'Y',
            'MAX_FILES_AMOUNT' => 3,
            'MAX_FILE_SIZE' => 5,
            'SUCCESS_CONTENT_DEFAULT' => '<p>Спасибо за ваш отзыв!</p>',
            'USE_ERROR_CONTENT' => 'Y',
            'ERROR_CONTENT_DEFAULT' => '<p>При обработке Вашего запроса произошла ошибка, повторите попытку позже или свяжитесь с администрацией сайта</p>',
        ];

        $arModuleVersion = [];

        include(__DIR__ . "/version.php");

        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
    }
}
