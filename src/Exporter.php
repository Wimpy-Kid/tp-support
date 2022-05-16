<?php

namespace CherryLu\TpSupport;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class Exporter
{
    public static function export($header, $data, $filename = '导出数据', $detailName = 'detail')
    {

        $newExcel = new Spreadsheet();  //创建一个新的excel文档
        $objSheet = $newExcel->getActiveSheet();  //获取当前操作sheet的对象
        $objSheet->setTitle($filename);  //设置当前sheet的标题
        $lines = 1;

        $header && $header = array_change_key_case($header,CASE_LOWER);

        if (isset($header[$detailName])) {
            $descField = array_change_key_case($header[$detailName],CASE_LOWER);;
            $descKeyField = array_keys($descField);
            unset($header[$detailName]);
            $titleFieldArr = array_merge($header, $descField);
            $key_field = array_keys($header);
        } else {
            $titleFieldArr = $header;
            $key_field = $titleFieldArr ? array_keys($titleFieldArr) : array_keys($data[0]);
        }

        $value_field = $titleFieldArr ? array_values($titleFieldArr) : array_keys($data[0]);

        foreach ($value_field as $k => $v) {
            $letter = static::numLetter($k+1);
            $newExcel->getActiveSheet()->getColumnDimension($letter)->setAutoSize(true); // 简单设置列宽
            $objSheet = $objSheet->setCellValue($letter . $lines, $v); // 设置标题
        }

        foreach ($data as $k => $v) { // 循环外部数据处理(行)
            $lines ++; // 记录外部行号
            $descCount = isset($descField) ? count($v[$detailName]) : 0;
            foreach ($key_field as $m => $n) { // (列)
                $letter = static::numLetter($m+1);

                $objSheet = $objSheet->setCellValue($letter . $lines, $v[$n]);
                $objSheet->getStyle($letter . $lines)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                if ($descCount > 0) { // 处理内部desc数据
                    $descCount > 1 && $newExcel->getActiveSheet()->mergeCells("$letter$lines:$letter".($lines+$descCount-1)); // 单元格合并
                    $descLine = $lines; // 行号(记录多行desc数据行号)
                    foreach ($v[$detailName] as $x => $y) { // 循环desc数据(行)
                        foreach ($descKeyField as $q => $w) { // (列)
                            $letter = static::numLetter(count($key_field)+$q+1);
                            $objSheet = $objSheet->setCellValue($letter . $descLine, $y[$w]);
                        }
                        $descLine ++; // 行号(记录多行desc数据行号)
                    }
                }
            }
            $descCount > 0 && $lines = $lines + $descCount - 1; // 重新处理数据行号
        }

        // downloadExcel($newExcel, '测试表', 'Xlsx', './public'); // 保存到本地
        static::downloadExcel($newExcel, $filename, 'Xlsx'); //生成文件直接下载
    }

    private static function numLetter($num) {
        $num = intval($num);
        if ($num <= 0)
            return false;
        $letterArr = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
        $letter = '';
        do {
            $key = ($num - 1) % 26;
            $letter = $letterArr[$key] . $letter;
            $num = floor(($num - $key) / 26);
        } while ($num > 0);
        return $letter;
    }

    private static function downloadExcel($newExcel, $filename, $format, $savePath = false)
    {
        if(!$savePath){   //网页下载
            // $format只能为 Xlsx 或 Xls
            if ($format == 'Xlsx') {
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            } elseif ($format == 'Xls') {
                header('Content-Type: application/vnd.ms-excel');
            }
            $filename = urlencode($filename);
            header("Content-Disposition: attachment;filename=" . $filename . date('Y-m-d') . '.' . strtolower($format));
            header('Cache-Control: max-age=0');
            $objWriter = IOFactory::createWriter($newExcel, $format);
            $objWriter->save('php://output');
            exit;
        } else {
            ob_clean();
            ob_start();
            $objWriter = IOFactory::createWriter($newExcel, $format);
            $savePath = trim(trim($savePath, '.'), '/') . '/' . $filename . date('Y-m-d') . '.' . strtolower($format);
            $objWriter->save($savePath);
            // 释放内存
            $newExcel->disconnectWorksheets();
            unset($newExcel);
            ob_end_flush();
        }
    }

}

