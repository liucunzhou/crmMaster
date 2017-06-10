<?php
namespace KWS\Controller;

/**
 * Class BrandController、
 * 品牌管理
 * @package KWS\Controller
 */
class BrandController extends BaseController
{
    public function _initialize()
    {
        parent::_initialize();

        $Business = [
            '摄影事业部' => [
                '婚纱摄影',
                '儿童摄影'
            ],
            '母婴事业部' => [
                '月子中心'
            ],
            '礼服事业部' => [
                '男装',
                '女装',
                '女鞋',
            ],
            '婚宴事业部' => [
                '婚礼堂'
            ]
        ];
        $this->assign('Business', $Business);

        // 集团
        $compays = [
            1 => '婚宴事业部',
            2 => '嘉丰集团',
            3 => '嘉艺集团',
            4 => '嘉铄集团',
            5 => '礼服集团',
            6 => '特区1集团',
            7 => '特区2集团',
            8 => '翰林宝宝',
        ];
        $this->assign('companys', $compays);
        
    }

    /**
     * 所有品牌
     */
    public function index()
    {
        // 搜索
        $map = $this->_search();
        
        $this->page('brand', [
            'map' => $map,
            'order' => 'OrderNo,Department,Business',
            'display' => 20
        ]);

        $this->display();
    }
    
    /**
     * 获取查询条件
     */
    private function _search()
    {
        $map = [];
        $get = I('get.');

        isset($get['BrandId']) && $get['BrandId'] > 0 && $map['BrandId'] = $get['BrandId'];
        !empty($get['Department']) && $map['Department'] = $get['Department'];
        !empty($get['Business']) && $map['Business'] = $get['Business'];
        isset($get['OrderNo']) && $get['OrderNo'] > 0 && $map['OrderNo'] = $get['OrderNo'];
        !empty($get['BrandName']) && $map['BrandName'] = ['like', $get['BrandName'] . '%'];

        return $map;
    }

    /**
     * 导出品牌
     */
    public function expBrand()
    {
        // 设置要导出的字段
        $expCellName = [
            ['brandid', '品牌编号'],
            ['department', '事业部'],
            ['business', '业务'],
            ['brandname', '品牌名称'],
            ['orderno', '排序'],
        ];

        $expTableData = M('Brand')->order('OrderNo')->select();

        $expName = '品牌管理_' . $this->user['userid'] . '_';
        exportExcel($expName, $expTitle = '', $expCellName, $expTableData);
        operateLog($this->user['userid'], $this->user['realname'], '导出了crm系统品牌参数数据', 4);
    }

    /**
     * 添加品牌
     */
    public function addBrand()
    {
        layout('Layout/win');
        $this->display();
    }

    /**
     * 编辑品牌
     */
    public function editBrand()
    {
        $where = ['BrandId' => $_GET['id']];
        $data = M('brand')->where($where)->find();
        $this->assign('d', $data);

        layout('Layout/win');
        $this->display();
    }

    /**
     * 执行添加
     */
    public function doEditBrand()
    {
        $data = I("post.");
        $id = I('id');
        if ($id) {
            $arr = explode(":::", $data['parent']);
            $data['Department'] = $arr[0];
            $data['Business'] = $arr[1];
            $result = M('brand')->where(['BrandId' => $id])->save($data);
            //操作记录
            operateLog($this->user['userid'], $this->user['realname'], '修改了品牌信息' . I('BrandName'), 3);
        } else {
            $result = M('brand')->add($data);
            //操作记录
            operateLog($this->user['userid'], $this->user['realname'], '添加了品牌信息' . I('BrandName'), 1);
        }

        if ($result) {
            // 更新品牌缓存
            D('Brand')->getAllBrand(true);

            $arr['code'] = '200';
            $arr['msg'] = '保存品牌信息成功';
            $arr['layer'] = 'yes';
        } else {
            $arr['code'] = '400';
            $arr['msg'] = '保存品牌信息失败';
        }
        $this->ajaxReturn($arr);
    }

    /**
     * 删除品牌
     */
    public function delBrand()
    {
        $result = M('brand')->where(['BrandId' => $_GET['id']])->delete();
        if ($result) {
            //操作记录
            operateLog($this->user['userid'], $this->user['realname'], '删除了品牌信息' . $_GET['id'], 2);
            $data['code'] = '200';
            $data['msg'] = '删除成功';
            $data['redirect'] = U('brand/index');

        } else {

            $data['code'] = '400';
            $data['msg'] = '删除失败';
        }

        $this->ajaxReturn($data);
    }

}