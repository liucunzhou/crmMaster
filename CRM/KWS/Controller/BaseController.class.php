<?php
namespace KWS\Controller;

use Think\Controller;

class BaseController extends Controller
{
    // 信息来源页面
    protected $referer = '';

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

    /**
     * 系统初始化
     */
    public function _initialize()
    {
        header("Content-type: text/html; charset=utf-8");
        // session
        $this->user = session("user");

        if (empty($this->user)) {
            $this->redirect('Public/login');
        } else {
            $this->assign('user', $this->user);
        }

        // 获取跳转信息
        isset($_POST['referer']) && $this->referer = $_POST['referer'];

        // 初始化系统菜单导航
        $this->_initNavMenu();

        // 初始化系统配置
        $this->_initSysConfig();

        // 初始化系统信息
        $this->_initUser();

        $userId = $this->user['userid'];
        cookie('user_id', $userId, ['expire'=> 86400 * 24,'prefix'=>'tk_']);
    }

    /**
     * 初始化导航菜单
     */
    private function _initNavMenu()
    {
        $RulesBlack = D('AuthRule')->getRuleBlack();
        $this->assign('RulesBlack', $RulesBlack);

        // 初始化导航及权限
        $nav = C('Nav');
        $menus = C('Menu');
        if ($this->user['roleid'] != '12') {
            foreach ($menus as $key => $val) {
                foreach ($val as $k => $v) {
                    foreach ($v as $m => $n) {
                        if (in_array($n['url'], $RulesBlack)) {
                            unset($menus[$key][$k][$m]);
                        }

                        if (!($this->user['roleid'] == '5' && $this->user['kid'] == 67) && in_array($n['url'] ,['Washing/k7assign','Washing/realReport','Washing/fineReport','Washing/salseReport'] )) {
                            unset($menus[$key][$k][$m]);
                        }
                    }

                    if (count($menus[$key][$k]) == 0) {
                        unset($menus[$key][$k]);
                    }
                }

                if (count($menus[$key]) == 0) {
                    unset($nav[ucfirst($key)]);
                    unset($menus[$key]);
                }
            }
        }
        $this->assign('systemNav', $nav);
        $this->menus = $menus;

        // 右侧导航
        $ctrl = strtolower(CONTROLLER_NAME);
        foreach ($nav as $key => $val) {
            $key = strtolower($key);
            if (in_array($ctrl, $val['ctrl'])) {
                $asideMenu = $menus[$key];
                $this->assign(['controller' => $key, 'asideMenu' => $asideMenu]);
            }
        }

        // 判断用户列表
        $isOnline = S('UserOnline-'.$this->user['userid']);
        $this->assign('isOnline', $isOnline);
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
        $this->stores = D('Store')->getAllStore(

        );
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

        // 获所在部门的所有在职销售
        $this->dsellers = D('User')->getUserOfDepartId($this->user['departid']);
        $this->assign('dsellers', $this->dsellers);

        // 获取所在部门的所有销售
        $this->sellers = D('Department')->getDepartSellers($this->user['departid']);
        $this->assign('sellers', $this->sellers);
    }

    /**
     * 简单
     * @param $model
     * @param $id
     */
    public function delete($model, $id = 0)
    {
        if ($id == 0) $id = I('id');

        $result = M($model)->delete($id);
        if ($result) {
            $return = ['code' => '200', 'msg' => '删除成功', 'reload' => 'yes'];
        } else {
            $return = ['code' => '500', 'msg' => '删除失败'];
        }

        // 写入log日志
        $this->ajaxReturn($return);
    }

    /**
     * 通用编辑模型
     * @param string $modelName 模型名称
     * @param int $id 编辑
     * @param string $msg 错误提示信息
     * @param string 类型
     * @return mixed string
     */
    protected function edit($modelName, $isLayer = false)
    {
        $model = D($modelName);
        $validate = $model->create();
        if (!$validate) {
            $error = $model->getError();
            $keys = array_keys($error);
            $messages = array_values($error);

            $this->ajaxReturn([
                'code' => '400',
                'id' => $keys[0],
                'msg' => $messages[0]
            ]);
        }

        $pk = $model->getPk();
        $id = I($pk);
        if ($id) {
            $actionType = '修改';
            $result = $model->save();
        } else {
            $actionType = '添加';
            $result = $model->add();
        }

        if ($result) {
            // 写日志
            $arr['code'] = '200';
            $arr['msg'] = '保存成功';
            if ($isLayer) {
                $arr['layer'] = 'yes';
            } else {
                $arr['redirect'] = $this->referer;
            }
        } else {
            $arr['code'] = '500';
            $arr['msg'] = '保存失败';
        }

        $this->ajaxReturn($arr);
    }

    /**
     * @param $name
     * @param array $options
     * @return mixed
     */
    protected function page($name, $options = [])
    {
        $model = M($name);
        $pk = $model->getPk();

        $field = isset($options['field']) ? $options['field'] : [];
        $map = isset($options['map']) ? $options['map'] : [];
        $order = isset($options['order']) ? $options['order'] : '';
        $display = isset($options['display']) ? $options['display'] : 16;

        $p = isset($_GET['p']) ? (int)$_GET['p'] : 0;
        if (empty($order)) {
            $list = $model->field($field)->where($map)->order("$pk desc")->page($p, $display)->select();
        } else {
            $list = $model->field($field)->where($map)->order($order)->page($p, $display)->select();
        }

        $log = $model->getLastSql();
        $this->assign('log', $log);

        $this->assign('list', $list);
        $count = $model->where($map)->count();
        $Page = new \Think\Page($count, $display);
        if (isset($options['header'])) {
            $Page->setConfig('header', $options['header']);
        } else {
            $Page->setConfig('header', '<li><a class="num">共%TOTAL_ROW%条记录</a></li>');
        }
        $show = $Page->show();
        $this->assign('page', $show);

        return $list;
    }

    /**
     * @param $name
     * @param array $options
     * @return mixed
     */
    protected function pageByBetween($name, $options = [])
    {
        $model = M($name);
        $pk = $model->getPk();

        $field = isset($options['field']) ? $options['field'] : [];
        $order = isset($options['order']) ? $options['order'] : '';
        $display = isset($options['display']) ? $options['display'] : 10;
        $result = $model->field($field)->order("$pk desc")->find();

        if(empty($_GET['p'])) {
            $end = $result[strtolower($pk)];
        } else {
            $end = $result[strtolower($pk)] - ($_GET['p'] - 1) * $display;
        }

        $start = $end - $display + 1;
        if(isset($options['map'])) {
            $map = array_merge([$pk => ['between', [$start, $end]]], $options['map']);
        } else {
            $map[$pk] = ['between', [$start, $end]];
        }
        $list = $model->field($field)->where($map)->order($order)->select();
        $log = $model->getLastSql();

        $this->assign('log', $log);
        $this->assign('list', $list);
        unset($map[$pk]);
        $count = $model->where($map)->count();
        $model->_sql();
        $Page = new \Think\Page($count, $display);
        if (isset($options['header'])) {
            $Page->setConfig('header', $options['header']);
        } else {
            $Page->setConfig('header', '<li><a class="num">共%TOTAL_ROW%条记录</a></li>');
        }
        $show = $Page->show();
        $this->assign('page', $show);

        return $list;
    }
}
