<?php
namespace Admin\Logic;

use Admin\Model\ConfigModel;

class UploadLogic
{
    /**
     * 上传流附件
     * @param $base64
     * @return array
     */
    public function uploadStreamDocument($base64,$user_id){
        if(!$user_id){return '非法操作';}
        if(!$base64){return '请上传附件';}
        $image64 = substr($base64, strpos($base64, ',') + 1);
        $imageType=explode(';',explode('/',substr($base64, 0,strpos($base64, ',')))[1])[0];
        $img = base64_decode($image64);
        $day=date('Y-m-d',time());
        $fileDir="./Uploads/minEditor/image/".$day;
        $saveDbDir="/Uploads/minEditor/image/".$day;
        if(!file_exists($fileDir)){mkdir($fileDir); }
        $name=$user_id.rand(1,10000).time();
        $a = file_put_contents($fileDir.'/'.$name.'.'.$imageType, $img);//返回的是字节数
        if(!$a){return '上传失败';}
        $data=array(
            "user_id"=>$user_id?$user_id:0,
            "name"=>$name.".".$imageType,
            "original"=>$name.".".$imageType,
            "type" =>$imageType,
            "url" => $saveDbDir.'/'.$name.'.'.$imageType,
            "size"=>$a,
            "createtime"=>time()
        );
        $id=M('upload')->add($data);
        if($id){
            $data['id']=$id;
            return $data;
        }else{
            return '上传失败';
        }
    }

    /**
     * 生成缩略图  只允许上传gif jpg png的图片
     * @param $source_path
     * @param string $wmax 最大宽度
     * @return array
     */
    public function resizeImage($source_path,$wmax,$savePath){
        $imagedata = getimagesize($source_path);
        $olgWidth = $imagedata[0];
        $oldHeight = $imagedata[1];
        $newWidth=$olgWidth;
        $newHeight=$oldHeight;
        //根据最大值，算出另一个边的长度，得到缩放后的图片宽度和高度,只有宽度太大才操作
        if(($wmax!=true)&&($olgWidth > $wmax)){
            $newWidth = $wmax;
            $newHeight = $oldHeight*($wmax/$olgWidth);
        }
        $image = imagecreatefrompng($source_path);
        if(!$image){
            $image = imagecreatefromjpeg($source_path);
            if(!$image){
                $image = imagecreatefromgif($source_path);
                if($image){
                    $thumb = imagecreatetruecolor ($newWidth, $newHeight);
                    imagecopyresized ($thumb, $image, 0, 0, 0, 0, $newWidth, $newHeight, $olgWidth, $oldHeight);
                    imagegif($thumb, $savePath);
                }else{
                    return false;
                }
            }else{
                $thumb = imagecreatetruecolor ($newWidth, $newHeight);
                imagecopyresized ($thumb, $image, 0, 0, 0, 0, $newWidth, $newHeight, $olgWidth, $oldHeight);
                ImageJpeg($thumb, $savePath);
            }
        }else{
            $thumb = imagecreatetruecolor ($newWidth, $newHeight);
            imagecopyresized ($thumb, $image, 0, 0, 0, 0, $newWidth, $newHeight, $olgWidth, $oldHeight);
            imagepng($thumb, $savePath);
        }

        imagedestroy($thumb);
        imagedestroy($image);
    }

    /**
     * hbuilder公共的上传逻辑
     * @return mixed
     */
    function upFileForHbuilderLogic($user_id){
        $canUpImg=array_filter(explode(',',(new ConfigModel())->getConfigByTypeModel('fileTypesImg',3)));
        $canUpFile=array_filter(explode(',',(new ConfigModel())->getConfigByTypeModel('fileTypesFile',3)));
        $moveFileArr=array();
        // 枚举所有提交的文件
        foreach ( $_FILES as $name=>$file ) {
            $fn=$file['name'];
            //分割名字
            $ft=strrpos($fn,'.',0);
            //得到附件额名字
            $fm=strtolower(substr($fn,0,$ft));
            //得到附件的后缀
            $fe=strtolower(substr($fn,$ft+1));
            //根据上传文件的类型分别指定图片文件夹和附件文件夹
            if (in_array($fe,$canUpImg)) {$foder='./Uploads/common/image/';}
            elseif (in_array($fe,$canUpFile)) {$foder='./Uploads/common/file/';}
            else {return '您上传的类型不允许';}
            $day=date('Y-m-d',time());
            if(!file_exists($foder.$day)){if(!mkdir($foder.$day)){return '创建文件夹失败';} }
            //下面创建附件的名字
            $fileName=rand(1, 10000).$user_id.rand(1, 10000).'.'.$fe;
            $moveFileArr[]=array(
                'tmp_name'=>$file['tmp_name'],
                'src'=>$foder.$day.'/'.$fileName,
                'type'=>$file['type'],
                'size'=>$file['size']
            );
        }
        $ret=array();
        foreach($moveFileArr as $value){
            // 将临时文件保存
            move_uploaded_file($value['tmp_name'],$value['src']);
            $ret[]=array('url'=>trim($value['src'],'.'),'type'=>$value['type'],'size'=>$value['size'],);
        }
        if(!$ret){return '请选择上传文件';}
        return $ret;
    }
}