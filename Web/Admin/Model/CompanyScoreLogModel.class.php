<?php
namespace Admin\Model;
use Think\Model;

/**
 * 公司的份数变动表
 * Class CompanyBranchModel
 * @package Admin\Model
 */
class CompanyScoreLogModel extends Model
{
    /**
     * 添加公司积分变动日志
     * @param $company_id
     * @param $score
     * @param int $create_user_id
     * @return mixed
     */
    public function addLog($company_id,$score,$create_user_id=0){
        $add = array();
        $add['company_id'] = $company_id;
        $add['score'] = $score;
        $add['create_user_id'] = $create_user_id;
        $add['create_time'] = time();
        $return = $this->add($add);
        return $return;
    }
}