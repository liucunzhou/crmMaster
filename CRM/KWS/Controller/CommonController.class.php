<?php
/**
 * Created by PhpStorm.
 * User: LFC
 * Date: 2017/3/7
 * Time: 18:28
 */

namespace KWS\Controller;


use Think\Controller;

class CommonController extends Controller
{
    // 所在大区
    protected $kid = 0;

    // 部门列表
    protected $departments = [];

    // 用户当前部门
    protected $department = [];

    // 用户信息
    protected $user = [];

    // 平台来源
    protected $sources = [];

    // 业务
    protected $business = [];

    // 品牌
    protected $brands = [];

    // 门店列表
    protected $stores = [];

    // 所在k所负责的门店列表
    protected $kstores = [];

    // 所在部门所负责的门店列表
    protected $dstores = [];

    // 客户有效性状态
    protected $status = [];

    // 订单类型
    protected $customerTypes = [];

    // 支付类型
    protected $orderTypes = [];

    // 支付平台
    protected $orderPays = [];

    // 菜单
    protected $menus = [];

    // 所在大区销售列表
    protected $ksellers = [];

    // 所在部门销售列表
    protected $sellers = [];

    // 组装数据
    protected $data = [];

    /**
     * 系统初始化
     */
    public function _initialize()
    {
        $request = file_get_contents('php://input');
        $post = json_decode($request, true);
        foreach($post['entry'] as $key=>$value) {
            // 检测手机
            if(isMobile($value)) {
                $this->data['Mobile'] = $value;
            }

            // 检测门店
            if(stripos($value, '__store__') === 0) {
                $storeArr = explode("__", $value);
                $this->data['StoreId'] = $storeArr[2];
            }

            // 检测来源
            if(stripos($value, '__platform__') === 0) {
                $storeArr = explode("__", $value);
                $this->data['SourceFrom'] = $storeArr[2];
            }

            // 检测邀约手
            if(stripos($value, '__promoter__') === 0) {
                $storeArr = explode("__", $value);
                $this->data['Opeartor'] = $storeArr[2];
            }
        }

        empty($this->data) && $this->data = I("post.");

        // 检测无效数据
        if(empty($this->data['Mobile']) || empty($this->data['StoreId']) || empty($this->data['SourceFrom']) || empty($this->data['Opeartor'])) {
            $this->ajaxReturn([
                'code' => '400',
                'msg'   => 'Data is invalid'
            ]);
        }

        // 获取城市
        if(empty($post['entry']['info_remote_ip'])) {
            $ip = get_client_ip();
        } else {
            $ip = $post['entry']['info_remote_ip'];
        }
        $url = "http://restapi.amap.com/v3/ip?ip={$ip}&output=json&key=cfb78a03eefa2b6cc9a401cd25599e8c";
        $amap = file_get_contents($url);
        $position = json_decode($amap, true);
        $this->data['city'] = $position['city'];

        // 初始化个人信息
        $this->user = M("user")->find($this->data['Opeartor']);
        session("user", $this->user);

        // 初始化系统配置
        $this->_initSysConfig();

        // 初始化系统信息
        $this->_initUser();
    }


    /**
     * 初始化系统需要的配置文件
     * 1、业务列表
     * 2、品牌列表(有效的)
     * 3、门店列表(有效的)
     * 4、部门列表(有效的)
     * 5、来源列表(有效的)
     * 6、状态列表(有效的)
     */
    private function _initSysConfig()
    {
        // 初始化品牌列表
        $this->brands = D('Brand')->getAllBrand();
        $this->assign('brands', $this->brands);

        // 初始化门店列表
        $this->stores = D('Store')->getAllStore();
        $this->assign('stores', $this->stores);


        // 初始化来源列表
        $this->sources = D('Source')->getAllSource();
        $this->assign('sources', $this->sources);

        // 来源分组
        $gsources = [];
        foreach ($this->sources as $key => $val) {
            $Platforms[] = $val['platform'];
            $gsources[$val['platform']][] = $val;
        }
        $this->assign('gsources', $gsources);

        // 来源平台
        $Platforms = array_unique($Platforms);
        $this->assign('Platforms', $Platforms);

        // 初始化客户状态列表
        $this->status = D('StatusType')->getAllStatus();
        $this->assign('status', $this->status);

        // 初始化部门列表
        $this->departments = D('Department')->getAllDepartment();

        $this->assign('departments', $this->departments);
    }

    /**
     * 初始化用户配置相关信息
     * 1、用户所在K
     * 2、用户所在部门信息
     * 3、用户的角色
     * 4、如果用户是销售，他负责的门店是哪些？
     * 5、判断用户是否离线
     */
    private function _initUser()
    {
        /**
         * 大区 -> 部门
         *     -> 销售部门 -> 销售小组
         *     -> 推广部门 -> 推广小组
         */

        // 获取大区信息
        $this->kid = D('Department')->getDepartment($this->user['kid']);

        // 获取部门信息
        $this->department = D('Department')->getDepartment($this->user['departid']);

        // 所在k所负责的所有门店
        $this->kstores = D('Store')->getKStores($this->user['kid']);
        $this->assign('kstores', $this->kstores);

        // 所在部门所负责的所有门店
        $this->dstores = D('Store')->getDStores($this->user['departid']);
        $this->assign('dstores', $this->dstores);

        // 获所在大区的所有销售
        $this->ksellers = D('Department')->getKSellers($this->user['kid']);
        $this->assign('ksellers', $this->ksellers);

        // 获取所在部门的所有销售
        $this->sellers = D('Department')->getDepartSellers($this->user['departid']);
        $this->assign('sellers', $this->sellers);
    }
}