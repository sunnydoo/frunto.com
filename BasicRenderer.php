<?php

require 'vendor/autoload.php';


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function diffInDays($date1, $date2){  
    
    $formatDate = function( $rawDate ) {
        $excelInteger = function( $intDate ) {
            $date = FALSE;

            if( gettype( $intDate ) == 'integer' or gettype( $intDate ) == 'double' ) {
                $date = date_create_from_format("Y-m-d", "1900-1-1");
                $intDate -= 2; //不知原因，PHP比Excel计算的日期多2天。
                $date->add(new DateInterval('P'.$intDate.'D'));
            }

            return $date;
        };

        $date = ($date = date_create_from_format("y/m/d", $rawDate)) ? $date :
                ($date = date_create_from_format("Y/m/d", $rawDate)) ? $date :
                ($date = date_create_from_format("y-m-d", $rawDate)) ? $date :
                ($date = date_create_from_format("Y-m-d", $rawDate)) ? $date :
                ($date = $excelInteger($rawDate));


        return $date;
    };    
    
    //use Lambda function as inline, to save the function call cost.
    $diff  = date_diff( $formatDate( $date1 ), $formatDate( $date2 ));
    
    return $diff->format("%r%a");
    
}

function appendBirthToMatingRowByDate(&$inProps, &$outProps, $rowIndex, $outRowIndex){
    
    $birthDate  = $inProps["sheet"]->getCellByColumnAndRow($inProps["dateIndex"], $rowIndex)->getValue();
    $matingDate = $outProps["sheet"]->getCellByColumnAndRow($outProps["matingDateIndex"], $outRowIndex)->getValue();
        
    $diffDays  = diffInDays($matingDate, $birthDate);
    
    $findMatch = false;
    if($diffDays > 110 and $diffDays < 125){
        $findMatch = true;
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
    
    return $findMatch;
}

function appendWeaningToMatingRowByDate(&$inProps, &$outProps, $rowIndex, $outRowIndex){
    
    $weaningDate  = $inProps["sheet"]->getCellByColumnAndRow($inProps["dateIndex"], $rowIndex)->getValue();
    $birthDate = $outProps["sheet"]->getCellByColumnAndRow($outProps["birthDateIndex"], $outRowIndex)->getValue();
        
    $diffDays  = diffInDays($birthDate, $weaningDate);
    
    $findMatch = false;
    if($diffDays > 0 and $diffDays < 60 ){
        $findMatch = true;
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
    
    return $findMatch;
}

function hashOfRowIndexByEartag( &$props ) {
    
    $sheet             = $props["sheet"];
    $eartagIndex       = $props["eartagIndex"];
    $highestRowIndex   = $sheet->getHighestRow();
    
    $count = 1;
    for($rowIndex = 2; $rowIndex <= $highestRowIndex; $rowIndex++) {
        
        $eartag = $sheet->getCellByColumnAndRow($eartagIndex, $rowIndex)->getValue();

        if( is_eartag_exists($eartag, $props["hashOfRows"]) ){            
            if( is_array( $props["hashOfRows"][ $eartag ] ) ) {
                array_push($props["hashOfRows"][ $eartag ], $rowIndex);
            } 
            else {
                $props["hashOfRows"][ $eartag ] = [$props["hashOfRows"][ $eartag ], $rowIndex];
            }
        }
        else {
            $props["hashOfRows"][ $eartag ] = $rowIndex;
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
    
    if( ! array_key_exists( "highestRowIndex", $outProps)) {
        $outProps["highestRowIndex"] = $inProps["highestRowIndex"];
    }
    
    $inCurHighestColumn         = $inSheet->getHighestColumn(); 
    $inProps["highestColIndex"] = PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($inCurHighestColumn); 

    $dateColumnIndex        = -1; 
    $inProps["eartagIndex"] = -1;
    
    if( $firstEartagInsert ) {
        $outProps["baseTableHeader"] = array();
    }
    
    for ($col = 1; $col <= $inProps["highestColIndex"]; ++$col) {
        
        $value = $inSheet->getCellByColumnAndRow($col, 1)->getValue();
        
        if( $firstEartagInsert ) {
            array_push($outProps["baseTableHeader"], $value);
        }

        if( $value == "耳号" ){
            $inProps["eartagIndex"]  = $col;                      // 5
            if( $firstEartagInsert ) {
                $outProps["eartagIndex"] = $col;
                
//Robin: is_eartag_exists() 的临时方案，因为直接设置格式不工作
//                $colString = PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex( $col );
//                $coordinates = $colString."2:".$colString.$outProps["highestRowIndex"]; //like "M2:M128"
//            
//                $outSheet->getStyle( $coordinates )
//                     ->getNumberFormat()
//                     ->setFormatCode( PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
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
            
            $colString = PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex( $dateColumnIndex );
            $coordinates = $colString."2:".$colString.$outProps["highestRowIndex"]; //like "M1:M128"
            
            $outSheet->getStyle( $coordinates )
                     ->getNumberFormat()
                     ->setFormatCode( PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_YYYYMMDD );
        }

        $outSheet->setCellValueByColumnAndRow($outIndex, 1, $value);
    }
    
    if( ($inProps["eartagIndex"] < 0) or ($returnDateColumn and $dateColumnIndex < 0)) {
        $errorMessage = $sheetName."表中耳号或日期信息有误。";
        throw new Exception( $errorMessage );
    }
 
    $inProps["sheet"]          = $inSheet;                          //2
    $inProps["hashOfRows"]     = array();                           //7 
    
    return $dateColumnIndex;
}

function loadNextMatingAndSetupProps(&$inProps, &$outProps ) {

    $num = count($outProps["baseTableHeader"]);
    
    $inProps["eartagIndex"] = -1;
    for ($col = 1; $col <= $num; ++$col) {
        
        $value = $outProps["baseTableHeader"][$col - 1];
        
        if( $value == "耳号" ){
            $inProps["eartagIndex"]  = $col;    
            continue;
        }

        if( $value == "备注"){
            $value ="再配备注";
        }
        
        $outIndex = $col;
        $outIndex += $outProps["highestColIndex"];
        if( $inProps["eartagIndex"] > 0 && $col > $inProps["eartagIndex"] ) {
            --$outIndex;
        }
        
        if( $value == "日期") {
            $value                = "再配日期";
            $inProps["dateIndex"] = $col;                          //6
        }

        $outProps["sheet"]->setCellValueByColumnAndRow($outIndex, 1, $value);
    }
    
    $inProps["highestColIndex"] = $num + 1;
        
    $inProps["sheet"]      = $inProps["baseSheet"];
    $inProps["hashOfRows"] = &$inProps["baseHashOfRows"];
}

function addMatingToOutSheet( &$inProps, &$outProps ) {
    
    $inHighestRowIndex = $inProps["highestRowIndex"];
    $inSheet           = $inProps["sheet"];
    $inEartagIndex     = $inProps["eartagIndex"];
    $inDateIndex       = $inProps["dateIndex"];
    $inHighestColIndex = $inProps["highestColIndex"];
        
    $outSheet          = $outProps["sheet"];
    
    $duplicateCount = 0;
    for ($row = 2; $row <= $inHighestRowIndex; ++$row) {
        $hashKey = $inSheet->getCellByColumnAndRow( $inEartagIndex, $row)->getValue();
        $curDate = $inSheet->getCellByColumnAndRow( $inDateIndex,   $row)->getValue();

        //5天内多次配种，只取一次做后续分析
        $duplicateMating = false;
        if( is_eartag_exists($hashKey, $inProps["hashOfRows"]) ) {
            $rowIndexOrArray  = $inProps["hashOfRows"][$hashKey];
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
        
        for ($col = 1; $col <= $inHighestColIndex; ++$col) {
            $value = $inSheet->getCellByColumnAndRow($col, $row)->getValue();
            $outSheet->setCellValueByColumnAndRow($col, $row - $duplicateCount, $value);
        }
    }
}

//统一使用先Hash，然后再去重复的方法，结果总是出错
//等将来有带宽的时候再优化
function addMatingToOutSheetV2( &$inProps, &$outProps ) {
    $inSheet           = $inProps["sheet"];
    $outSheet          = $outProps["sheet"];

    $inDateIndex       = $inProps["dateIndex"];
    $inHighestColIndex = $inProps["highestColIndex"];
        
    hashOfRowIndexByEartag( $inProps );
    
    $appendMaitingRowByDate = function($inSheet, $outSheet, $inRowIndex, &$outRowIndex, $inHighestColIndex) {
        for ($col = 1; $col <= $inHighestColIndex; ++$col) {
            $value = $inSheet->getCellByColumnAndRow($col, $inRowIndex)->getValue();
            $outSheet->setCellValueByColumnAndRow($col, $outRowIndex , $value);
        }
        $outRowIndex++;
    };
    
    $outRowIndex = 2;
    foreach( $inProps["hashOfRows"] as $eartag=>$rowIndexOrArray ) {

        if( is_array( $rowIndexOrArray )) {
            
            $num       = count( $rowIndexOrArray );
            $dateArray = array();
                    
            for($idx = 0; $idx < $num; ++$idx){ 
                $date = $inSheet->getCellByColumnAndRow($inDateIndex, $rowIndexOrArray[$idx])->getValue(); 
                $dateArray[$idx] = $date;
            }
            
            // 先存入第一个
            $appendMaitingRowByDate($inSheet, $outSheet, $rowIndexOrArray[0], $outRowIndex, $inHighestColIndex);
            
            for($cur = 1; $cur < $num; ++$cur){ 
                
                $duplicateMating = false;    
                for($pre = 0; $pre < $cur; ++$pre ) {
                    
                    $diffDays = diffInDays($dateArray[$pre], $dateArray[$cur]);

                    if( $diffDays >= -5 && $diffDays <= 5 ) {
                        $duplicateMating = true;  
                        break;
                    }
                }
                if( ! $duplicateMating ) {
                    $appendMaitingRowByDate($inSheet, $outSheet, $rowIndexOrArray[ $cur ], $outRowIndex, $inHighestColIndex);
                }
            }
        } 
        else {
            $appendMaitingRowByDate($inSheet, $outSheet, $rowIndexOrArray, $outRowIndex, $inHighestColIndex);
        }
    }
}

//Robin: 这是一个丑陋的Bug Fix
//输入的表里，耳号可能是double，全部转成了string再比较
function is_eartag_exists($eartag, &$eartagArray) {
    $type = gettype( $eartag );
    if( $type != 'string' && $type != "integer") {
        settype( $eartag, "string");
    }
    
    return array_key_exists($eartag, $eartagArray);
}

function addWeaningToOutSheet( &$inProps, &$outProps){ 
    $outSheet             = $outProps["sheet"];
    $outHighestRowIndex   = $outProps["highestRowIndex"];
    $outEartagIndex       = $outProps["eartagIndex"];
    $outBirthDateIndex    = $outProps["birthDateIndex"];
        
    for ($outRowIndex = 2; $outRowIndex <= $outHighestRowIndex; ++$outRowIndex) {

        $eartag = $outSheet->getCellByColumnAndRow($outEartagIndex, $outRowIndex)->getValue();
        
        
        if( ! is_eartag_exists($eartag, $inProps["hashOfRows"]) ){
            continue;  //配种后无断奶
        }

        $rowIndexOrArray  = $inProps["hashOfRows"][ $eartag ];
        
        // 配种未分娩，分娩日期为空
        $birthDate = $outProps["sheet"]->getCellByColumnAndRow($outBirthDateIndex, $outRowIndex)->getValue();
        if( ! $birthDate ) {
            continue;
        }
                
        if( is_array($rowIndexOrArray) ) {
            $num = count( $rowIndexOrArray ); 
            for($idx = 0; $idx < $num; ++$idx){ 
                $rowIndex = $rowIndexOrArray[ $idx ];
                if( appendWeaningToMatingRowByDate($inProps, $outProps, $rowIndex, $outRowIndex) ) {
                    break;
                }
            }
        }
        else{
            appendWeaningToMatingRowByDate($inProps, $outProps, $rowIndexOrArray, $outRowIndex);
        }
    }
}

function addBirthToOutSheet( &$inProps, &$outProps ) {
    
    $outSheet             = $outProps["sheet"];
    $outHighestRowIndex   = $outProps["highestRowIndex"];
    $outEartagIndex       = $outProps["eartagIndex"];
        
    for ($outRowIndex = 2; $outRowIndex <= $outHighestRowIndex; ++$outRowIndex) {

        $key = $outSheet->getCellByColumnAndRow($outEartagIndex, $outRowIndex)->getValue();
        
        if( ! is_eartag_exists($key, $inProps["hashOfRows"]) ){
            continue;  //配种未分娩
        }

        $rowIndexOrArray  = $inProps["hashOfRows"][ $key ];

        if( is_array($rowIndexOrArray) ) {
            $num = count( $rowIndexOrArray ); 
            for($idx = 0; $idx < $num; ++$idx){ 
                $rowIndex = $rowIndexOrArray[ $idx ];
                if(appendBirthToMatingRowByDate($inProps, $outProps, $rowIndex, $outRowIndex)) {
                    break;
                }
            }
        }
        else{
            appendBirthToMatingRowByDate($inProps, $outProps, $rowIndexOrArray, $outRowIndex);
        }
    }
}

function addNextMatingToOutSheet(&$inProps, &$outProps ) {
    
    $outSheet             = $outProps["sheet"];
    $outHighestRowIndex   = $outProps["highestRowIndex"];
    $outEartagIndex       = $outProps["eartagIndex"];
    $birthDateIndex       = $outProps["birthDateIndex"];
    
    $inSheet              = $inProps["sheet"];
    $matingDateIndex      = $inProps["dateIndex"];
        
    for ($outRowIndex = 2; $outRowIndex <= $outHighestRowIndex; ++$outRowIndex) {

        $key = $outSheet->getCellByColumnAndRow($outEartagIndex, $outRowIndex)->getValue();

        if( ! is_eartag_exists($key, $inProps["baseHashOfRows"] )) {
            continue;
        }       
        
        //在同一张配种表中，找到下一个配种记录
        if( is_array( $inProps["baseHashOfRows"][ $key ] ) ) {
            
            $rowIndexOrArray  = $inProps["baseHashOfRows"][ $key ];
            
            $birthDate = $outSheet->getCellByColumnAndRow($birthDateIndex, $outRowIndex)->getValue();
            
            if( !$birthDate ) {
                continue;
            }
                        
            $num = count( $rowIndexOrArray ); 
            for($idx = 0; $idx < $num; ++$idx){ 
                $inRowIndex = $rowIndexOrArray[ $idx ];
                
                $matingDate = $inSheet->getCellByColumnAndRow($matingDateIndex, $inRowIndex)->getValue();
                if( !$matingDate ) {
                    continue;
                }
                
                if( diffInDays($birthDate, $matingDate) > 0) {
                                             
                     for ($col = 1; $col <= $inProps["highestColIndex"]; ++$col) {
                        if($col != $inProps["eartagIndex"]) {
                            
                            $value = $inSheet->getCellByColumnAndRow($col, $inRowIndex)->getValue();
                            $outIndexToInsert = $outProps["highestColIndex"] + $col;
                            
                            if($col > $inProps["eartagIndex"]) {
                                $outIndexToInsert--;
                            }
                            
                            $outSheet->setCellValueByColumnAndRow($outIndexToInsert, $outRowIndex, $value);
                        }
                    }
                    break;
                }
            }
        }
    }
}

function addOneExtraColumnToHeader(&$inProps, &$outProps, $extraColName) {
    
    $outSheet = $outProps["sheet"];
    //日期去掉一列，这里不再+1;
    $outIndex = $outProps["highestColIndex"] + $inProps["highestColIndex"];
    
    $outSheet->setCellValueByColumnAndRow($outIndex, 1, $extraColName);
}

function addPregnantCheckToOutSheet( &$inProps, &$outProps){ 
    $outSheet             = $outProps["sheet"];
    $outHighestRowIndex   = $outProps["highestRowIndex"];
    $outEartagIndex       = $outProps["eartagIndex"];
    $outBirthDateIndex    = $outProps["birthDateIndex"];
    $outMatingDateIndex   = $outProps["matingDateIndex"];

        
    for ($outRowIndex = 2; $outRowIndex <= $outHighestRowIndex; ++$outRowIndex) {

        $eartag = $outSheet->getCellByColumnAndRow($outEartagIndex, $outRowIndex)->getValue();
             
        if( ! is_eartag_exists($eartag, $inProps["hashOfRows"]) ){
            continue;  //配种后无孕检
        }

        $rowIndexOrArray  = $inProps["hashOfRows"][ $eartag ];
        
        if( is_array($rowIndexOrArray) ) {
            $num = count( $rowIndexOrArray ); 
            for($idx = 0; $idx < $num; ++$idx){ 
                $rowIndex = $rowIndexOrArray[ $idx ];
                if( appendPregnantCheckToMatingRowByDate($inProps, $outProps, $rowIndex, $outRowIndex, $eartag) ) {
                    break;
                }
            }
        }
        else{
            appendPregnantCheckToMatingRowByDate($inProps, $outProps, $rowIndexOrArray, $outRowIndex, $eartag);
        }
    }
}



function appendPregnantCheckToMatingRowByDate(&$inProps, &$outProps, $rowIndex, $outRowIndex, $eartag){
    
    $pregnantCheckDate = $inProps["sheet"]->getCellByColumnAndRow($inProps["dateIndex"], $rowIndex)->getValue();
    $matingDate        = $outProps["sheet"]->getCellByColumnAndRow($outProps["matingDateIndex"], $outRowIndex)->getValue();
        
    $diffDays  = diffInDays($matingDate, $pregnantCheckDate);
        
    $findMatch = false;
    if($diffDays > 0 and $diffDays < 125 ){
        $findMatch = true;
        $outIndexToInsert = -1;
        for ($col = 1; $col <= $inProps["highestColIndex"]; ++$col) {
            if($col != $inProps["eartagIndex"]) {
                $value = $inProps["sheet"]->getCellByColumnAndRow($col, $rowIndex)->getValue();
                $outIndexToInsert = $outProps["highestColIndex"] + $col;
                if($col > $inProps["eartagIndex"]) {
                    $outIndexToInsert--;
                }
                $outProps["sheet"]->setCellValueByColumnAndRow($outIndexToInsert, $outRowIndex, $value);
            }
        }
        
        if( is_array($inProps["baseHashOfRows"][$eartag])) {
            $count = count($inProps["baseHashOfRows"][$eartag]);
            for($idx = 0; $idx < $count; $idx++) {
                $nextMatingDate = $inProps["baseSheet"]->getCellByColumnAndRow($outProps["matingDateIndex"], $inProps["baseHashOfRows"][$eartag][$idx])->getValue();
                if( diffInDays($pregnantCheckDate, $nextMatingDate) >= 0 ) {
                    $outProps["sheet"]->setCellValueByColumnAndRow(++$outIndexToInsert, $outRowIndex, $nextMatingDate);
                    break;
                }
            }
        }
    }
    
    return $findMatch;
}

function addLeaveToOutSheet( &$inProps, &$outProps){ 
    $outSheet             = $outProps["sheet"];
    $outHighestRowIndex   = $outProps["highestRowIndex"];
    $outEartagIndex       = $outProps["eartagIndex"];
        
    for ($outRowIndex = 2; $outRowIndex <= $outHighestRowIndex; ++$outRowIndex) {

        $eartag = $outSheet->getCellByColumnAndRow($outEartagIndex, $outRowIndex)->getValue();
        
        if( ! is_eartag_exists($eartag, $inProps["hashOfRows"]) ){
            continue;  
        }

        //离场只可能出现一次，如有重复记录，只取一次，忽略其他记录。
        $rowIndex  = $inProps["hashOfRows"][ $eartag ];
        if( is_array($rowIndex) ) {
            $rowIndex = $rowIndex[0];
        }
        
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

function addEntryToOutSheet($inProps, $outProps) {
    $outSheet             = $outProps["sheet"];
    $outHighestRowIndex   = $outProps["highestRowIndex"];
    $outEartagIndex       = $outProps["eartagIndex"];
        
    for ($outRowIndex = 2; $outRowIndex <= $outHighestRowIndex; ++$outRowIndex) {

        $eartag = $outSheet->getCellByColumnAndRow($outEartagIndex, $outRowIndex)->getValue();
        
        if( ! is_eartag_exists($eartag, $inProps["hashOfRows"]) ){
            continue;  
        }

        //进群只可能出现一次，如有重复记录，只取一次，忽略其他记录。
        $rowIndex  = $inProps["hashOfRows"][ $eartag ];
        if( is_array($rowIndex) ) {
            $rowIndex = $rowIndex[0];
        }
        
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


function render($inFilePath, $outFilePath, $printDebug = false) {
    
    $debugStartTime = microtime(true);

    $inProps  = array();
    $outProps = array();

    $outSpreadsheet = new Spreadsheet();
    $outProps["sheet"]       = $outSpreadsheet->getActiveSheet();
    $outProps["sheet"]->setTitle("TopFarmDataSource");

    $reader = PhpOffice\PhpSpreadsheet\IOFactory::createReader("Xlsx");
    $reader->setReadDataOnly(true);
    $reader->setLoadSheetsOnly(["配种", "分娩", "断奶", "孕检", "离场", "进群"]);
    
    $inSpreadsheet          = $reader->load( $inFilePath );
    $inProps["spreadsheet"] = $inSpreadsheet;
    
    $debugStartNoIO = microtime(true);

    $outProps["matingDateIndex"] = loadInSheetAndSetupProps("配种", $inProps, $outProps, true);
    addMatingToOutSheetV2($inProps, $outProps);
    $outProps["highestRowIndex"]   = $outProps["sheet"]->getHighestRow();
    $outProps["highestColIndex"]   = $inProps["highestColIndex"];
    
    $inProps["baseSheet"]        = $inProps["sheet"];
    $inProps["baseHashOfRows"]   = $inProps["hashOfRows"];
    
    $outProps["birthDateIndex"] = loadInSheetAndSetupProps("分娩", $inProps, $outProps);
    hashOfRowIndexByEartag( $inProps );
    addBirthToOutSheet($inProps, $outProps);
    $outProps["highestColIndex"] = $outProps["highestColIndex"] + $inProps["highestColIndex"] - 1;
    
    $outProps["weaningDateIndex"] = loadInSheetAndSetupProps("断奶", $inProps, $outProps);
    hashOfRowIndexByEartag( $inProps );
    addWeaningToOutSheet($inProps, $outProps);
    $outProps["highestColIndex"] = $outProps["highestColIndex"] + $inProps["highestColIndex"] - 1;

    $outProps["pregnantCheckDateIndex"] = loadInSheetAndSetupProps("孕检", $inProps, $outProps);
    
    //孕检添加“异常复配日期”
    addOneExtraColumnToHeader($inProps, $outProps, "异常复配日期");
    hashOfRowIndexByEartag( $inProps );
    addPregnantCheckToOutSheet($inProps, $outProps);
    $outProps["highestColIndex"] = $outProps["highestColIndex"] + $inProps["highestColIndex"];

    $outProps["leaveDateIndex"] = loadInSheetAndSetupProps("离场", $inProps, $outProps);
    hashOfRowIndexByEartag( $inProps );
    addLeaveToOutSheet($inProps, $outProps);
    $outProps["highestColIndex"] = $outProps["highestColIndex"] + $inProps["highestColIndex"] - 1;
    
    $outProps["entryDateIndex"] = loadInSheetAndSetupProps("进群", $inProps, $outProps);
    hashOfRowIndexByEartag( $inProps );
    addEntryToOutSheet($inProps, $outProps);
    $outProps["highestColIndex"] = $outProps["highestColIndex"] + $inProps["highestColIndex"] - 1;
    
    loadNextMatingAndSetupProps($inProps, $outProps);
    addNextMatingToOutSheet($inProps, $outProps);
    $outProps["highestColIndex"] = $outProps["highestColIndex"] + $inProps["highestColIndex"] - 1;

    $debugEndNoIO = microtime(true);

    
    $writer = PhpOffice\PhpSpreadsheet\IOFactory::createWriter($outSpreadsheet, "Xlsx");

    $writer->save( $outFilePath );
    
    if( $printDebug ) {
        echo "\nComplete TopFarmDataSource File: ", $outFilePath;
        $debugEndTime = microtime(true);
        echo "\n文件共 ", $outProps["highestRowIndex"], " 行, ", $outProps["highestColIndex"], "列";
        echo "\n执行时间：", round($debugEndTime - $debugStartTime, 3), "秒";
        echo "\n不计算IO的执行时间：", round($debugEndNoIO - $debugStartNoIO, 3), "秒\n";
    }
}

//For Testing.
render("2019LHMS.xlsx", "Processed.xlsx", true);
