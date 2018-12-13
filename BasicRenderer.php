<?php

require 'vendor/autoload.php';


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$out_ssheets = new Spreadsheet();
$outsheet = $out_ssheets->getActiveSheet();
$outsheet->setTitle("TopFarmSource");

$reader = PhpOffice\PhpSpreadsheet\IOFactory::createReader("Xlsx");
$reader->setReadDataOnly(true);
$reader->setLoadSheetsOnly(["配种", "分娩"]);
$in_ssheets = $reader->load("TemplateRecords.xlsx");
$insheet = $in_ssheets->getSheetByName("配种");

// Robin Experiment: Iterater 
//foreach ($insheet->getRowIterator() as $row) {
//    $cellIterator = $row->getCellIterator();
//    $cellIterator->setIterateOnlyExistingCells(FALSE); 
//    foreach ($cellIterator as $cell) {
//        $cell->getValue()
//    }
//}

// Get the highest row and column numbers referenced in the worksheet
$highestRow = $insheet->getHighestRow(); 
$highestColumn = $insheet->getHighestColumn(); 
$highestColumnIndex = PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn); 

//Process Header Renaming
for ($col = 1; $col <= $highestColumnIndex; ++$col) {
    $value = $insheet->getCellByColumnAndRow($col, 1)->getValue();
    if( $value == "日期") {
        $value = "配种日期";
    }
        
    $outsheet->setCellValueByColumnAndRow($col, 1, $value);
}

//Process Data Rows
for ($row = 2; $row <= $highestRow; ++$row) {
    for ($col = 1; $col <= $highestColumnIndex; ++$col) {
        $value = $insheet->getCellByColumnAndRow($col, $row)->getValue();
        
        $outsheet->setCellValueByColumnAndRow($col, $row, $value);

    }
}

$writer = PhpOffice\PhpSpreadsheet\IOFactory::createWriter($out_ssheets, "Xlsx");

//Debug Location
$writer->save("Processed.xlsx");


//This is target location
//$writer->save("/172.21.0.6/ExportsForTableau/Processed.xlsx");

//Save as CSV File, very possible.
//$writer = new \PhpOffice\PhpSpreadsheet\Writer\Csv($out_ssheets);
//Enable UTF8
//$writer->setUseBOM(true);
//$writer->save("/172.21.0.6/ExportsForTableau/Processed.csv");
