<?php
namespace KWS\Controller;

/**
 * Class SourceController
 * 平台来源管理
 * @package KWS\Controller
 */
class SourceController extends BaseController
{
    /**
     * 平台来源列表
     */
    public function index()
    {
        // 搜索
        $map = $this->_search();

        $this->page('source',[
            'map' => $map,
            'order' => 'Platform,OrderNo',
            'display' => 25
        ]);

        $this->display();
    }

    protected function _search()
    {
        $map = [];
        $get = I('get.');

        isset($get['SourceId']) && $get['SourceId'] > 0 && $map['sourceId'] = $get['SourceId'];
        !empty($get['Platform']) && $map['Platform'] = $get['Platform'];
        isset($get['OrderNo']) && $get['OrderNo'] > 0 && $map['OrderNo'] = $get['OrderNo'];
        !empty($get['SourceName']) && $map['SourceName'] = ['like', $get['SourceName'] . '%'];

        return $map;
    }

    /**
     * 导出来源
     */
    public function expSource()
    {
        $list = M('source')->order('sourceId desc')->select();
        $xlsCell = [
            ['sourceId','编号'],['PlatForm','所属类别'], ['SourceName','来源名称'], ['OrderNo','排序']
        ];
        $data = [];

        foreach($list as $key=>$val){
            $data[$key]['sourceId'] = $val['sourceid'];
            $data[$key]['SourceName'] = $val['sourcename'];
            $data[$key]['PlatForm'] = $val['platform'];
            $data[$key]['OrderNo'] = $val['orderno'];
        }
        operateLog($this->user['userid'], $this->user['realname'], '导出crm系统来源参数数据',4);
        exportExcel('平台来源'.'-'.$this->user['userid'],'来源统计', $xlsCell, $data);

    }

    /**
     * 添加平台来源
     */
    public function addSource()
    {
        layout('Layout/win');
        $this->display();
    }

    /**
     * 编辑平台来源
     */
    public function editSource()
    {
        $d = M('source')->find($_GET['id']);
        $this->assign('d', $d);

        layout('Layout/win');
        $this->display();
    }

    /**
     * 执行编辑、添加
     */
    public function doEditSource()
    {
        $data = I("post.");
        $id = I('id');
        if ($id) {
            $result = M('source')->where(['sourceId' => $id])->save($data);
            //操作记录
            operateLog($this->user['userid'],$this->user['realname'],'修改了crm系统来源参数数据'.I('sourceName'),3);
        } else {
            $result = M('source')->add($data);
            $arr['redirect'] = $this->referer;
            //操作记录
            operateLog($this->user['userid'],$this->user['realname'],'添加了crm系统来源信参数数据'.I('sourceName'),1);
        }

        if ($result) {
            $arr['layer'] = 'yes';
            $arr['code'] = '200';
            $arr['msg'] = '保存成功';
        } else {
            $arr['code'] = '500';
            $arr['msg'] = '保存失败';
        }

        $this->ajaxReturn($arr);
    }

    /**
     * 删除平台来源
     */
    public function delSource()
    {
        $rid = M('source')->where(['SourceId' => $_GET['id']])->delete();
        if ($rid) {
            $data['alert'] = '删除成功';
            $data['reload'] = U('Source/index');
            //操作记录
            $source =D('Source')->getAllSource();
            operateLog($this->user['userid'],$this->user['realname'],'删除了crm系统来源参数数据'.$source[$_GET['id']],2);
        } else {
            $data['alert'] = '删除失败';
        }
        $this->ajaxReturn($data);
    }
}