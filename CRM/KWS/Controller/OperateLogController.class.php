<?php
namespace KWS\Controller;

/**
 * Class PromoterController
 * 推广人员功能管理（邀约手）
 * @package KWS\Controller
 */
class OperateLogController extends BaseController
{
    private $where = [];

    public function _initialize()
    {
        parent::_initialize();

        if ($this->user['roleid'] == '6' || $this->user['roleid'] == '11') {
            // 不是管理员账号
            $this->where['Kid'] =  $this->user['kid'];
        }
    }
    public function index()
    {
        $map = [];
        $_GET['sf'] && $map = $this->_search();
        $map = array_merge($this->where, $map);

        $options = [
            'map' => $map,
            'order' => 'InsertTime desc',
        ];

        $this->page('OperateLog', $options);
        $users = D('User')->getAllUser();
        $this->assign('users',$users);
        $this->display();
    }

    /**
     * 获取检索条件
     */
    private function _search()
    {
        $map = [];
        $get = I('get.');

        !empty($get['UserId']) && $map['UserId'] = ['in',$get['UserId']];
        $get['IP'] > 0 && $map['IP'] = $get['IP'];
        $get['TypeId']>-1 && $map['TypeId'] = $get['TypeId'];
        $get['contents']  && $map['OperateLog'] =['like','%'.$get['contents'].'%'] ;

        if(empty($get['StartInsertTime'])) {
            $get['StartInsertTime'] = date('Y-m-d', 0);
        } else {
            $get['StartInsertTime'] = date('Y-m-d 00:00:00',  strtotime($get['StartInsertTime']));
        }
        if(empty($get['EndInsertTime'])) {
            $get['EndInsertTime'] = date('Y-m-d 23:59:59');
        } else {
            $get['EndInsertTime'] = date('Y-m-d 23:59:59',  strtotime($get['EndInsertTime']));
        }
        $map['InsertTime'] = ['between', [$get['StartInsertTime'], $get['EndInsertTime']]];


        !empty($where) && $map['_complex'] = $where;
        return $map;
    }

    public function expLog()
    {
        $map = [];
        $_GET['sf'] && $map = $this->_search();
        $map = array_merge($this->where, $map);

        $list = M('OperateLog')->where($map)->order('InsertTime desc')->select();
        $xlsCell = [
            ['UserName','用户'],['Content','操作内容'], ['DateTime','上级部门'], ['IP','IP地址']
        ];

        $User = D('User');
        $data = [];
        foreach($list as $key=>$val){
            $data[$key]['UserName'] = $User->getUser($val['userid'], 'realname');
            $data[$key]['Content'] = $val['operatelog'];
            $data[$key]['DateTime'] = $val['inserttime'];
            $data[$key]['IP'] = $val['ip'];
        }

        exportExcel('操作日志'.'-'.$this->user['userid'], '操作日志', $xlsCell, $data);
    }
}