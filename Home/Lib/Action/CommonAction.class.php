<?php
class CommonAction extends Action {
	
	function _initialize() {
        import('@.ORG.Util.Cookie');
		// 用户权限检查
		if (C ( 'USER_AUTH_ON' ) && !in_array(MODULE_NAME,explode(',',C('NOT_AUTH_MODULE')))) {
            import('@.ORG.Util.RBAC');
			if (! RBAC::AccessDecision ()) {
				//检查认证识别号
				if (! $_SESSION [C ( 'USER_AUTH_KEY' )]) {
					if ($this->isAjax()){ // zhanghuihua@msn.com
						$this->ajaxReturn(true, "", 301);
					} else {
						//跳转到认证网关
						redirect ( PHP_FILE . C ( 'USER_AUTH_GATEWAY' ) );
					}
				}
			}
		}
	}

	//ajax赋值扩展
	protected function ajaxAssign(&$result){
		$result['statusCode']  =  $result['status'];
		$result['navTabId']  =  $_REQUEST['navTabId'];
		$result['message']=$result['info'];
	}


	public function index() {
		//列表过滤器，生成查询Map对象
		$map = $this->_search ();
		if (method_exists ( $this, '_filter' )) {
			$this->_filter ( $map );
		}
		$name=$this->getActionName();
		$model = CM($name);
		if (! empty ( $model )) {
			$this->_list ( $model, $map );
		}
		$this->display ();
		return;
	}
	/**
     +----------------------------------------------------------
	 * 取得操作成功后要返回的URL地址
	 * 默认返回当前模块的默认操作
	 * 可以在action控制器中重载
     +----------------------------------------------------------
	 * @access public
     +----------------------------------------------------------
	 * @return string
     +----------------------------------------------------------
	 * @throws ThinkExecption
     +----------------------------------------------------------
	 */
	function getReturnUrl() {
		return __URL__ . '?' . C ( 'VAR_MODULE' ) . '=' . MODULE_NAME . '&' . C ( 'VAR_ACTION' ) . '=' . C ( 'DEFAULT_ACTION' );
	}

	/**
     +----------------------------------------------------------
	 * 根据表单生成查询条件
	 * 进行列表过滤
     +----------------------------------------------------------
	 * @access protected
     +----------------------------------------------------------
	 * @param string $name 数据对象名称
     +----------------------------------------------------------
	 * @return HashMap
     +----------------------------------------------------------
	 * @throws ThinkExecption
     +----------------------------------------------------------
	 */
	protected function _search($name = '') {
		//生成查询条件
		if (empty ( $name )) {
			$name = $this->getActionName();
		}
        $name = $this->getActionName();
		$model = CM( $name );
		$map = array ();
		foreach ( $model->getDbFields () as $key => $val ) {
			if (isset ( $_REQUEST [$val] ) && $_REQUEST [$val] != '') {
				$map [$val] = $_REQUEST [$val];
			}
		}
		return $map;

	}

	/**
     +----------------------------------------------------------
	 * 根据表单生成查询条件
	 * 进行列表过滤
     +----------------------------------------------------------
	 * @access protected
     +----------------------------------------------------------
	 * @param Model $model 数据对象
	 * @param HashMap $map 过滤条件
	 * @param string $sortBy 排序
	 * @param boolean $asc 是否正序
     +----------------------------------------------------------
	 * @return void
     +----------------------------------------------------------
	 * @throws ThinkExecption
     +----------------------------------------------------------
	 */
	protected function _list($model, $map, $sortBy = '', $asc = true) {
		$pk=$model->getPk ();
		$dbArray=$model->getDbFields ();
		unset($dbArray['_autoinc']);		// _autoinc 表示主键是否自动增长类型
		unset($dbArray['_pk']);			//_pk 表示主键字段名称 
		$order="";
		if(in_array("sort", $dbArray)){
			$order.="sort desc,";
		}
		$order.=$pk." desc";
		//取得满足条件的记录数
		$count = $model->where ( $map )->count ( 'id' );
		if ($count > 0) {
			import ( "@.ORG.Util.Page" );
			//创建分页对象
			if (! empty ( $_REQUEST ['listRows'] )) {
				$listRows = $_REQUEST ['listRows'];
			} else {
				$listRows = '';
			}
			$p = new Page ( $count, $listRows );
			 $pageNum =empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
			//分页查询数据
			 $voList = $model->where($map)->order($order)->limit($pageNum)->page($_REQUEST[C('VAR_PAGE')])->select();
			//分页跳转的时候保证查询条件
			foreach ( $map as $key => $val ) {
				if (! is_array ( $val )) {
					$p->parameter .= "$key=" . urlencode ( $val ) . "&";
				}
			}
			//分页显示
			$page = $p->show ();
			$name=$this->getActionName();
			if($name == 'Rleave') {
				foreach($voList as $k=>$v) {
					$voList[$k] = $v;
					$user = M('student')->where("id = $v[sid]")->find();
					$voList[$k]['account'] = $user['account'];
					$voList[$k]['class_id'] = $user['class_id'];
					$voList[$k]['nickname'] = $user['nickname'];
					$voList[$k]['gender'] = $user['gender'];
					$voList[$k]['tel'] = $user['tel'];
				}
			}
			//模板赋值显示
			$this->assign ( 'list', $voList );
			$this->assign ( 'sort', $sort );
			$this->assign ( 'order', $order );
			$this->assign ( 'sortImg', $sortImg );
			$this->assign ( 'sortType', $sortAlt );
			$this->assign ( "page", $page );
		}
		
		//zhanghuihua@msn.com
		$this->assign ( 'totalCount', $count );
		//lxz
		 $pageNum =empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
		 $this->assign ( 'numPerPage', $pageNum ); //每页显示多少条
		$this->assign ( 'currentPage', !empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1);
			
		Cookie::set ( '_currentUrl_', __SELF__ );
		return;
	}

