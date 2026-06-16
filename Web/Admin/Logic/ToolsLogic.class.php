<?php
namespace Admin\Logic;

class ToolsLogic
{
    /**
     * 上传csv文件并且返回里面的所有数据
     */
    public function uploadCsv($csvfile){
        if ($csvfile) {
            $csvList=array();
            $filename = $csvfile['tmp_name'];
            if (empty ($filename)) {return '请选择上传的文件';}
            $file = fopen($filename,'r');
            while ($data = fgetcsv($file)) {
                foreach($data as $k=>$v){
                    //解决iconv函数无法转换某些中文的问题,将gb2312改成gbk 弢 贇 旻晔
                    $data[$k]=trim(iconv('gbk', 'utf-8', $v));
                }
                $csvList[] = $data;
            }
            fclose($file);
            unset($csvList[0]);
            return $csvList;
        }else{
            return '请选择上传的文件';
        }
    }

}