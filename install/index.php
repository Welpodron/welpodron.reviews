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
    const DEFAULT_IBLOCK_TYPE = "welpodron_reviews";
    const DEFAULT_EVENT_TYPE = 'WELPODRON_REVIEWS_FEEDBACK';

    public function DoInstall()
    {
        global $APPLICATION;

        if (!CheckVersion(ModuleManager::getVersion('main'), '14.00.00')) {
            $APPLICATION->ThrowException(GetMessage("WELPODRON_REVIEWS_INSTALL_ERROR_VERSION"));
            return false;
        }

        if (!$this->InstallDb()) {
            return false;
        }

        if (!$this->InstallEvents()) {
            return false;
        }

        if (!$this->InstallFiles()) {
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
            $this->UnInstallEvents();
            $this->UnInstallOptions();
            // По умолчанию БД не удаляется 

            if ($request->get("savedata") != "Y")
                $this->UnInstallDB();

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
            foreach ($this->DEFAULT_OPTIONS as $optionName => $optionValue) {
                Option::delete($this->MODULE_ID, ['name' => $optionName]);
            }
        } catch (\Throwable $th) {
            $APPLICATION->ThrowException($th->getMessage() . '\n' . $th->getTraceAsString());
            return false;
        }
        return true;
    }

    public function InstallEvents()
    {
        global $APPLICATION, $DB;

        try {
            $dbSites = CSite::GetList($by = "sort", $order = "desc");
            while ($arSite = $dbSites->Fetch()) {
                $arSites[] = $arSite["LID"];
            }

            foreach ($arSites as $siteId) {
                $dbEt = CEventType::GetByID(self::DEFAULT_EVENT_TYPE, $siteId);
                $arEt = $dbEt->Fetch();

                if (!$arEt) {
                    $et = new CEventType;

                    $DB->StartTransaction();

                    $et = $et->Add([
                        'LID' => $siteId,
                        'EVENT_NAME' => self::DEFAULT_EVENT_TYPE,
                        'NAME' => 'Добавление отзыва',
                        'EVENT_TYPE' => 'email',
                        'DESCRIPTION'  => '
                        #USER_ID# - ID Пользователя
                        #SESSION_ID# - Сессия пользователя
                        #IP# - IP Адрес пользователя
                        #PAGE# - Страница отправки
                        #USER_AGENT# - UserAgent
                        #AUTHOR# - Автор отзыва
                        #COMMENT# - Текст отзыва
                        #ELEMENT_ARTIKUL# - Артикул товара
                        #EMAIL_TO# - Email получателя письма
                        '
                    ]);

                    if (!$et) {
                        $DB->Rollback();

                        $APPLICATION->ThrowException(GetMessage("WELPODRON_REVIEWS_INSTALL_ERROR_EVENT_TYPE_INSTALL") . $APPLICATION->LAST_ERROR);

                        return false;
                    } else {
                        $DB->Commit();
                    }
                }

                $dbMess = CEventMessage::GetList($by = "id", $order = "desc", ['SITE_ID' => $siteId, 'TYPE_ID' => self::DEFAULT_EVENT_TYPE]);
                $arMess = $dbMess->Fetch();

                if (!$arMess) {
                    $mess = new CEventMessage;

                    $DB->StartTransaction();

                    $messId = $mess->Add([
                        'ACTIVE' => 'Y',
                        'EVENT_NAME' => self::DEFAULT_EVENT_TYPE,
                        'LID' => $siteId,
                        'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
                        'EMAIL_TO' => '#EMAIL_TO#',
                        'SUBJECT' => '#SITE_NAME#: Добавлен отзыв на товар',
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
                        На сайте был добавлен отзыв, ожидающий проверки
                        </p>
                        <p>
                        Артикул товара:
                        </p>
                        <p>
                        #ELEMENT_ARTIKUL#
                        </p>
                        <p>
                        Автор отзыва:
                        </p>
                        <p>
                        #AUTHOR#
                        </p>
                        <p>
                        Содержимое отзыва:
                        </p>
                        <p>
                        #COMMENT#
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

                        $APPLICATION->ThrowException(GetMessage("WELPODRON_REVIEWS_INSTALL_ERROR_EVENT_MESS_INSTALL") . $mess->LAST_ERROR);

                        return false;
                    } else {
                        $DB->Commit();
                    }
                }
            }

            $this->DEFAULT_OPTIONS['USE_NOTIFY'] = "Y";
            $this->DEFAULT_OPTIONS['NOTIFY_TYPE'] = self::DEFAULT_EVENT_TYPE;
            $this->DEFAULT_OPTIONS['NOTIFY_EMAIL'] = Option::get('main', 'email_from');
        } catch (\Throwable $th) {
            $APPLICATION->ThrowException($th->getMessage() . '\n' . $th->getTraceAsString());
            return false;
        }

        return true;
    }

    public function UnInstallEvents()
    {
        $dbSites = CSite::GetList($by = "sort", $order = "desc");
        while ($arSite = $dbSites->Fetch()) {
            $arSites[] = $arSite["LID"];
        }

        foreach ($arSites as $siteId) {
            $dbMess = CEventMessage::GetList($by = "id", $order = "desc", ['SITE_ID' => $siteId, 'TYPE_ID' => self::DEFAULT_EVENT_TYPE]);
            $arMess = $dbMess->Fetch();
            CEventMessage::Delete($arMess['ID']);
        }

        CEventType::Delete(self::DEFAULT_EVENT_TYPE);
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
                            'NAME' => 'Welpodron Отзывы',
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

            // Попытаться найти хотя бы один инфоблок
            $iblockId = null;
            $firstFoundIblock = CIBlock::GetList([], ['TYPE' => self::DEFAULT_IBLOCK_TYPE])->Fetch();

            if (!$firstFoundIblock) {
                $firstIblock = new CIBlock;

                $arFields = [
                    "NAME" => 'Welpodron Отзывы',
                    "IBLOCK_TYPE_ID" => self::DEFAULT_IBLOCK_TYPE,
                    "ELEMENTS_NAME" => "Отзывы",
                    "ELEMENT_NAME" => "Отзыв",
                    "ELEMENT_ADD" => "Добавить отзыв",
                    "ELEMENT_EDIT" => "Изменить отзыв",
                    "ELEMENT_DELETE" => "Удалить отзыв",
                    "SITE_ID" => $arSites,
                ];

                $DB->StartTransaction();

                $iblockId = $firstIblock->Add($arFields);

                if (!$iblockId) {
                    $DB->Rollback();

                    $APPLICATION->ThrowException(GetMessage("WELPODRON_REVIEWS_INSTALL_ERROR_IBLOCK_INSTALL") . $firstIblock->LAST_ERROR);

                    return false;
                } else {
                    $DB->Commit();
                }

                // TODO: 2 - Группа всех пользователей можно получать динамически ?
                CIBlock::SetPermission($iblockId, ["2" => "R"]);

                $arProps = [
                    [
                        "NAME" => "Артикул элемента",
                        "CODE" => "artikul",
                        "IS_REQUIRED" => "Y",
                        "IBLOCK_ID" => $iblockId
                    ],
                    [
                        "NAME" => "Дата публикации отзыва",
                        "CODE" => "date",
                        "PROPERTY_TYPE" => "S",
                        "USER_TYPE" => "DateTime",
                        "IBLOCK_ID" => $iblockId
                    ],
                    // [
                    //     "NAME" => "Элемент",
                    //     "CODE" => "element",
                    //     "PROPERTY_TYPE" => "E",
                    //     "IS_REQUIRED" => "Y",
                    //     "IBLOCK_ID" => $iblockId
                    // ],
                    [
                        "NAME" => "Оценка",
                        "CODE" => "rating",
                        "PROPERTY_TYPE" => "N",
                        "IS_REQUIRED" => "Y",
                        "IBLOCK_ID" => $iblockId
                    ],
                    [
                        "NAME" => "Достоинства",
                        "CODE" => "advantages",
                        "USER_TYPE" => "",
                        "ROW_COUNT" => 3,
                        "IBLOCK_ID" => $iblockId
                    ],
                    [
                        "NAME" => "Недостатки",
                        "CODE" => "disadvantages",
                        "USER_TYPE" => "",
                        "ROW_COUNT" => 3,
                        "IBLOCK_ID" => $iblockId
                    ],
                    [
                        "NAME" => "Комментарий",
                        "CODE" => "comment",
                        "USER_TYPE" => "",
                        "ROW_COUNT" => 3,
                        "IS_REQUIRED" => "Y",
                        "IBLOCK_ID" => $iblockId
                    ],
                    [
                        "NAME" => "Автор",
                        "CODE" => "author",
                        "IS_REQUIRED" => "Y",
                        "IBLOCK_ID" => $iblockId
                    ],
                    [
                        "NAME" => "Изображения",
                        "CODE" => "images",
                        "PROPERTY_TYPE" => "F",
                        "MULTIPLE" => "Y",
                        "FILE_TYPE" => "jpg, png, jpeg",
                        "IBLOCK_ID" => $iblockId
                    ],
                    [
                        "NAME" => "Ответ",
                        "CODE" => "responce_text",
                        "USER_TYPE" => "",
                        "ROW_COUNT" => 3,
                        "IBLOCK_ID" => $iblockId
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
            } else {
                $iblockId = $firstFoundIblock['ID'];
            }

            $this->DEFAULT_OPTIONS['IBLOCK_ID'] = $iblockId;
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
            // На данный момент папка перемещается в local пространство
            if (!CopyDirFiles(__DIR__ . '/components/', Application::getDocumentRoot() . '/local/components', true, true)) {
                $APPLICATION->ThrowException(GetMessage("WELPODRON_REVIEWS_INSTALL_ERROR_FILES_COPY"));
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
        Directory::deleteDirectory(Application::getDocumentRoot() . '/local/components/welpodron/reviews.list');
        Directory::deleteDirectory(Application::getDocumentRoot() . '/local/components/welpodron/reviews.form');
        Directory::deleteDirectory(Application::getDocumentRoot() . '/local/components/welpodron/element.rating');
    }

    public function __construct()
    {
        $this->MODULE_ID = 'welpodron.reviews';
        $this->MODULE_NAME = GetMessage("WELPODRON_REVIEWS_MODULE_NAME");
        $this->MODULE_DESCRIPTION = GetMessage("WELPODRON_REVIEWS_MODULE_DESC");
        $this->PARTNER_NAME = GetMessage("WELPODRON_REVIEWS_PARTNER_NAME");
        $this->PARTNER_URI = GetMessage("WELPODRON_REVIEWS_PARTNER_URI");

        $arModuleVersion = [];

        include(__DIR__ . "/version.php");

        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];

        $this->DEFAULT_OPTIONS = [
            'MAX_FILES_AMOUNT' => 3,
            'MAX_FILE_SIZE' => 5,
            'BANNED_SYMBOLS' => '<,>,&,*,^,%,$,`,~,#',
            'USE_CAPTCHA' => 'N',
            'GOOGLE_CAPTCHA_SECRET_KEY' => '',
            'GOOGLE_CAPTCHA_PUBLIC_KEY' => ''
        ];
    }
}
