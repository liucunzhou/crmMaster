<?php
namespace KWS\Controller;

/**
 * Class CustomerController
 * 客户基础信息管理
 * @package KWS\Controller
 */
class CustomerController extends BaseController
{
    public function _initialize(){
        parent::_initialize();

        // 获取所有门店信息
        $store = D('Store')->getAllStore();
        $this->assign('store', $store);

        // 获取所有来源信息
        $source = D('Source')->getAllSource();
        $this->assign('source', $source);

        // 获取所有状态
        $status = D('StatusType')->getAllStatus();
        $this->assign('status', $status);
    }
    /**
     * 所有客户
     */
    public function index()
    {
        $controller = strtolower(CONTROLLER_NAME);
        $first = array_shift($this->menus[$controller]);
        $this->redirect($first[0]['url']);
        $this->display();
    }

    /**
     * 添加客户
     */
    public function addCustomer()
    {

        $this->display();
    }

    /**
     * 编辑客户
     */
    public function editCustomer()
    {
        $where = array('BrandId' => $_GET['id'] );
        $data =  M('brand') -> where($where)->find();

        $this->assign('list',$data);
        $this->display();
    }

    /**
     * 执行添加
     */
    public function doEditCustomer(){

        $data['BrandName'] = I('name');
        $data['OrderNo'] = I('sort');
        $id = I('id');
        if($id){
            $nid = M('brand')->where(['BrandId' => $id ]) ->save($data);
        }else{
            $nid = M('brand')->add($data);
        }

        if($nid){
            $res['code'] = '200';
            $res['msg'] = '保存成功';
            $res['redirect'] = U('Brand/index');
            $this->ajaxReturn($res);
        }
    }
    /**
     * 删除品牌
     */
    public function delCustomer()
    {

        $rid = M('brand')->where(['BrandId'=>$_GET['id']])->delete();
        if($rid){
            $data['code'] = '200';
            $data['msg'] = '删除成功';
            $data['redirect'] = U('brand/index');
        }else{
            $data['code'] = '400';
            $data['msg'] = '删除失败';
        }

        $this->ajaxReturn($data);
    }

    /**
     * 手动分配
     */
    public function menuAssign()
    {
        $options = [
            'AppointType'=>array('<>',0),
             'order' => 'InsertTime desc'
        ];
        $list = $this->page('assign', $options);

        $customer = M('customer');
        foreach($list as $k=>$v){
            $custers = $customer->where(['CustId'=>$v['custid']])->find();
            $list[$k]['wechat'] = $custers['wechat'];
            $list[$k]['weiboname'] = $custers['weiboname'];
            $list[$k]['qq'] = $custers['qq'];
            $list[$k]['storeid'] = $custers['storeid'];
            $list[$k]['mobile'] = $custers['mobile'];
            $list[$k]['custname'] = $custers['custname'];
            $list[$k]['sourcefrom'] = $custers['sourcefrom'];
            $list[$k]['opeartor'] = $custers['opeartor'];
        }
        $this->assign('list',$list);
        $this->display();
    }

    /**
     * 比较
     */
    public function compare()
    {
        $sql = 'select a.UserId new ,b.UserId old from tk_user as a left join UserInfo as b on a.UserAccount = b.UserAccount where a.UserId!=b.UserId';
        $arr = M('User')->query($sql);

        // echo "<pre>";
        // print_r($arr);

        $Assign = M('assign');
        foreach($arr as $key=>$val){
            $Assign->where(['NowUser'=>$val['old']])->save(['UserId'=>'new']);
            echo $val['old'].'----'.$val['new'];
            echo "<br >";
        }
    }

    public function NowToSalse()
    {
        ignore_user_abort(true);
        set_time_limit(0);

        $Customer = M('Customer');
        $count = $Customer->count();

        $Assign = M('Assign');
        $limit = 1000;
        $User = M('User');

        // ob_clean();
        
        $pages = ceil($count / $limit);
        for( $i = 0; $i< $pages; $i++ ) {
            $customers = $Customer->field('CustNo,Kid,Opeartor')->where(['Kid' => 0])->limit($i * $limit, $limit)->select();
            foreach($customers as $key=>$val) {
                if(empty($val['kid'])) {
                    $data = [];
                    // 设置K及邀约手的部门ID
                    $user = $User->find($val['opeartor']);
                    $data['Kid'] = $user['kid'];
                    $data['PdepartId'] = $user['departid'];

                    // 匹配对应的销售
                    $assign = $Assign->where(['CustNo' => $val['custno']])->find();
                    $seller = $User->find($assign['nowuser']);

                    // 获取销售的部门
                    $data['DepartId'] = $seller['departid'];

                    // 设置销售的Id
                    $data['salseId'] = $assign['nowuser'];
                    $Customer->where(['CustNo' => $val['custno']])->save($data);
                }

                // echo $val['custno'];
                // echo "<br>";
            }
            // ob_flush();
            // flush();
            echo '已经执行了10000条了...<br >';
            sleep(2);
        }
    }

    /**
     * 获取客户信息
     */
    public function ajaxGetCustomers()
    {
        $get = I("get.");
        !empty($get['CustId']) && $map['CustId'] = $get['CustId'];
        !empty($get['Mobile']) && $map['Mobile'] = $get['Mobile'];

        $list = M('customer')->where($map)->select();
        $this->assign('list', $list);

        layout(false);
        $this->display();
    }

    /**
     * 纠正错误的客资
     */
    public function correct()
    {

    }
}