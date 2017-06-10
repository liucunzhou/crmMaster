<?php
namespace KWS\Widget;

use Think\Controller;

class WebkitWidget extends Controller
{
	public function _initialize()
	{
		layout(false);
	}

	public function ueditor($key, $content='', $more=''){

		$this->assign(['key' => $key, 'content' => htmlspecialchars_decode($content), 'more'=>$more]);
		return $this->fetch("Webkit/ueditor");
	}

	public function image($field, $value='')
	{
		$this->assign(['field' => $field, 'value'=>$value]);
		$this->display("Webkit/image");
	}

	public function area()
	{
		$province =D('areas')->where(['parent_id' => 1])->select();
		$this->assign('province',$province);
		$this->display('Webkit/area');
	}
}
?>