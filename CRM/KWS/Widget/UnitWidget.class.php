<?php
namespace KWS\Widget;
use Think\Controller;

class UnitWidget extends Controller{
	// 百度编辑器
	public function ueditor($id,$content=''){
		$this->assign('id',$id);
		if (!empty($content))
			$this->assign('content',htmlspecialchars_decode($content));
		layout(false);
		$this->display("Unit/ueditor");
	}
}
?>