<?

use Bitrix\Main\ModuleManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Application;

class welpodron_reviews extends \CModule
{
    const IBLOCK_TYPE = "welpodron_reviews";

    public function DoInstall()
    {
        global $APPLICATION;

        ModuleManager::registerModule($this->MODULE_ID);

        $this->InstallDb();
        $this->InstallFiles();

        $APPLICATION->IncludeAdminFile('Установка модуля ' . $this->MODULE_ID, __DIR__ . '/step.php');
    }

    public function DoUninstall()
    {
        global $APPLICATION;

        $context = Application::getInstance()->getContext();
        $request = $context->getRequest();

        if ($request->get("step") < 2) {
            $APPLICATION->IncludeAdminFile('Деинсталляция модуля ' . $this->MODULE_ID, __DIR__ . '/unstep1.php');
        } elseif ($request->get("step") == 2) {
            $this->UnInstallFiles();
            // По умолчанию БД не удаляется 

            if ($request->get("savedata") != "Y")
                $this->UnInstallDB();

            ModuleManager::unRegisterModule($this->MODULE_ID);
            $APPLICATION->IncludeAdminFile('Деинсталляция модуля ' . $this->MODULE_ID, __DIR__ . '/unstep2.php');
        }
    }

    public function InstallDb()
    {
        Loader::includeModule("iblock");

        // Попытаться найти тип 
        $iblockType = \CIBlockType::GetList([], ['=ID' => self::IBLOCK_TYPE])->Fetch();

        if (!$iblockType) {
            $iblockType = new \CIBlockType;

            $arFields = [
                'ID' => self::IBLOCK_TYPE,
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

            $iblockType = $iblockType->Add($arFields);
        }

        // Попытаться найти хотя бы один инфоблок
        $firstFoundIblock = \CIBlock::GetList([], ['TYPE' => self::IBLOCK_TYPE])->Fetch();

        if (!$firstFoundIblock) {
            $firstIblock = new \CIBlock;

            $dbSites = \CSite::GetList();
            while ($arSite = $dbSites->Fetch()) {
                $arSites[] = $arSite['ID'];
            }

            $arFields = [
                "NAME" => 'Welpodron Отзывы',
                "IBLOCK_TYPE_ID" => self::IBLOCK_TYPE,
                "ELEMENTS_NAME" => "Отзывы",
                "ELEMENT_NAME" => "Отзыв",
                "ELEMENT_ADD" => "Добавить отзыв",
                "ELEMENT_EDIT" => "Изменить отзыв",
                "ELEMENT_DELETE" => "Удалить отзыв",
                "SITE_ID" => $arSites,
            ];

            $iblockId = $firstIblock->Add($arFields);
            // TODO: 2 - Группа всех пользователей можно получать динамически ?
            CIBlock::SetPermission($iblockId, ["2" => "R"]);

            $arProps = [
                [
                    "NAME" => "Элемент",
                    "CODE" => "element",
                    "PROPERTY_TYPE" => "E",
                    "IS_REQUIRED" => "Y",
                    "IBLOCK_ID" => $iblockId
                ],
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
                    "IBLOCK_ID" => $iblockId
                ],
                [
                    "NAME" => "Недостатки",
                    "CODE" => "disadvantages",
                    "IBLOCK_ID" => $iblockId
                ],
                [
                    "NAME" => "Комментарий",
                    "CODE" => "comment",
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
                ]
            ];

            foreach ($arProps as $prop) {
                $iblockProp = new \CIBlockProperty;
                $iblockProp->Add($prop);
            }
        }
    }

    public function UnInstallDB()
    {
        Loader::includeModule("iblock");
        // Удалить iblock_type
        CIBlockType::Delete(self::IBLOCK_TYPE);
    }

    public function InstallFiles()
    {
        // На данный момент папка перемещается в local пространство
        CopyDirFiles(__DIR__ . '/components/', Application::getDocumentRoot() . '/local/components', true, true);
    }

    public function UnInstallFiles()
    {
        DeleteDirFilesEx(Application::getDocumentRoot() . '/local/components/welpodron/reviews.list');
        DeleteDirFilesEx(Application::getDocumentRoot() . '/local/components/welpodron/reviews.form');
        DeleteDirFilesEx(Application::getDocumentRoot() . '/local/components/welpodron/element.rating');
    }

    public function __construct()
    {
        $this->MODULE_ID = 'welpodron.reviews';
        $this->MODULE_NAME = 'Модуль ' . $this->MODULE_ID;
        $this->MODULE_DESCRIPTION = 'Модуль ' . $this->MODULE_ID;
        $this->PARTNER_NAME = 'welpodron';
        $this->PARTNER_URI = 'https://github.com/Welpodron';

        $arModuleVersion = [];

        $path = str_replace('\\', '/', __FILE__);
        $path = substr($path, 0, strlen($path) - strlen('/index.php'));
        include $path . '/version.php';

        if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion)) {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }
    }
}
