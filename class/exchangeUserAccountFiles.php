<?php

class exchangeUserAccountFiles
{
    public $path;
    const IBLOCK_ID_DOCUMENT = 9;

    public function __construct($path)
    {
        $this->path = $path;
    }

    public function getFilesPath()
    {
        $path = $this->path;
        $result = [];
        $files = scandir($path);
        foreach ($files as $filesItem) {
            if (preg_match('/\.(pdf)/', $filesItem)) {
                $result[] = $filesItem;
            }
        }
        return $result;
    }

    public function sort($filesArr)
    {
        $path = $this->path;
        $result = [];

        foreach ($filesArr as $filesArrItem) {
            $filesArrItemExplode = explode('_', $filesArrItem);

            $date = $filesArrItemExplode[1];
            $code = $filesArrItemExplode[2];
            $result[$code][$date][] = $filesArrItem;
        }

        return $result;
    }

    public function add($array, $key)
    {
        if (!\Bitrix\Main\Loader::includeModule('iblock')) {
            return [];
        }
        $blockElement = new CIBlockElement;

        $path = $this->path;

        $result = [];
        $params = array(
            "max_len" => "128", // обрезает символьный код до 128 символов
            "change_case" => "L", // буквы преобразуются к нижнему регистру
            "replace_space" => "_", // меняем пробелы на нижнее подчеркивание
            "replace_other" => "_", // меняем левые символы на нижнее подчеркивание
            "delete_repeat_replace" => "true", // удаляем повторяющиеся нижние подчеркивания
            "use_google" => "false", // отключаем использование google
        );

        $accountNumber = $key;

        foreach ($array as $date => $arrayItem) {
            $dateSplit = chunk_split($date, 4, '.');
            $dateSplitExplode = explode('.', $dateSplit);
            $year = $dateSplitExplode[0];
            $month = $dateSplitExplode[1];
            $dateFormatted = $this->getRusMonthName($month) . ' ' . $year . 'год';
            $name = 'Лицевой счет №' . $accountNumber . ': Документы за ' . $month . ' месяц ' . $year . ' год.';
            $code = CUtil::translit($name, "ru", $params);

            $filesArray = [];
            foreach ($arrayItem as $filesItem) {
                $filesArray[] = CFile::MakeFileArray($path . '/' . $filesItem);
            }

            $ID = $this->GetByCode($code);

            if (empty($ID)) {
                $arFields = [
                    "MODIFIED_BY" => "1",
                    'IBLOCK_ID' => self::IBLOCK_ID_DOCUMENT,
                    "NAME" => $name,
                    "CODE" => $code
                ];
                $ID = $blockElement->Add($arFields);
                if (!$ID) {
                    $result["errors"]["add"][] = $blockElement->LAST_ERROR;
                }
            }

            $userID = $this->GetUserIDByAccountNumber($accountNumber);
            if (!empty($userID)) {
                CIBlockElement::SetPropertyValuesEx($ID, self::IBLOCK_ID_DOCUMENT, ['USER_RECEIPTS' => $userID]);
            }
            CIBlockElement::SetPropertyValuesEx($ID, self::IBLOCK_ID_DOCUMENT, ['PERSONAL_ACCOUNT' => $accountNumber]);
            CIBlockElement::SetPropertyValuesEx($ID, self::IBLOCK_ID_DOCUMENT, ['FILE_RECEIPTS' => $filesArray]);
            CIBlockElement::SetPropertyValuesEx($ID, self::IBLOCK_ID_DOCUMENT, ['DATE_RECEIPTS' => $dateFormatted]);
        }
        return $result;
    }

    public function GetByCode($code)
    {
        if (!\Bitrix\Main\Loader::includeModule('iblock')) {
            return [];
        }
        $itemGetList = CIBlockElement::GetList([], ['IBLOCK_ID' => self::IBLOCK_ID_DOCUMENT, "CODE" => $code], false, false, ["ID"])->GetNext();
        return $itemGetList["ID"];
    }

    public function GetUserIDByAccountNumber($accountNumber)
    {
        $order = ['sort' => 'asc'];
        $tmp = 'sort';
        $filter = ["UF_CODE" => $accountNumber];
        $itemsGetList = CUser::GetList($order, $tmp, $filter)->GetNext();
        return $itemsGetList["ID"];
    }

    public function getRusMonthName($n)
    {
        $rusMonthNames = [
            '01' => 'Январь',
            '02' => 'Февраль',
            '03' => 'Март',
            '04' => 'Апрель',
            '05' => 'Май',
            '06' => 'Июнь',
            '07' => 'Июль',
            '08' => 'Август',
            '09' => 'Сентябрь',
            '10' => 'Октябрь',
            '11' => 'Ноябрь',
            '12' => 'Декабрь',
        ];

        return $rusMonthNames[$n];
    }
}