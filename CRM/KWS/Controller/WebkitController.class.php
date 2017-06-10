<?php
namespace KWS\Controller;

use Think\Upload;

class WebkitController extends BaseController
{

    public function images()
    {
        $groups = M('ImageGroup')->where(['user_id'=>$this->user['userid']])->select();
        $this->assign('groups', $groups);

        $gid = I('gid');
        if(!empty($gid)){
            $option['map']['user_id'] = $this->user['userid'];
            $option['map']['group_id'] = $gid;
        } else {
            $option['map']['user_id'] = $this->user['userid'];
        }
        $option['order'] = 'id desc';
        $option['display'] = 8;
        $this->page('image', $option);

        layout(false);
        $this->display();
    }

    public function delImage()
    {
        $id = I('id');
        $Image = M('image');
        $Image->where(['id'=>$id, 'user_id'=>$this->user['userid']])->find();
        $result = unlink('.'.$Image->dpath);
        if($result) {
            $delete = $Image->delete();
        } else {
            $delete = false;
        }

        if($delete){
            $this->ajaxReturn([
                'code'  => '200',
                'msg'   => '删除图片成功'
            ]);
        } else {
            $this->ajaxReturn([
                'code'  => '500',
                'msg'   => '删除图片失败'
            ]);
        }
    }

    public function doUpload()
    {
        $gid = I('gid');
        if(empty($gid)){
            $gid = 0;
            $path = '/data/member/'.$this->user['userid'].'/';
        } else {
            $group = M('ImageGroup')->field('group_name')->find($gid);
            $path = '/data/member/'.$this->user['userid'].'/'.$group['group_name'].'/';
        }

        $config = [
            'maxSize'	=> 3145728,
            'rootPath'	=> './',
            'savePath'	=> $path,
            'saveName'	=> ['uniqid',''],
            'exts'		=> ['jpg', 'gif', 'png', 'jpeg'],
            'autoSub'	=> false, // 关闭自动目录
            // 'subName'	=> ['date','Ymd'],
        ];
        $uploader = new Upload($config);
        $info = $uploader->upload();

        if ($info){
            $savepath = $info['file']['savepath'].$info['file']['savename'];
            // 写入数据库
            $data['user_id']        = $this->user['userid'];
            $data['group_id']       = $gid;
            $data['dpath']		    = $savepath;
            $data['create_time']	= time();
            M('image')->add($data);

            $this->ajaxReturn([
                'code' => '200',
                'result' => $savepath
            ]);
        } else {
            $this->ajaxReturn([
                'code' => '500',
                'result' => $uploader->getError()
            ]);
        }
    }

    public function addGroup()
    {
        $groupName = I('groupName');
        if(empty($groupName)) {
            $this->ajaxReturn([
                'code'  => '400',
                'msg'   => '请输入分组名称'
            ]);
        }

        $path = './data/member/'.$this->user['userid'].'/'.$groupName;
        if(file_exists($path)) {
            $this->ajaxReturn([
                'code'  => '400',
                'msg'   => '分组名称已经存在'
            ]);
        }

        $result = mkdir($path, '0755', true);
        // 写入数据库
        M('ImageGroup')->add([
            'user_id' => $this->user['userid'],
            'group_name' => $groupName,
            'dpath' => substr($path,1),
            'create_time' => time()
        ]);

        if($result) {
            $this->ajaxReturn([
                'code' => '200',
                'msg' => '添加分组成功'
            ]);
        } else {
            $this->ajaxReturn([
                'code'  => '500',
                'msg'   => '添加分组成功'
            ]);
        }
    }
}