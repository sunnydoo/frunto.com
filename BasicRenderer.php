<?php

require 'vendor/autoload.php';


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function diffInDays($date1, $date2){
    $date1 = date_create_from_format("y/m/d", $date1);
    $date2 = date_create_from_format("y/m/d", $date2);

    $diff  = date_diff($date1, $date2);
    
    return $diff->format("%r%a");
}

function appendBirthToMatingRowByDate(&$inProps, &$outProps, $inRow, $outRowIndex){
    
    $birthDate  = $inProps["sheet"]->getCellByColumnAndRow($inProps["dateIndex"], $inRow->getRowIndex())->getValue();
    $matingDate = $outProps["sheet"]->getCellByColumnAndRow($outProps["matingDateIndex"], $outRowIndex)->getValue();
        
    $diffDays  = diffInDays($matingDate, $birthDate);
        
    if($diffDays > 110 and $diffDays < 125){
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

function pushSheetRowToHash( &$inProps ) {
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

function isDuplicateMating( &$inProps, $preDate, $curDate ) {
    $diffDays = diffInDays($preDate, $curDate);

    return $diffDays >= -5 && $diffDays <= 5; 
}

function topfarmMain() {
    
    $debugStartTime = microtime(true);

    $inProps  = array();
    $outProps = array();

    $outSpreadsheet = new Spreadsheet();
    $outProps["sheet"]       = $outSpreadsheet->getActiveSheet();
    $outProps["sheet"]->setTitle("TopFarmDataSource");

    $reader = PhpOffice\PhpSpreadsheet\IOFactory::createReader("Xlsx");
    $reader->setReadDataOnly(true);
    $reader->setLoadSheetsOnly(["配种", "分娩", "断奶"]);
    $inSpreadsheet = $reader->load("TemplateRecords.xlsx");
    $inProps["sheet"]    = $inSpreadsheet->getSheetByName("配种");
    
    $debugStartNoIO = microtime(true);

    //处理“配种”表
    $inProps["hashOfRows"]         = array();
    $inProps["curHighestRowIndex"] = $inProps["sheet"]->getHighestRow(); 
    $inCurHighestColumn            = $inProps["sheet"]->getHighestColumn(); 
    $inProps["curHighestColIndex"] = PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($inCurHighestColumn); 

    $outProps["eartagIndex"]       = -1;
    $outProps["matingDateIndex"]         = -1;

    for ($col = 1; $col <= $inProps["curHighestColIndex"]; ++$col) {
        $value = $inProps["sheet"]->getCellByColumnAndRow($col, 1)->getValue();
        if( $value == "日期") {
            $value = "配种日期";
            $outProps["matingDateIndex"] = $col;
        }

        if( $value == "耳号"){
            $outProps["eartagIndex"] = $col;
        }

        if( $value == "备注"){
            $value = "配种备注";
        }

        $outProps["sheet"]->setCellValueByColumnAndRow($col, 1, $value);
    }

    if( $outProps["eartagIndex"] < 0 or $outProps["matingDateIndex"] < 0) {
        throw new Exception("配种表中耳号或日期信息有误。");
    }

    $duplicateCount = 0;
    for ($row = 2; $row <= $inProps["curHighestRowIndex"]; ++$row) {
        $hashKey = $inProps["sheet"]->getCellByColumnAndRow($outProps["eartagIndex"], $row)->getValue();
        $curDate    = $inProps["sheet"]->getCellByColumnAndRow($outProps["matingDateIndex"],   $row)->getValue();

        //5天内多次配种，只取一次做后续分析
        $duplicateMating = false;
        if( array_key_exists($hashKey, $inProps["hashOfRows"]) ) {
            $rowIndexOrArray  = $inProps["hashOfRows"][$hashKey];
            if( is_array($rowIndexOrArray) ){
                $num = count( $rowIndexOrArray ); 
                for($idx = 0; $idx < $num; ++$idx){ 
                    $preDate  = $inProps["sheet"]->getCellByColumnAndRow($outProps["matingDateIndex"], $rowIndexOrArray[$idx])->getValue();
                    if( isDuplicateMating($inProps, $preDate, $curDate) ) {
                        $duplicateMating = true;
                        $duplicateCount++;
                        break;
                    }
                }
                if( ! $duplicateMating ){
                    array_push($rowIndexOrArray, $row );
                }
            }
            else {
                $preDate  = $inProps["sheet"]->getCellByColumnAndRow($outProps["matingDateIndex"], $rowIndexOrArray)->getValue();

                if( isDuplicateMating($inProps, $preDate, $curDate) ) {
                    $duplicateMating = true;
                    $duplicateCount++;
                } 
                else {
                    $inProps["hashOfRows"][$hashKey] = [$rowIndexOrArray, $row];
                }
            }
        }
        else {
            $inProps["hashOfRows"][$hashKey] = $row;
        }
        
        if( $duplicateMating ) {
            continue;
        }
        
        for ($col = 1; $col <= $inProps["curHighestColIndex"]; ++$col) {
            $value = $inProps["sheet"]->getCellByColumnAndRow($col, $row)->getValue();
            $outProps["sheet"]->setCellValueByColumnAndRow($col, $row - $duplicateCount, $value);
        }
    }

    $outProps["highestRowIndex"]   = $outProps["sheet"]->getHighestRow();
    $outProps["highestColIndex"]   = $inProps["curHighestColIndex"];
    
    $inProps["hashOfRows"]         = array();

    //处理“分娩”表
    $inProps["sheet"]              = $inSpreadsheet->getSheetByName("分娩");
    $inProps["curHighestRowIndex"] = $inProps["sheet"]->getHighestRow(); 
    $inCurHighestColumn            = $inProps["sheet"]->getHighestColumn(); 
    $inProps["curHighestColIndex"] = PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($inCurHighestColumn); 
    $inProps["eartagIndex"]        = -1;
    $inProps["dateIndex"]          = -1;

    for ($col = 1; $col <= $inProps["curHighestColIndex"]; ++$col) {
        $value = $inProps["sheet"]->getCellByColumnAndRow($col, 1)->getValue();

        if( $value == "耳号"){
            $inProps["eartagIndex"] = $col;
            continue;
        }

        if( $value == "备注"){
            $value = "分娩备注";
        }

        $outIndex = $col + $outProps["highestColIndex"];
        if( $col > $inProps["eartagIndex"] && $inProps["eartagIndex"] > 0 ) {
            --$outIndex;
        }
        
        if( $value == "日期") {
            $value = "分娩日期";
            $inProps["dateIndex"] = $col;
            $inProps["birthDateIndex"] = $outIndex;
        }
        
        $outProps["sheet"]->setCellValueByColumnAndRow($outIndex, 1, $value);
    }
    
    if( $inProps["eartagIndex"] < 0 or $inProps["dateIndex"] < 0) {
        throw new Exception("分娩表中耳号或日期信息有误。");
    }
    
    pushSheetRowToHash( $inProps );
    
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
    
    $outProps["highestColIndex"] = $outProps["highestColIndex"] + $inProps["curHighestColIndex"] - 1;

    $debugEndNoIO = microtime(true);

    
    $writer = PhpOffice\PhpSpreadsheet\IOFactory::createWriter($outSpreadsheet, "Xlsx");

    $outFilePath = "Processed.xlsx";
    $writer->save( $outFilePath );
    echo "\nComplete TopFarmDataSource File: ", $outFilePath;
    
    $debugEndTime = microtime(true);
    
    echo "\n文件共 ", $outProps["highestRowIndex"], " 行, ", $outProps["highestColIndex"], "列";
    
    echo "\n执行时间：", round($debugEndTime - $debugStartTime, 3), "秒";
    echo "\n不计算IO的执行时间：", round($debugEndNoIO - $debugStartNoIO, 3), "秒\n";
    

    
}

// === Start of Main Execution === //
topfarmMain();
