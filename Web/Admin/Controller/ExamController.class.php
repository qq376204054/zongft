<?php
namespace Admin\Controller;
use Think\Controller;
/**
 * 考题相关控制器
 * Class ExamController
 * @package app\admin\controller
 */
class ExamController extends BaseController
{
    /**
     * 考题管理
     */
    public function manger(){
        $list = M('exam')->where(array('status'=>1))->select();
        foreach($list as $key=>$value){
            $list[$key]['content'] = json_decode($value['content'],true);
            $list[$key]['answer'] = json_decode($value['answer'],true);
        }
        $zimu_arr = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];
        $this->assign('zimu_arr', $zimu_arr);
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 添加考题
     */
    public function add(){
        if(IS_POST){
            if(!$this->data['name']){$this->errorjsonReturn("请输入标题");}
            if(!$this->data['score']){$this->errorjsonReturn("请输入分值");}
            if(!in_array($this->data['type'],array('checkbox','radio'))){$this->errorjsonReturn("请选择类型");}
            if(!$this->data['value']){$this->errorjsonReturn("请输入答案");}
            foreach($this->data['value'] as $key=>$value){
                if(trim($value)==""){
                    unset($key);
                }else{
                    $this->data['value'][$key] = trim($value);
                }
            }
            $this->data['value'] = array_values($this->data['value']);
            $answers = [];
            foreach($this->data['key'] as $key=>$value){
                if($value=="true"){
                    $answers[] = $key;
                }
            }
            if(!$answers){$this->errorjsonReturn('请选择答案');}
            $add = array();
            if($this->data['id']){
                $add['id'] = $this->data['id'];
            }else{
                $add['id'] = time();
            }
            $add['name'] = $this->data['name'];
            $add['answer'] = json_encode($answers);
            $add['content'] = json_encode($this->data['value']);
            $add['score'] = $this->data['score'];
            $add['type'] = $this->data['type'];
            if($this->data['id']){
                $return = M('exam')->where(array('id'=>$add['id']))->save($add);
                if($return===false){$this->errorjsonReturn('操作失败');}
            }else{
                $return = M('exam')->add($add);
                if(!$return){$this->errorjsonReturn('操作失败');}
            }
            $this->setjsonReturn('操作成功');
        }ELSE{
            if($this->data['id']){
                $info = M('exam')->where(array('id'=>$this->data['id']))->find();
                $info['answer'] = json_decode($info['answer'],true);
                $info['content'] = json_decode($info['content'],true);
                $this->assign('info',$info);
            }
            $this->display();
        }
    }

    /**
     * 删除东西
     */
    public function delete(){
        $return = M('exam')->where(array('id'=>$this->data['id']))->save(array('status'=>2));
        $return===false?$this->errorjsonReturn('操作失败'):$this->setjsonReturn('操作成功');
    }

    /**
     * 试卷考试中心
     */
    public function work(){
        $count = M('exam')->where(array('status'=>1))->sum('score');
        $list = M('exam')->where(array('status'=>1))->select();
        foreach($list as $key=>$value){
            $list[$key]['content'] = json_decode($value['content'],true);
            $list[$key]['answer'] = json_decode($value['answer'],true);
        }
        $zimu_arr = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];
        $this->assign('zimu_arr', $zimu_arr);
        $this->assign('count', $count);
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 提交试卷
     */
    public function workaction(){
        $list = M('exam')->where(array('status'=>1))->select();
        $addAll = array();
        $is_all = true;
        $time = time();
        foreach($list as $key=>$value){
            $one = array();
            if(!$this->data['answer'][$value['id']]){
                $is_all = false;
            }
            $one['exam_id'] = $value['id'];
            $one['answer'] = $value['answer'];
            $one['name'] = $value['name'];
            $one['content'] = $value['content'];
            $one['score'] = $value['score'];
            $one['type'] = $value['type'];
            $one['user_id'] = $this->userInfo['id'];
            $one['user_answer'] = json_encode($this->data['answer'][$value['id']]);
            $one['right'] = implode(',',json_decode($value['answer'],true))==implode(',',$this->data['answer'][$value['id']])?1:0;
            $one['get_score'] = implode(',',json_decode($value['answer'],true))==implode(',',$this->data['answer'][$value['id']])? $value['score']:0;
            $one['create_time'] = $time;
            $addAll[] = $one;
        }
        if($is_all===false){$this->errorjsonReturn('还有题目未完成');}
        $return = M('exam_action')->addAll($addAll);
        if(!$return){$this->errorjsonReturn('提交失败，请重新提交');}
        $this->setjsonReturn(array('user_id'=>$this->userInfo['id'],'time'=>$time));

    }

