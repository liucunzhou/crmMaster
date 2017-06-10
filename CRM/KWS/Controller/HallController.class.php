<?php
namespace KWS\Controller;

/**
 * Class HallController
 * 婚宴厅管理
 * @package KWS\Controller
 */
class HallController extends BaseController
{
    /**
     * 婚宴厅列表
     */
    public function index()
    {
        $this->page('hall');
        $this->display();
    }

    /**
     * 添加婚宴厅
     */
    public function addHall()
    {
        $data = M('store')->field('StoreId,StoreName')->order('BrandId')->select();
        $this->assign('values', $data);
        layout('Layout/win');
        $this->display();
    }

    /**
     * 编辑婚宴厅
     */
    public function editHall()
    {
        $id = I('id');
        $where = ['HallId' => $id];
        $data = M('hall')->where($where)->find();
        $this->assign('d', $data);

        // 门店
        $data = M('store')->field('StoreId,StoreName')->order('BrandId')->select();
        $this->assign('values', $data);
        layout('Layout/win');
        $this->display();
    }

    /**
     * 执行添加、编辑婚宴厅
     */
    public function doEditHall()
    {
        $data = I('post.');
        $id = I('id');
        if ($id) {
            $result = M('hall')->where(['HallId' => $id])->save($data);
            $arr['layer'] = 'yes';
            //操作记录
            operateLog($this->user['userid'],$this->user['realname'],'修改了婚宴厅信息'.I('HallName'),3);
        } else {
            $result = M('hall')->add($data);
            $arr['redirect'] = $this->referer;
            //操作记录
            operateLog($this->user['userid'],$this->user['realname'],'添加了婚宴厅信息'.I('HallName'),1);
        }

        if ($result) {
            $arr['code'] = '200';
            $arr['msg'] = '信息保存成功';
        } else {
            $arr['code'] = '500';
            $arr['msg'] = '信息保存失败';
        }
        $this->ajaxReturn($arr);
    }

    /**
     * 删除婚宴厅
     */
    public function delHall()
    {
        $result = M('hall')->where(['HallId' => $_GET['id']])->delete();
        if ($result) {
            //操作记录
            operateLog($this->user['userid'],$this->user['realname'],'删除了婚宴厅信息'.I('HallName'),2);
            $arr['code'] = '200';
            $arr['alert'] = '删除婚宴厅成功';
            $arr['reload'] = 'yes';
        } else {
            $arr['code'] = '500';
            $arr['alert'] = '删除婚宴厅失败';
        }

        $this->ajaxReturn($arr);

    }

    /**
     * 显示婚宴厅详情
     */
    public function showHall()
    {
        $this->display();
    }

    /**
     * 导出婚礼堂
     */
    public function expHall()
    {
        // 设置要导出的字段
        $expCellName = [
            ['hallid', '编号'],
            ['storename', '门店名称'],
            ['hallname', '婚宴厅名称'],
            ['maxtables', '最大桌数'],
            ['orderno', '排序'],
        ];

        $expTableData = M('Hall')->order('OrderNo')->select();
        foreach($expTableData as $k=>$v){
            $expTableData[$k]['storename'] = $this->brands[$this->stores[$v['storeid']]['brandid']]['brandname'].$this->stores[$v['storeid']]['storename'];
        }
        $expName = '婚宴厅_' . $this->user['userid'] . '_';
        exportExcel($expName, $expTitle = '', $expCellName, $expTableData);
        operateLog($this->user['userid'], $this->user['realname'], '导出了crm系统婚宴厅参数数据', 4);
    }
}