	function insert() {
		//B('FilterString');
		$name=$this->getActionName();
		$model = CM($name);
		if (false === $model->create ()) {
			$this->error ( $model->getError () );
		}
		//保存当前数据对象
		$list=$model->add ();
		if ($list!==false) { //保存成功
			$this->assign ( 'jumpUrl', Cookie::get ( '_currentUrl_' ) );
			$this->success ('新增成功!');
		} else {
			//失败提示
			$this->error ('新增失败!');
		}
	}

	public function _before_add()
    {
        $provincelist = M('z_province')->getField('id,name');
        $this->assign("provincelist", $provincelist);
    }

    public function _before_edit()
    {
        $provincelist = M('z_province')->getField('id,name');
        $this->assign("provincelist", $provincelist);
    }
    
    // 三级联动下拉菜单  
    public function returnCity()
    {
        $cityId = $_REQUEST['cityId'];
        $str    = '[ ["", "所有城市"]'; //默认选项字符串
        if ($cityId != '') {
            $MisSystemAreasModel = M('z_city');
            $areaslistcity       = $MisSystemAreasModel->where('province_id = ' . $cityId)->getField('id,name');
            foreach ($areaslistcity as $key => $val) {
                $str = $str . ', ["' . $key . '", "' . $val . '"]';
            }
        }
        $str = $str . ' ]';
        echo $str;
    }
    
    public function returnArea()
    {
        $areaId = $_REQUEST['areaId'];
        $str    = '[ ["", "所有区县"]';
        if ($areaId != '') {
            $MisSystemAreasModel = M('z_district');
            $areaslistcity       = $MisSystemAreasModel->where('city_id = ' . $areaId)->getField('id,name');
            foreach ($areaslistcity as $key => $val) {
                $str = $str . ', ["' . $key . '", "' . $val . '"]';
            }
        }
        $str = $str . ' ]';
        echo $str;
    }

    public function choose() {
    	if ($_POST) {
    		$map['account'] = array('like',"%".$_POST['account']."%");
    		$map['nickname'] = array('like',"%".$_POST['nickname']."%");
			$name='Student';
			$model = CM($name);
			if (! empty ( $model )) {
				$this->_list ( $model, $map );
			}
			$this->display ();
			return;
    	}else{
    		$this->display ();
    	}
	}

	public function add() {
		$this->display ();
	}

	public function adds() {
		$this->display ();
	}

	function read() {
		$this->edit ();
	}

	function edit() {
		$name=$this->getActionName();
		$model = M ( $name );
		$id = $_REQUEST [$model->getPk ()];
		$vo = $model->getById ( $id );
		$this->assign ( 'vo', $vo );
		$this->display ();
	}

	function update() {
		//B('FilterString');
		$name=$this->getActionName();
		$model = CM( $name );
		if (false === $model->create ()) {
			$this->error ( $model->getError () );
		}
		// 更新数据
		$list=$model->save ();
		if (false !== $list) {
			//成功提示
			$this->assign ( 'jumpUrl', Cookie::get ( '_currentUrl_' ) );
			$this->success ('编辑成功!');
		} else {
			//错误提示
			$this->error ('编辑失败!');
		}
	}
	/**
     +----------------------------------------------------------
	 * 默认删除操作
     +----------------------------------------------------------
	 * @access public
     +----------------------------------------------------------
	 * @return string
     +----------------------------------------------------------
	 * @throws ThinkExecption
     +----------------------------------------------------------
	 */
	public function delete() {
		//删除指定记录
		$name=$this->getActionName();
		$model = M ($name);
		if (! empty ( $model )) {
			$pk = $model->getPk ();
			$id = $_REQUEST [$pk];
			if (isset ( $id )) {
				$condition = array ($pk => array ('in', explode ( ',', $id ) ) );
				$list=$model->where ( $condition )->setField ( 'status', - 1 );
				if ($list!==false) {
					$this->success ('删除成功！' );
				} else {
					$this->error ('删除失败！');
				}
			} else {
				$this->error ( '非法操作' );
			}
		}
	}
	public function foreverdelete() {
		//删除指定记录
		$name=$this->getActionName();
		$model = CM($name);
		if (! empty ( $model )) {
			$pk = $model->getPk ();
			$id = $_REQUEST [$pk];
			if (isset ( $id )) {
				$condition = array ($pk => array ('in', explode ( ',', $id ) ) );
				if (false !== $model->where ( $condition )->delete ()) {
					//echo $model->getlastsql();
					$this->success ('删除成功！');
				} else {
					$this->error ('删除失败！');
				}
			} else {
				$this->error ( '非法操作' );
			}
		}
		$this->forward ();
	}