    /**
     * 考试的列表详情
     */
    public function actionlist(){
        $where = array();
        //找出我权限下面的所有人
        if($this->userInfo['data_auth'] != 1){
            $where['user_id'] = $this->userInfo['id'];
        }
        $list = M('exam_action')->where($where)->field("SUM(`score`) as all_score,sum(`get_score`) as all_get_score,count(*) as all_num,sum(`right`) as right_num,`user_id`,`create_time`")->group('user_id,create_time')->Page($this->p,$this->perpage)->order($this->desc)->select();
        $user_ids = array_column($list,'user_id');
        if($user_ids){
            $user_names = M('user')->where(array('id'=>array('in',$user_ids)))->getField('id,user_name',true);
        }
        foreach($list as $k=>$v){
            $list[$k]['user_name'] = $user_names[$v['user_id']]?$user_names[$v['user_id']]:"";
            $list[$k]['create_time_true'] = date('Y-m-d H:i:s',$v['create_time']);
        }

        $this->assign('list', $list);
        $this->assign('list_table', true);
        $this->display();
    }

    /**
     * 考试的详情
     */
    public function examdetail(){
        $where = array();
        $where['user_id'] = $this->data['user_id'];
        $where['create_time'] = $this->data['time'];
        $list = M('exam_action')->where($where)->order($this->desc)->select();
        $count = M('exam_action')->where($where)->field("SUM(`score`) as all_score,sum(`get_score`) as all_get_score,count(*) as all_num,sum(`right`) as right_num,`user_id`,`create_time`")->find();
        $user_name = M('user')->where(array('id'=>array('eq',$this->data['user_id'])))->getField('user_name');
        foreach($list as $key=>$value){
            $list[$key]['content'] = json_decode($value['content'],true);
            $list[$key]['answer'] = json_decode($value['answer'],true);
            $list[$key]['user_answer'] = json_decode($value['user_answer'],true);
        }
        $zimu_arr = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];
        $this->assign('zimu_arr', $zimu_arr);
        $this->assign('count', $count);
        $this->assign('list', $list);
        $this->assign('user_name', $user_name);
        $this->display();
    }

    /**
     * 添加附件
     */
    public function addfile(){
        if(IS_POST){
            //下面上传附件
            $upload = new \Think\Upload();// 实例化上传类
//            $upload->exts  = array('pdf','doc','docx','xls','xlsx','txt','rar','zip','jpg','png','jpeg','gif');// 设置附件上传类型
            $upload->rootPath='./Files/'; //保存根路径
            $upload->autoSub = true;
            $upload->subType = 'date';
            $upload->dateFormat = 'Y-m-d';
            $upload->maxSize  = 20*1024*1024 ;// 设置附件上传大小 20M
            $file = $_FILES['imgFile'];
            $info   = $upload->upload();
            $all_setting = M('config')->where(array('name'=>'exam_teach_file'))->getField('value');
            $all_setting = $all_setting?json_decode($all_setting,true):array();
            $all_setting[] = array(
                'name'=>$info['imgFile']['name'],
                'url'=>__ROOT__.'/Files/'.$info['imgFile']['savepath'].$info['imgFile']['savename']
            );
            M('config')->where(array('name'=>'exam_teach_file'))->save(array('value'=>json_encode($all_setting)));
            $this->setjsonReturn(array(
                'url'=>__ROOT__.'/Files/'.$info['imgFile']['savepath'].$info['imgFile']['savename']
            ));
        }else{
            $this->display();
        }
    }

    /**
     * 添加附件
     */
    public function addfile_list(){
        $all_setting = M('config')->where(array('name'=>'exam_teach_file'))->getField('value');
        $all_setting = $all_setting?json_decode($all_setting,true):array();
        $this->assign('all_setting',$all_setting);
        $this->display();
    }

    /**
     * 删除某个附件
     */
    public function del_action(){
        $all_setting = M('config')->where(array('name'=>'exam_teach_file'))->getField('value');
        $all_setting = $all_setting?json_decode($all_setting,true):array();
        unset($all_setting[$this->data['index']]);
        $all_setting = array_values($all_setting);
        $return = M('config')->where(array('name'=>'exam_teach_file'))->save(array('value'=>json_encode($all_setting)));
        $return===false?$this->errorjsonReturn('删除失败'):$this->setjsonReturn('删除成功');
    }

    /**
     * 添加附件
     */
    public function showfile_list(){
        $all_setting = M('config')->where(array('name'=>'exam_teach_file'))->getField('value');
        $all_setting = $all_setting?json_decode($all_setting,true):array();
        $this->assign('all_setting',$all_setting);
        $this->display();
    }



}

