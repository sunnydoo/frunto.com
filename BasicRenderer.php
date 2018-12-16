<?php

require 'vendor/autoload.php';


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$outSpreadsheet = new Spreadsheet();
$outSheet       = $outSpreadsheet->getActiveSheet();
$outSheet->setTitle("TopFarmDataSource");

$reader = PhpOffice\PhpSpreadsheet\IOFactory::createReader("Xlsx");
$reader->setReadDataOnly(true);
$reader->setLoadSheetsOnly(["配种", "分娩"]);
$inSpreadsheet = $reader->load("TemplateRecords.xlsx");
$inCurSheet    = $inSpreadsheet->getSheetByName("配种");

//处理“配种”表
$inCurHighestRow         = $inCurSheet->getHighestRow(); 
$inCurHighestColumn      = $inCurSheet->getHighestColumn(); 
$inCurHighestColumnIndex = PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($inCurHighestColumn); 

$outHighestRow      = $inCurHighestRow;
$outHighestColIndex = $inCurHighestColumnIndex;
$outEartagIndex     = -1;
$outDateIndex       = -1;

for ($col = 1; $col <= $inCurHighestColumnIndex; ++$col) {
    $value = $inCurSheet->getCellByColumnAndRow($col, 1)->getValue();
    if( $value == "日期") {
        $value = "配种日期";
        $outDateIndex = $col;
    }
    
    if( $value == "耳号"){
        $outEartagIndex = $col;
    }
        
    $outSheet->setCellValueByColumnAndRow($col, 1, $value);
}

if( $outEartagIndex < 0 or $outDateIndex < 0) {
    throw new Exception("配种表中耳号或日期信息有误。");
}

for ($row = 2; $row <= $inCurHighestRow; ++$row) {
    for ($col = 1; $col <= $inCurHighestColumnIndex; ++$col) {
        $value = $inCurSheet->getCellByColumnAndRow($col, $row)->getValue();
        
        $outSheet->setCellValueByColumnAndRow($col, $row, $value);
    }
}

//如果5天内重复配种，取第一次记录
// Add Logic Here.


//处理“分娩”表
$inCurSheet              = $inSpreadsheet->getSheetByName("分娩");
$inCurHighestRow         = $inCurSheet->getHighestRow(); 
$inCurHighestColumn      = $inCurSheet->getHighestColumn(); 
$inCurHighestColumnIndex = PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($inCurHighestColumn); 

$inEartagIndex  = -1;
$inDateIndex    = -1;
for ($col = 1; $col <= $inCurHighestColumnIndex; ++$col) {
    $value = $inCurSheet->getCellByColumnAndRow($col, 1)->getValue();
    if( $value == "日期") {
        $value = "分娩日期";
        $inDateIndex = $col;
    }
    
    if( $value == "耳号"){
        $inEartagIndex = $col;
        continue;
    }
    
    $outIndex = $col + $outHighestColIndex;
    if( $col > $inEartagIndex && $inEartagIndex > 0 ) {
        --$outIndex;
    }
    $outSheet->setCellValueByColumnAndRow($outIndex, 1, $value);
}

if( $inEartagIndex < 0 or $inDateIndex < 0) {
    throw new Exception("分娩表中耳号或日期信息有误。");
}

function pushSheetRowToHash($inCurSheet, $keyColumnIndex, $hashFromInSheet) {
//如何传递数组指针？    
}


function appendBirthToMatingRowByDate($inCurSheet, $outSheet, $inRow, $outRowIndex, $inDateIndex, $outDateIndex, $inCurHighestColumnIndex, $inEartagIndex, $outHighestColIndex){
        
    $birthDate  = $inCurSheet->getCellByColumnAndRow($inDateIndex, $inRow->getRowIndex())->getValue();
    $matingDate = $outSheet->getCellByColumnAndRow($outDateIndex, $outRowIndex)->getValue();
        
    $birthDate  = date_create_from_format("y/m/d", $birthDate);
    $matingDate = date_create_from_format("y/m/d", $matingDate);

    $diff       = date_diff($matingDate, $birthDate);
    $diff_days  = $diff->format("%r%a");
        
    if($diff_days > 110 and $diff_days < 125){
        for ($col = 1; $col <= $inCurHighestColumnIndex; ++$col) {
            if($col != $inEartagIndex) {
                $value = $inCurSheet->getCellByColumnAndRow($col, $inRow->getRowIndex())->getValue();
                $outIndexToInsert = $outHighestColIndex + $col;
                //“分娩”表中耳号没有插入
                if($col > $inEartagIndex) {
                    $outIndexToInsert--;
                }
                $outSheet->setCellValueByColumnAndRow($outIndexToInsert, $outRowIndex, $value);
            }
            
        }
    }
}

$hashFromInSheet = array();
//pushSheetRowToHash($inCurSheet, $inEartagIndex, $hashFromInSheet);

//PHP的一个空数组，大约需要 82 个字节，不要产生小的空数组
foreach ($inCurSheet->getRowIterator() as $row) {

    $key = $inCurSheet->getCellByColumnAndRow($inEartagIndex, $row->getRowIndex())->getValue();

    if( array_key_exists($key, $hashFromInSheet) ){
        $rowORArray = $hashFromInSheet[ $key ];
        if( is_array($rowORArray) ) {
            array_push($rowORArray, $row);
        } 
        else {
            $hashFromInSheet[ $key ] = array( $rowORArray, $row );
        }
    }
    else {
        $hashFromInSheet[ $key ] = $row;
    }
}

for ($outRowIndex = 2; $outRowIndex <= $outHighestRow; ++$outRowIndex) {
    $key         = $outSheet->getCellByColumnAndRow($outEartagIndex, $outRowIndex)->getValue();
    $rowOrArray  = $hashFromInSheet[ $key ];
        
    if( ! $rowOrArray ) {
        echo "Warning: 当前耳号不存在--", $key,"--";
        continue;
    }
    
    if( is_array($rowOrArray) ) {
        $num = count( $rowOrArray ); 
        for($idx = 0; $idx < $num; ++$idx){ 
            $inRow = $rowOrArray[ $idx ];
            appendBirthToMatingRowByDate($inCurSheet, $outSheet, $inRow, $outRowIndex, $inDateIndex, $outDateIndex, $inCurHighestColumnIndex, $inEartagIndex, $outHighestColIndex);
        }
    }
    else{
        appendBirthToMatingRowByDate($inCurSheet, $outSheet, $rowOrArray, $outRowIndex, $inDateIndex, $outDateIndex, $inCurHighestColumnIndex, $inEartagIndex, $outHighestColIndex);
    }
}



$writer = PhpOffice\PhpSpreadsheet\IOFactory::createWriter($outSpreadsheet, "Xlsx");

$writer->save("Processed.xlsx");
