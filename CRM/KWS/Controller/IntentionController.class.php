<?php
namespace KWS\Controller;

/**
 * Class IntentionController
 * 意向状态管理
 * @package KWS\Controller
 */
class IntentionController extends BaseController
{
    /**
     * 所有意向状态
     */
    public function index()
    {
        $options = [
            'order' => 'display desc,OrderNo asc',
            'display' => 20
        ];

        $list = $this->page('StatusType', $options);
        $this->display();
    }

    /**
     * 添加意向状态
     */
    public function addIntention()
    {
        layout('Layout/win');
        $this->display();
    }

    /**
     * 编辑意向状态
     */
    public function editIntention()
    {
        $where = array('SID' => $_GET['id']);
        $data = M('StatusType')->where($where)->find();
        $this->assign('d', $data);

        layout('Layout/win');
        $this->display();
    }

    /**
     * 添加、编辑意向状态信息，入库
     */
    public function doEditIntention()
    {
        $id = I('id');
        $data = I("post.");
        if ($id) {
            $result = M('StatusType')->where(['SID' => $id])->save($data);
            //操作记录
            operateLog($this->user['userid'],$this->user['realname'],'修改了意向信息'.I('Sname'),3);
        } else {
            $result = M('StatusType')->add($data);
            //操作记录
            operateLog($this->user['userid'],$this->user['realname'],'添加了意向信息'.I('Sname'),1);
        }

        if ($result) {
            $arr['code'] = '200';
            $arr['msg'] = '保存成功';
            $arr['layer'] = 'yes';
        } else {
            $arr['code'] = '500';
            $arr['msg'] = '保存失败';
        }

        $this->ajaxReturn($arr);
    }

    /**
     * 删除意向状态
     */
    public function delIntention()
    {
        $id = $_GET['id'];
        if ($id) {
            $rid = M('StatusType')->where(['SID' => $id])->delete();
        }

        if ($rid) {
            //操作记录
            operateLog($this->user['userid'],$this->user['realname'],'删除了意向信息'.I('Sname'),2);
            $res['alert'] = '删除成功';
            $res['redirect'] = U('Intention/index');
        } else {
            $res['alert'] = '删除失败';
            $res['redirect'] = U('Intention/index');
        }

        $this->ajaxReturn($res);
    }

    /**
     * 显示意向状态详情
     */
    public function showIntention()
    {
        $this->display();
    }

    /**
     *  导出意向状态
     */
    public function expIntention()
    {
        $list = M('StatusType')->order('SID desc')->select();
        $xlsCell = [
            ['SID','编号'],['Sname','状态名称'],  ['Display','有效/无效'],['OrderNo','排序']
        ];
        $data = [];
        foreach($list as $key=>$val){
            $data[$key]['SID'] = $val['sid'];
            $data[$key]['Sname'] = $val['sname'];
            $data[$key]['Display'] = $val['display']?'有效':'无效';
            $data[$key]['OrderNo'] = $val['orderno'];
        }

        exportExcel('意向状态'.'-'.$this->user['userid'],'意向状态统计', $xlsCell, $data);
        operateLog($this->user['userid'], $this->user['realname'], '导出了crm系统客户意向状态参数数据', 4);
    }
}