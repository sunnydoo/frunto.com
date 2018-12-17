<?php

require 'vendor/autoload.php';


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$inProps  = array();
$outProps = array();

$outSpreadsheet = new Spreadsheet();
$outProps["sheet"]       = $outSpreadsheet->getActiveSheet();
$outProps["sheet"]->setTitle("TopFarmDataSource");

$reader = PhpOffice\PhpSpreadsheet\IOFactory::createReader("Xlsx");
$reader->setReadDataOnly(true);
$reader->setLoadSheetsOnly(["配种", "分娩"]);
$inSpreadsheet = $reader->load("TemplateRecords.xlsx");
$inProps["sheet"]    = $inSpreadsheet->getSheetByName("配种");

//处理“配种”表
$inProps["curHighestRowIndex"] = $inProps["sheet"]->getHighestRow(); 
$inCurHighestColumn            = $inProps["sheet"]->getHighestColumn(); 
$inProps["curHighestColIndex"] = PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($inCurHighestColumn); 

$outProps["highestRowIndex"]   = $inProps["curHighestRowIndex"];
$outProps["highestColIndex"]   = $inProps["curHighestColIndex"];
$outProps["eartagIndex"]       = -1;
$outProps["dateIndex"]         = -1;

for ($col = 1; $col <= $inProps["curHighestColIndex"]; ++$col) {
    $value = $inProps["sheet"]->getCellByColumnAndRow($col, 1)->getValue();
    if( $value == "日期") {
        $value = "配种日期";
        $outProps["dateIndex"] = $col;
    }
    
    if( $value == "耳号"){
        $outProps["eartagIndex"] = $col;
    }
        
    $outProps["sheet"]->setCellValueByColumnAndRow($col, 1, $value);
}

if( $outProps["eartagIndex"] < 0 or $outProps["dateIndex"] < 0) {
    throw new Exception("配种表中耳号或日期信息有误。");
}

for ($row = 2; $row <= $inProps["curHighestRowIndex"]; ++$row) {
    for ($col = 1; $col <= $inProps["curHighestColIndex"]; ++$col) {
        $value = $inProps["sheet"]->getCellByColumnAndRow($col, $row)->getValue();
        
        $outProps["sheet"]->setCellValueByColumnAndRow($col, $row, $value);
    }
}

//如果5天内重复配种，取第一次记录
$arrayOfoutSheet = $outProps["sheet"]->toArray();


//处理“分娩”表
$inProps["sheet"]              = $inSpreadsheet->getSheetByName("分娩");
$inProps["curHighestRowIndex"] = $inProps["sheet"]->getHighestRow(); 
$inCurHighestColumn            = $inProps["sheet"]->getHighestColumn(); 
$inProps["curHighestColIndex"] = PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($inCurHighestColumn); 
$inProps["eartagIndex"]        = -1;
$inProps["dateIndex"]          = -1;

for ($col = 1; $col <= $inProps["curHighestColIndex"]; ++$col) {
    $value = $inProps["sheet"]->getCellByColumnAndRow($col, 1)->getValue();
    if( $value == "日期") {
        $value = "分娩日期";
        $inProps["dateIndex"] = $col;
    }
    
    if( $value == "耳号"){
        $inProps["eartagIndex"] = $col;
        continue;
    }
    
    $outIndex = $col + $outProps["highestColIndex"];
    if( $col > $inProps["eartagIndex"] && $inProps["eartagIndex"] > 0 ) {
        --$outIndex;
    }
    $outProps["sheet"]->setCellValueByColumnAndRow($outIndex, 1, $value);
}

if( $inProps["eartagIndex"] < 0 or $inProps["dateIndex"] < 0) {
    throw new Exception("分娩表中耳号或日期信息有误。");
}

function appendBirthToMatingRowByDate($inProps, $outProps, $inRow, $outRowIndex){
    
    $birthDate  = $inProps["sheet"]->getCellByColumnAndRow($inProps["dateIndex"], $inRow->getRowIndex())->getValue();
    $matingDate = $outProps["sheet"]->getCellByColumnAndRow($outProps["dateIndex"], $outRowIndex)->getValue();
        
    $birthDate  = date_create_from_format("y/m/d", $birthDate);
    $matingDate = date_create_from_format("y/m/d", $matingDate);

    $diff       = date_diff($matingDate, $birthDate);
    $diff_days  = $diff->format("%r%a");
        
    if($diff_days > 110 and $diff_days < 125){
        for ($col = 1; $col <= $inProps["curHighestColIndex"]; ++$col) {
            if($col != $inProps["eartagIndex"]) {
                $value = $inProps["sheet"]->getCellByColumnAndRow($col, $inRow->getRowIndex())->getValue();
                $outIndexToInsert = $outProps["highestColIndex"] + $col;
                //“分娩”表中耳号没有插入
                if($col > $inProps["eartagIndex"]) {
                    $outIndexToInsert--;
                }
                $outProps["sheet"]->setCellValueByColumnAndRow($outIndexToInsert, $outRowIndex, $value);
            }
            
        }
    }
}

$inProps["hashOfRows"] = array();

function pushSheetRowToHash( $inProps ) {
    //PHP的一个空数组，大约需要 82 个字节，不要产生小的空数组
    foreach ($inProps["sheet"]->getRowIterator() as $row) {

        $key = $inProps["sheet"]->getCellByColumnAndRow($inProps["eartagIndex"], $row->getRowIndex())->getValue();

        if( array_key_exists($key, $inProps["hashOfRows"]) ){
            $rowORArray = $inProps["hashOfRows"][ $key ];
            if( is_array($rowORArray) ) {
                array_push($rowORArray, $row);
            } 
            else {
                $inProps["hashOfRows"][ $key ] = array( $rowORArray, $row );
            }
        }
        else {
            $inProps["hashOfRows"][ $key ] = $row;
        }
    }
}

for ($outRowIndex = 2; $outRowIndex <= $outProps["highestRowIndex"]; ++$outRowIndex) {
    
    $key = $outProps["sheet"]->getCellByColumnAndRow($outProps["eartagIndex"], $outRowIndex)->getValue();
    
    if( ! array_key_exists($key, $inProps["hashOfRows"]) ){
        continue;  //配种未分娩
    }
    
    $rowOrArray  = $inProps["hashOfRows"][ $key ];
            
    if( is_array($rowOrArray) ) {
        $num = count( $rowOrArray ); 
        for($idx = 0; $idx < $num; ++$idx){ 
            $inRow = $rowOrArray[ $idx ];
            appendBirthToMatingRowByDate($inProps, $outProps, $inRow, $outRowIndex);
        }
    }
    else{
        appendBirthToMatingRowByDate($inProps, $outProps, $rowOrArray, $outRowIndex);
    }
}

$writer = PhpOffice\PhpSpreadsheet\IOFactory::createWriter($outSpreadsheet, "Xlsx");

$outFilePath = "Processed.xlsx";
$writer->save( $outFilePath );
echo "\nComplete TopFarmDataSource File: ", $outFilePath, "\n";