	/**
	+----------------------------------------------------------
	* 添加删除操作  (多个删除)
	+----------------------------------------------------------
	* @access public
	+----------------------------------------------------------
	* @return string
	+----------------------------------------------------------
	* @throws ThinkExecption
	+----------------------------------------------------------
	*/

    public function delAll(){
    	$name=$this->getActionName();
		$model = CM ($name);
    	$pk=$model->getPk ();  
		$data[$pk]=array('in', $_POST['ids']);
		$model->where($data)->delete();
		$this->success('批量删除成功');
	}

	public function clear() {
		//删除指定记录
		$name=$this->getActionName();
		$model = CM($name);
		if (! empty ( $model )) {
			if (false !== $model->where ( 'status=-1' )->delete ()) { // zhanghuihua@msn.com change status=1 to status=-1
				$this->assign ( "jumpUrl", $this->getReturnUrl () );
				$this->success ( L ( '_DELETE_SUCCESS_' ) );
			} else {
				$this->error ( L ( '_DELETE_FAIL_' ) );
			}
		}
		$this->forward ();
	}
	/**
     +----------------------------------------------------------
	 * 默认禁用操作
	 *
     +----------------------------------------------------------
	 * @access public
     +----------------------------------------------------------
	 * @return string
     +----------------------------------------------------------
	 * @throws FcsException
     +----------------------------------------------------------
	 */
	public function forbid() {
		$name=$this->getActionName();
		$model = CM($name);
		$pk = $model->getPk ();
		$id = $_REQUEST [$pk];
		$condition = array ($pk => array ('in', $id ) );
		$list=$model->forbid ( $condition );
		if ($list!==false) {
			$this->assign ( "jumpUrl", $this->getReturnUrl () );
			$this->success ( '状态禁用成功' );
		} else {
			$this->error  (  '状态禁用失败！' );
		}
	}
	public function checkPass() {
		$name=$this->getActionName();
		$model = CM($name);
		$pk = $model->getPk ();
		$id = $_GET [$pk];
		$condition = array ($pk => array ('in', $id ) );
		if (false !== $model->checkPass( $condition )) {
			$this->assign ( "jumpUrl", $this->getReturnUrl () );
			$this->success ( '状态批准成功！' );
		} else {
			$this->error  (  '状态批准失败！' );
		}
	}

	public function recycle() {
		$name=$this->getActionName();
		$model = CM($name);
		$pk = $model->getPk ();
		$id = $_GET [$pk];
		$condition = array ($pk => array ('in', $id ) );
		if (false !== $model->recycle ( $condition )) {

			$this->assign ( "jumpUrl", $this->getReturnUrl () );
			$this->success ( '状态还原成功！' );

		} else {
			$this->error   (  '状态还原失败！' );
		}
	}

	public function recycleBin() {
		$map = $this->_search ();
		$map ['status'] = - 1;
		$name=$this->getActionName();
		$model = CM($name);
		if (! empty ( $model )) {
			$this->_list ( $model, $map );
		}
		$this->display ();
	}

	/**
     +----------------------------------------------------------
	 * 默认恢复操作
	 *
     +----------------------------------------------------------
	 * @access public
     +----------------------------------------------------------
	 * @return string
     +----------------------------------------------------------
	 * @throws FcsException
     +----------------------------------------------------------
	 */
	function resume() {
		//恢复指定记录
		$name=$this->getActionName();
		$model = CM($name);
		$pk = $model->getPk ();
		$id = $_GET [$pk];
		$condition = array ($pk => array ('in', $id ) );
		if (false !== $model->resume ( $condition )) {
			$this->assign ( "jumpUrl", $this->getReturnUrl () );
			$this->success ( '状态恢复成功！' );
		} else {
			$this->error ( '状态恢复失败！' );
		}
	}


function saveSort() {
		$seqNoList = $_POST ['seqNoList'];
		if (! empty ( $seqNoList )) {
			//更新数据对象
		$name=$this->getActionName();
		$model = CM($name);
			$col = explode ( ',', $seqNoList );
			//启动事务
			$model->startTrans ();
			foreach ( $col as $val ) {
				$val = explode ( ':', $val );
				$model->id = $val [0];
				$model->sort = $val [1];
				$result = $model->save ();
				if (! $result) {
					break;
				}
			}
			//提交事务
			$model->commit ();
			if ($result!==false) {
				//采用普通方式跳转刷新页面
				$this->success ( '更新成功' );
			} else {
				$this->error ( $model->getError () );
			}
		}
	}
}
?>