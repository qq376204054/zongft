<?php
namespace Admin\Model;
use Think\Model;

class BusinessModel extends Model{

    private static $instance;

    /**
     * 单例模式
     */
    public static function instance(){
        if(!(self::$instance instanceof self)){
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * 获取数据列表
     */
    public function getList($where, $order = '', $limit = '', $field = '*') {
        if (empty($order)) {
            $order = $this->getPk() . ' DESC';
        }
        $data = $this->field($field)->where($where)->order($order)->limit($limit)->index($index)->select();
        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
        }
        return $data;
    }
}