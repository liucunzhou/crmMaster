<?php
namespace KWS\Model;

use Think\Model;

class DepartmentModel extends Model
{
    protected $patchValidate = true;
    protected $user = [];
    protected $users = [];

    protected $_validate = [
        ['BrandId', 'require', '请选择品牌'],
        ['DepartName', 'require', '部门名称不能为空'],
    ];

    protected $_auto = [
        ['BrandId', 'setBrandId', 3, 'callback'],
        ['dpath', 'setDpath', 3, 'callback'],
        ['sorts', 'setSorts', 2, 'callback'],
    ];

    public function _initialize()
    {
        $this->user = session('user');
        $this->users = D('User')->getAllUser();
    }

    /**
     * 设置排序
     * @return string
     */
    public function setSorts()
    {
        $ParentId = I('ParentId');
        $DepartId = I('DepartId');
        $OrderNo = I('OrderNo');
        empty($OrderNo) && $OrderNo = 0;
        empty($DepartId) && $DepartId = $this->getLastInsID();

        if ($ParentId == '0') {
            $dpath = '0-' . $OrderNo;
        } else {
            $department = M('department')->field('sorts')->where(['DepartId' => $ParentId])->find();
            $arr = explode('-', $department['sorts']);
            $dpath = implode('-', $arr) . '-' . $OrderNo;
        }

        return $dpath;
    }

    /**
     * 获取指定部门的ID
     * @param $id 部门ID
     * @param string $field 获取指定部门的字段信息
     * @return mixed
     */
    public function getDepartment($id, $field = '')
    {
        $department = S('Department-'.$id);
        if(empty($deparment)) {
            $fields = 'DepartId,DepartName,ParentId,Role,Stores,dpath';
            $department = $this->field($fields)->where(['DepartId' => $id])->find();
            S('Department-'.$id, $department);
        }

        if (!empty($field)) {
            return $department[$field];
        } else {
            return $department;
        }
    }

    /**
     * 获取大区销售
     */
    public function getKSellers()
    {
        $sellers = [];
        foreach ($this->users as $key => $val) {
            // 当前k 或者 超级管理员
            if ($val['kid'] == $this->user['kid'] && in_array($val['roleid'] ,[1,3,4]) && $val['islock'] == '0') {
                    $sellers[] = $val;
            } else if ($this->user['roleid'] == '12' && in_array($val['roleid'] ,[1,3,4]) && $val['islock'] == '0') {
                    $sellers[] = $val;
            }
        }

        return $sellers;
    }

   /* public function getDepartSellers()
    {
        $sellers = [];
        foreach ($this->users as $key => $val) {
            // 当前k 或者 超级管理员
            if ($val['departid'] == $this->user['departid'] && $val['roleid'] == '1' && $val['islock'] == '0') {
                $sellers[] = $val;
            } else if ($this->user['roleid'] == '12' && $val['roleid'] == '1' && $val['islock'] == '0') {
                $sellers[] = $val;
            }
        }

        return $sellers;
    }*/
    public function getDepartSellers()
    {
        $sellers = [];
        $departments = $this->getAllDepartment();
        $departids = $this->getTree($departments,$this->getDepartment($this->user['departid']));
        $departids = array_keys($departids);
        foreach ($this->users as $key => $val) {
            // 当前k 或者 超级管理员
            if (in_array($val['departid'] ,$departids) && $val['roleid'] == '1' && $val['islock'] == '0') {
                $sellers[] = $val;
            } else if ($this->user['roleid'] == '12' && $val['roleid'] == '1' && $val['islock'] == '0') {
                $sellers[] = $val;
            }
        }
        return $sellers;
    }
    /**
     * 获取大区推广
     */
    public function getPromoters()
    {
        $promoters = [];
        foreach ($this->users as $key => $val) {
            // 当前k 或者 超级管理员
            if ($val['kid'] == $this->user['kid'] && in_array($val['roleid'],[2, 9, 10, 11]) && $val['islock'] == '0') {
                $promoters[] = $val;
            } else if ($this->user['roleid'] == '12' && $val['islock'] == '0') {
                $promoters[] = $val;
            }
        }

        return $promoters;
    }

