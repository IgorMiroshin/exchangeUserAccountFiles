<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
require_once $_SERVER["DOCUMENT_ROOT"] . "/bitrix/templates/teploset/cron/uploadFiles/class/exchangeUserAccountFiles.php";

$path = $_SERVER["DOCUMENT_ROOT"] . '/form_jur/downloads';

$exchangeUserAccountFilesClass = new exchangeUserAccountFiles($path);
$filesArr = $exchangeUserAccountFilesClass->getFilesPath();
if (!empty($filesArr)) {
    $filesArr = $exchangeUserAccountFilesClass->sort($filesArr);

    foreach ($filesArr as $key => $filesArrItem) {
        $ID = $exchangeUserAccountFilesClass->add($filesArrItem, $key);
    }
} else {
    echo "Счетов нет";
}