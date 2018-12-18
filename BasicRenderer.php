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

function appendBirthToMatingRowByDate(&$inProps, &$outProps, $rowIndex, $outRowIndex){
    
    $birthDate  = $inProps["sheet"]->getCellByColumnAndRow($inProps["dateIndex"], $rowIndex)->getValue();
    $matingDate = $outProps["sheet"]->getCellByColumnAndRow($outProps["matingDateIndex"], $outRowIndex)->getValue();
        
    $diffDays  = diffInDays($matingDate, $birthDate);
        
    if($diffDays > 110 and $diffDays < 125){
        for ($col = 1; $col <= $inProps["highestColIndex"]; ++$col) {
            if($col != $inProps["eartagIndex"]) {
                $value = $inProps["sheet"]->getCellByColumnAndRow($col, $rowIndex)->getValue();
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

function hashOfRowIndexByEartag( &$props ) {
    
    $sheet             = $props["sheet"];
    $eartagIndex       = $props["eartagIndex"];
    $highestRowIndex   = $sheet->getHighestRow();
    
    $hashOfRows        = $props["hashOfRows"];
    
    for($rowIndex = 0; $rowIndex < $highestRowIndex; $rowIndex++) {
        
        $eartag = $sheet->getCellByColumnAndRow($eartagIndex, $rowIndex)->getValue();

        if( array_key_exists($eartag, $hashOfRows) ){
            
            $rowIndexOrArray = $hashOfRows[ $eartag ];
            
            if( is_array($rowIndexOrArray) ) {
                array_push($rowIndexOrArray, $rowIndex);
            } 
            else {
                $hashOfRows[ $eartag ] = [$rowIndexOrArray, $rowIndex];
            }
        }
        else {
            $hashOfRows[ $eartag ] = $rowIndex;
        }
    }
}

function isDuplicateMating( &$inProps, $preDate, $curDate ) {
    $diffDays = diffInDays($preDate, $curDate);

    return $diffDays >= -5 && $diffDays <= 5; 
}


//确保在这个函数里对所有 inSheet Properties 进行检验或设置。
// 1 spreadsheet 
// 2 sheet 
// 3 highestRowIndex
// 4 highestColIndex
// 5 eartagIndex 
// 6 dateIndex 
// 7 hashOfRows
function loadInSheetAndSetupProps($sheetName, &$inProps, &$outProps, $firstEartagInsert = false, $returnDateColumn = true) {
    
    $inSheet  = $inProps["spreadsheet"]->getSheetByName( $sheetName );  // 1
    $outSheet = $outProps["sheet"];
    
    $inProps["highestRowIndex"] = $inSheet->getHighestRow();   //3, 4
    $inCurHighestColumn         = $inSheet->getHighestColumn(); 
    $inProps["highestColIndex"] = PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($inCurHighestColumn); 

    $dateColumnIndex        = -1; 
    $inProps["eartagIndex"] = -1;
    for ($col = 1; $col <= $inProps["highestColIndex"]; ++$col) {
        
        $value = $inSheet->getCellByColumnAndRow($col, 1)->getValue();

        if( $value == "耳号" ){
            $inProps["eartagIndex"]  = $col;                      // 5
            if( $firstEartagInsert ) {
                $outProps["eartagIndex"] = $col;
            }
        }

        if( $value == "备注"){
            $value = $sheetName."备注";
        }
        
        $outIndex = $col;
        if( ! $firstEartagInsert ) {
            $outIndex += $outProps["highestColIndex"];
            if( $inProps["eartagIndex"] > 0 && $col > $inProps["eartagIndex"] ) {
                --$outIndex;
            }
        }
        
        if( $value == "日期") {
            $value                = $sheetName."日期";
            $inProps["dateIndex"] = $col;                          //6
            $dateColumnIndex      = $outIndex;
        }

        $outSheet->setCellValueByColumnAndRow($outIndex, 1, $value);
    }
    
    if( ($inProps["eartagIndex"] < 0) or ($returnDateColumn and $dateColumnIndex < 0)) {
        $errorMessage = $sheetName."表中耳号或日期信息有误。";
        throw new Exception( $errorMessage );
    }
    
    $inProps["sheet"]      = $inSheet;                          //2
    $inProps["hashOfRows"] = array();                           //7
    
    return $dateColumnIndex;
}

function addMatingToOutSheet( &$inProps, &$outProps ) {
    
    $inHighestRowIndex = $inProps["highestRowIndex"];
    $inSheet           = $inProps["sheet"];
    $inEartagIndex     = $inProps["eartagIndex"];
    $inDateIndex       = $inProps["dateIndex"];
    $hashOfRows        = $inProps["hashOfRows"];
    $inHighestColIndex = $inProps["highestColIndex"];
        
    $outSheet          = $outProps["sheet"];
    
    $duplicateCount = 0;
    for ($row = 2; $row <= $inHighestRowIndex; ++$row) {
        $hashKey = $inSheet->getCellByColumnAndRow( $inEartagIndex, $row)->getValue();
        $curDate = $inSheet->getCellByColumnAndRow( $inDateIndex,   $row)->getValue();

        //5天内多次配种，只取一次做后续分析
        $duplicateMating = false;
        if( array_key_exists($hashKey, $hashOfRows) ) {
            $rowIndexOrArray  = $hashOfRows[$hashKey];
            if( is_array($rowIndexOrArray) ){
                $num = count( $rowIndexOrArray ); 
                for($idx = 0; $idx < $num; ++$idx){ 
                    $preDate  = $inSheet->getCellByColumnAndRow($inDateIndex, $rowIndexOrArray[$idx])->getValue();
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
                $preDate  = $inSheet->getCellByColumnAndRow($inDateIndex, $rowIndexOrArray)->getValue();

                if( isDuplicateMating($inProps, $preDate, $curDate) ) {
                    $duplicateMating = true;
                    $duplicateCount++;
                } 
                else {
                    $hashOfRows[$hashKey] = [$rowIndexOrArray, $row];
                }
            }
        }
        else {
            $hashOfRows[$hashKey] = $row;
        }
        
        if( $duplicateMating ) {
            continue;
        }
        
        for ($col = 1; $col <= $inHighestColIndex; ++$col) {
            $value = $inSheet->getCellByColumnAndRow($col, $row)->getValue();
            $outSheet->setCellValueByColumnAndRow($col, $row - $duplicateCount, $value);
        }
    }
}

function addBirthToOutSheet( &$inProps, &$outProps ) {
    
    $outSheet             = $outProps["sheet"];
    $outHighestRowIndex   = $outProps["highestRowIndex"];
    $outEartagIndex       = $outProps["eartagIndex"];
    
    $hashOfRows           = $inProps["hashOfRows"];
    
    for ($outRowIndex = 2; $outRowIndex <= $outHighestRowIndex; ++$outRowIndex) {

        $key = $outSheet->getCellByColumnAndRow($outEartagIndex, $outRowIndex)->getValue();

        if( ! array_key_exists($key, $hashOfRows) ){
            continue;  //配种未分娩
        }

        $rowIndexOrArray  = $hashOfRows[ $key ];

        if( is_array($rowIndexOrArray) ) {
            $num = count( $rowIndexOrArray ); 
            for($idx = 0; $idx < $num; ++$idx){ 
                $rowIndex = $rowIndexOrArray[ $idx ];
                appendBirthToMatingRowByDate($inProps, $outProps, $rowIndex, $outRowIndex);
            }
        }
        else{
            appendBirthToMatingRowByDate($inProps, $outProps, $rowIndexOrArray, $outRowIndex);
        }
    }
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
    
    $inSpreadsheet          = $reader->load("TemplateRecords.xlsx");
    $inProps["spreadsheet"] = $inSpreadsheet;
    
    $debugStartNoIO = microtime(true);

    $outProps["matingDateIndex"] = loadInSheetAndSetupProps("配种", $inProps, $outProps, true);
    addMatingToOutSheet($inProps, $outProps);
    $outProps["highestRowIndex"]   = $outProps["sheet"]->getHighestRow();
    $outProps["highestColIndex"]   = $inProps["highestColIndex"];
    
    
    $outProps["birthDateIndex"]    = loadInSheetAndSetupProps("分娩", $inProps, $outProps);
    hashOfRowIndexByEartag( $inProps );
    addBirthToOutSheet($inProps, $outProps);
    $outProps["highestColIndex"] = $outProps["highestColIndex"] + $inProps["highestColIndex"] - 1;

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
