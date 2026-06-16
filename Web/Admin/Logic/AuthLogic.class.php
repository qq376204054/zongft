<?php
namespace Admin\Logic;

class AuthLogic{

    //默认配置
    protected $_config = array(
        'AUTH_ON' => true, //认证开关
        'AUTH_TYPE' => 1, // 认证方式，1为时时认证；2为登录认证。
        'AUTH_GROUP' => 'think_auth_group', //用户组数据表名
        'AUTH_GROUP_ACCESS' => 'think_auth_group_access', //用户组明细表
        'AUTH_RULE' => 'think_auth_rule', //权限规则表
        'AUTH_USER' => 'think_members'//用户信息表
    );

    /**
     * @param $name 权限唯一标识 可以是字符串或数组或逗号分割
     * @param $uid 认证的用户id
     * @param string $relation or属性 若干个一个条件通过则通过，如果为false
     *                          and属性 若干个需要全部条件通过。
     *                          diff 属性：若干个唯一标识在权限设置中没有就默认通过 传入值中有的哪些唯一标识用and的逻辑
     * @return bool   返回-1  您未登录      -2  权限不足
     */
    public function check($name, $uid, $relation='or') {
        if(!$uid){return -1;}
        if(!$name){return -2;}
        if (!$this->_config['AUTH_ON']) return true;   //认证开关关了 就默认有权限
        //下面转化$name同意为数组格式，array('name1','name2')
        if (is_string($name)) {
            if (strpos($name, ',') !== false) {
                $name = explode(',', $name);
            } else {
                $name = array($name);
            }
        }
        //关系等于diff 属性
        if($relation=='diff'){
            //查看后台有没有设置这些权限
            $hasRules=M('user_rules')->where(array('name'=>array('in',$name)))->field('name')->select();
            if(empty($hasRules)){return true;}
            //将存在的进行权限验证
            $name=array_column($hasRules,'name');
        }
        $authList = $this->getAuthList($uid); //获取用户的权限列表
        $list = array(); //有权限的name
        foreach($name as $val){
            if(in_array($val,$authList)){$list[] = $val;}
        }
        if($relation=='or'){
            return !empty($list)?true:-2;
        }elseif(($relation=='and')||($relation=='diff')){
            $diff = array_diff($name, $list);
            return empty($diff)?true:-2;
        }
        return -2;
    }

    /**
     * 获取用户的权限列表，将权限保存在缓存
     * @param $uid
     * @return array
     */
    protected function getAuthList($uid) {
        //存在这个用户的权限缓存就使用缓存
        if(S('AUTH_LIST_'.$uid)){
            return S('AUTH_LIST_'.$uid);
        }
        //读取用户所属角色所有信息
        $userInfo = M('user')->where(array('id'=>$uid,'delete'=>1))->find();
        $userRolesIds=$userInfo['role_ids'];
        $userRolesIds_arr=array_filter(array_unique(explode(',',$userRolesIds)));
        if(empty($userRolesIds_arr)){return array();}//没有角色就没有权限

        //找出这些角色的权限
        $where=array();
        $where['id']=array('in',$userRolesIds_arr);
        $where['status']=1;
        $rolesInfos=M('user_roles')->where($where)->field('rules')->select();
        $ruleIds = array();
        foreach ($rolesInfos as $rolesInfo) {
            $ruleIds = array_merge($ruleIds, explode(',',$rolesInfo['rules']));
        }
        $ruleIds = array_unique(array_unique($ruleIds));
        if(empty($ruleIds)){return array();}//一个设置的权限都没有就是没有权限
        //找出这些权限的信息
        $where=array();
        $where['id']=array('in',$ruleIds);
        $where['status']=1;
        $ruleInfos=M('user_rules')->where($where)->select();

        //循环规则，判断结果。
        $authList = array();
        foreach($ruleInfos as $ruleInfo){
            //如果这个权限没有规则，就是默认通过的，不然要匹配规则
            if (empty($ruleInfo['condition'])) {
                $authList[] = $ruleInfo['name'];
            }else{
                //下面的命令是将{score}>300 and {ding}<400 转化成  $user['score']>300 and $user['ding']<400
                $command = preg_replace('/\{(\w*?)\}/', '$userInfo[\'\\1\']', $ruleInfo['condition']);
                //执行上面表达式是  真  假
                $condition=false;
                @(eval('$condition=(' . $command . ');'));
                //如果是真，就是有权限
                if ($condition) {
                    $authList[] = $ruleInfo['name'];
                }
            }
        }
        if(!empty($authList)){S('AUTH_LIST_'.$uid,$authList);}//如果存在权限列表，就记录缓存
        return $authList;
    }
}