    /**
     * 获取孩子节点
     * @param $pid
     * @return array
     */
    public function getChildren($pid)
    {
        $departments = $this->getAllDepartment();

        $children = [];
        foreach ($departments as $key => $val) {
            if ($val['parentid'] == $pid) {
                $children[] = $val;
            }
        }

        return $children;
    }

    /**
     * @param bool|false $update 更新缓存
     * @return mixed
     */
    public function getAllDepartment($update = false)
    {
        //$departments = S('Departments');
        $departments = [];
        if (empty($departments) || $update) {
            $fields = 'DepartId,DepartName,ParentId,Role,Stores,dpath';
            $map['OrderNo'] = ['neq',4];
            $departments = $this->where($map)->order('OrderNo,dpath')->getField($fields);
            S('Departments', $departments);
        }

        // print_r($departments);

        // 超级管理员角色: 获取所有部门
        if ($this->user['roleid'] == '12') {
            return $departments;
        }

        // 获取当前角色的部门
        $department = $departments[$this->user['departid']];

        // 获取当前角色的所有子部门
        $tree = $this->getTree($departments, $department);
        $tree = [$department['departid'] => $department] + $tree;

        return $tree;
    }

    /**
     * 获取所有子节点
     * @param $departments
     * @param $department
     * @return array
     */
    public function getTree($departments, $department)
    {
        $tree = [];
        
        $tree[$department['departid']] = $department;
        foreach ($departments as $key => $val) {
            if (strpos($val['dpath'], $department['dpath'] . $department['departid'] . '-') === 0) {
                $tree[$val['departid']] = $val;
            }
        }

        return $tree;
    }

    /**
     * 设置品牌ID
     * @return array
     */
    protected function setBrandId()
    {
        $BrandIds = I("BrandId");

        return explode(':', $BrandIds);
    }

    /**
     * 设置井深
     * @return string
     */
    protected function setDpath()
    {
        $ParentId = I('ParentId');
        if ($ParentId == '0') {
            $dpath = '0-';
        } else {
            $department = M('department')->field('dpath')->where(['DepartId' => $ParentId])->find();
            $arr = explode('-', $department['dpath']);
            $dpath = implode('-', $arr) . $ParentId . '-';
        }

        return $dpath;
    }

    /**
     *
     * @param $kid
     * @param $roles
     * @return mixed
     */
    public function getSellDepartment($kid, $roles='')
    {
        $kid&&$map['dpath'] = ['like','0-1-'.$kid.'-%'];
        if(empty($roles)){
            $map['Role'] = ['in', ['manager','seller','seller-manager']];
        }else{
            $map['Role'] = ['in', $roles];
        }
        $sells = $this->where($map)->order('OrderNo')->select();
        return $sells;
    }

    /**
     * 通过门店表获取销售部门所属门店
     *
     */
    public function getDepartmentStores()
    {
        //$get = I('get.');
        $departId = I('department');
        $stores = D('Store')->getAllStore();

        // 获取门店关系
        //$Deparment = D('Department');
        $departments = $this->getAllDepartment();
        $department = $this->getDepartment($departId);
        $treeDepartment = $this->getTree($departments, $department);

        if (empty($treeDepartment)) {
            $DepartStore = [];
            foreach ($stores as $key => $val) {
                $SalesDepartment = explode(',', $val['sellids']);
                if (in_array($departId, $SalesDepartment)) {
                    $DepartStore[$key] = $val;
                }
            }
        } else {
            $departIds = array_keys($treeDepartment);
            $DepartStore = [];
            foreach ($stores as $key => $val) {
                $SalesDepartment = explode(',', $val['sellids']);
                foreach ($departIds as $v) {
                    if (in_array($v, $SalesDepartment)) {
                        $DepartStore[$key] = $val;
                    }
                }
            }
        }
        return $DepartStore;
        //$this->assign('store', $DepartStore);
    }
}