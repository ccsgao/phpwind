<?php

/**
 * 动态帖子列表相关接口
 *
 * @fileName: ListController.php
 * @author: yuliang<yuliang.lyl@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-16 19:08:17
 * @desc: 
 * */
defined('WEKIT_VERSION') || exit('Forbidden');

class ListController extends PwBaseController {

    private $perpage = 30;

    public function beforeAction($handlerAdapter) {
        parent::beforeAction($handlerAdapter);
//		if (!$this->loginUser->isExists()) $this->showError('VOTE:user.not.login');
    }

    
    
    /**
     * 获取最热帖子列表
     * @access public
     * @return string
      <pre>
      /index.php?m=native&c=list&a=hot&page=1&_json=1
      response: {err:"",data:""}
      </pre>
     */
    public function hotAction() {
        $time = time();
        $num = $this->perpage;//一页显示记录数
        $page = isset($_GET['page']) && intval($_GET['page'])>=1 ? intval($_GET['page']) : 1;//第几页，从请求参数获取
        $threadsHotDao = Wekit::loadDao('native.dao.PwThreadsHotDao');
        $hot_count = $threadsHotDao->getThreadsHotCount($time);
        $nativeThreadsDao = Wekit::loadDao('native.dao.PwNativeThreadsDao');
        $threads = $nativeThreadsDao->fetchHotThreads($hot_count,$time,$page,$num);
        $tids = array_keys($threads);
        $threadsNativeDao = Wekit::loadDao('native.dao.PwThreadsNativeDao');
        $threads_native = $threadsNativeDao->fetchByTids($tids);
        foreach($threads as $k=>$v){
            $threads[$k]['from_type'] = isset($threads_native[$k]['from_type']) ? $threads_native[$k]['from_type'] : 0;
            $threads[$k]['created_address'] = isset($threads_native[$k]['created_address']) ? $threads_native[$k]['created_address'] : '';
        }
        if($res){//筛选帖子的回帖
            /*
            $tids = array();
            $threads = array();
            foreach($res as $v){
                $threads[$v['tid']] = $v;
                $threads[$v['tid']]['replies'] = array();
                $tids[] = $v['tid'];
            }
            $tids = implode(",", $tids);
            $sql = "SELECT `tid`,`rpid`,`pid`,`replies`,`subject`,`content`,`like_count`,`sell_count`,`created_username`,`created_userid`,`created_time` 
                    FROM `${prefix}bbs_posts` 
                    WHERE tid IN ($tids) 
                    ORDER BY `tid` ASC,`pid` ASC; ";
            $replies = $dao->fetchAll($sql);
            foreach($replies as $v){
                $threads[$v['tid']]['replies'][] = $v;
            }
             * 
             */
            $this->setOutput($res,'data');
            $this->showMessage('NATIVE:data.success');
//            $this->showError('USER:verifycode.error');
        }else{//没有最热帖子
//            echo json_encode(array('error'=>'','data'=>array()));
            $this->setOutput(array(),'data');
            $this->showMessage('NATIVE:data.empty');
        }
        exit;
    }

    /**
     * 管理员设置最热帖子
     * @access public
     * @return void
      <pre>
      /index.php?m=native&c=list&a=sethot&_json=1
      post: tid=1&starttime=2011-1-1&endtime=2016-1-1
      response: {err:"",data:""}
      </pre>
     */
    public function setHotAction() {
//        $tid = 7;
//        $threadsHotDao = Wekit::loadDao('native.dao.PwThreadsHotDao');
//            $res = $threadsHotDao->getThreadsHotId($tid);
//            var_dump($res);exit;
        //缺少用户登录状态判断
//          echo "setHotAction<br>";
//          var_dump($_POST);
//          echo $this->getInput('tid');
//          exit;
//          var_dump($GLOBALS['acloud_object_dao']);exit;
        //Sorry, CSRF verification failed(token missing or incorrect),refresh to try again.
        $uid = $this->loginUser->uid;
        $username = $this->loginUser->username;
        $tid = isset($_POST['tid']) ? $_POST['tid'] : 0;
        $starttime = isset($_POST['starttime']) && strtotime($_POST['starttime']) ? strtotime($_POST['starttime']) : 0;
        $endtime = isset($_POST['endtime']) && strtotime($_POST['endtime']) ? strtotime($_POST['endtime']) : strtotime("2038-1-19");
        $msg = '';
        if ($uid && $tid && $starttime && $endtime > $starttime) {
//            $dao = $GLOBALS['acloud_object_dao'];
//            $prefix = $dao->getDB()->getTablePrefix();
            $threadsHotDao = Wekit::loadDao('native.dao.PwThreadsHotDao');
            $res = $threadsHotDao->getThreadsHotId($tid);
            $id = isset($res['id']) ? $res['id'] : 0;
            if($id){//更新数据
                $updatetime = time();
                $data = array('starttime'=>$starttime,'endtime'=>$endtime,'updatetime'=>$updatetime,'id'=>$id);
                $res = $threadsHotDao->updateThreadHot($data);
                $msg = 'Modify';
            }else{//新增数据
                $createtime = time();
                $data = array('tid'=>$tid,'starttime'=>$starttime,'endtime'=>$endtime,'createtime'=>$createtime,'updatetime'=>$createtime,'created_userid'=>$uid,'created_username'=>$username);
                $res = $threadsHotDao->insertThreadHot($data);
                $msg = 'Add';
            }
            if($res){
//                echo "$msg Success !";
                $this->showMessage('NATIVE:sethot.success');
            }else{
//                echo "$sql<br>";
//                echo "$msg Failed";
                $this->showMessage('NATIVE:sethot.failed');
            }
//          $res = $dao->query($sql);
//          $res = $dao->fetchAll($sql);
//          $res = $dao->fetchOne($sql);
//            var_dump($res);
            exit;
        } else {
            $this->showError('NATIVE:args.error');
//            echo "args error";
            exit;
        }
        exit;
//          var_dump($this);exit;
        $this->forwardAction('mobile/test/test', array('arg' => 'arg1'));
    }

    /**
     * 获取我关注的话题相关帖子列表,帖子数不足时展示话题
     * @access public
     * @return string
     * @example
      <pre>
      /index.php?m=native&c=list&a=my&page=1&_json=1
      cookie:usersession
      response: {err:"",data:""}
      </pre>
     */
    public function myAction() {
//        var_dump($this->loginUser->uid);exit;
        if ($this->loginUser->uid < 1) {
//            $this->forwardRedirect(WindUrlHelper::createUrl('u/login/run', array('backurl' => WindUrlHelper::createUrl('tag/index/my'))));
            echo "用户为登录";exit;
        }
        $uid = $this->loginUser->uid;     
        $tagAttentionDao = Wekit::loadDao('tag.dao.PwTagAttentionDao');
        $res = $tagAttentionDao->getByUid($uid,30);//获得用户关注的话题最大30个
        $tag_ids = array();
        foreach($res as $v){
            $tag_ids[] = $v['tag_id'];
        }
//        $tag_ids = implode(',', $tag_ids);
        $time_point = time()-604800;
        //分页计算
        $num = $this->perpage;//一页显示记录数
        $page = isset($_GET['page']) && intval($_GET['page'])>=1 ? intval($_GET['page']) : 1;//第几页，从请求参数获取
        $pos = ($page-1)*$num;
        $nativeTagRelationDao = Wekit::loadDao('native.dao.PwNativeTagRelationDao');
        $res = $nativeTagRelationDao->fetchTids($tag_ids,$time_point,$pos,$num);//根据用户关注的话题tag_id获取文章tid
        $tids = array();
        foreach($res as $v){
            $tids[] = $v['param_id'];
        }
        $nativeThreadsDao = Wekit::loadDao('native.dao.PwNativeThreadsDao');
        $threads = $nativeThreadsDao->fetchMyThreads($tids);
//        $tids = array_keys($threads);
        $threadsNativeDao = Wekit::loadDao('native.dao.PwThreadsNativeDao');
        $threads_native = $threadsNativeDao->fetchByTids($tids);
        foreach($threads as $k=>$v){
            $threads[$k]['from_type'] = isset($threads_native[$k]['from_type']) ? $threads_native[$k]['from_type'] : 0;
            $threads[$k]['created_address'] = isset($threads_native[$k]['created_address']) ? $threads_native[$k]['created_address'] : '';
        }
//        var_dump($threads);exit;
        if(count($res)<=3){
            //关注的话题小于3个，展示更多话题，话题的展示规则有待商量
            $tag_ids = implode(',', $tag_ids);
            $dao = $GLOBALS['acloud_object_dao'];
            $prefix = $dao->getDB()->getTablePrefix();
            $sql = "SELECT `tag_id`,`tag_name` 
                    FROM `${prefix}tag` 
                    WHERE `tag_id` NOT IN ($tag_ids) LIMIT 10;";
            $res_tag = $dao->fetchAll($sql);
//            var_dump($res,$res_tag);
            $this->setOutput(array('threads'=>$res,'tags'=>$res_tag),'data');
            $this->showMessage('NATIVE:my.threads');
        }else{
//            var_dump($res);
            $this->setOutput($res,'data');
            $this->showMessage('NATIVE:my.threads');
        }
//        var_dump();exit;
        exit;
    }
    

    /**
     * 获取最新帖子列表
     * @access public
     * @return string
     * @example
      <pre>
      /index.php?m=native&c=list&a=new&page=1&_json=1
      response: {err:"",data:""}
      </pre>
     */
    public function newAction() {
        //分页计算
        $num = $this->perpage;//一页显示记录数
        $page = isset($_GET['page']) && intval($_GET['page'])>=1 ? intval($_GET['page']) : 1;//第几页，从请求参数获取
        $pos = ($page-1)*$num;
        $nativeThreadsDao = Wekit::loadDao('native.dao.PwNativeThreadsDao');
        $threads = $nativeThreadsDao->fetchNewThreads($pos,$num);
        $tids = array_keys($threads);
        $threadsNativeDao = Wekit::loadDao('native.dao.PwThreadsNativeDao');
        $threads_native = $threadsNativeDao->fetchByTids($tids);
        foreach($threads as $k=>$v){
            $threads[$k]['from_type'] = isset($threads_native[$k]['from_type']) ? $threads_native[$k]['from_type'] : 0;
            $threads[$k]['created_address'] = isset($threads_native[$k]['created_address']) ? $threads_native[$k]['created_address'] : '';
        }
//        var_dump($threads);exit;
        if($res){
            /* 列表页不展示回帖信息
            $tids = array();
            $threads = array();
            foreach($res as $v){
                $threads[$v['tid']] = $v;
                $threads[$v['tid']]['replies'] = array();
                $tids[] = $v['tid'];
            }
            $tids = implode(",", $tids);
            $sql = "SELECT `tid`,`rpid`,`pid`,`replies`,`subject`,`content`,`like_count`,`sell_count`,`created_username`,`created_userid`,`created_time` 
                    FROM `${prefix}bbs_posts` 
                    WHERE tid IN ($tids) 
                    ORDER BY `tid` ASC,`pid` ASC; ";
            $replies = $dao->fetchAll($sql);
            foreach($replies as $v){
                $threads[$v['tid']]['replies'][] = $v;
            }
             * 
             */
//            var_dump($threads);exit;
//            echo json_encode(array('error'=>'','data'=>$threads));
//            var_dump($res);exit;
            $this->setOutput($res,'data');
            $this->showMessage('NATIVE:new.threads');
        }else{//没有数据
//            echo json_encode(array('error'=>'','data'=>array()));
            $this->setOutput(array(),'data');
            $this->showMessage('NATIVE:data.empty');
        }
        exit;
//        var_dump($res);exit;
    }

    /**
     * 获取同城帖子列表
     * @access public
     * @return string
     * @example
      <pre>
      /index.php?m=native&c=list&a=city&city=aaa&page=1&_json=1
      cookie:usersession
      response: {err:"",data:""}
      </pre>
     */
    public function cityAction() {
        //分页计算
        $num = $this->perpage;//一页显示记录数
        $page = isset($_GET['page']) && intval($_GET['page'])>=1 ? intval($_GET['page']) : 1;//第几页，从请求参数获取
        $city = isset($_GET['city']) ? $_GET['city'] : '';
        $pos = ($page-1)*$num;
        $nativeThreadsDao = Wekit::loadDao('native.dao.PwNativeThreadsDao');
        $threads = $nativeThreadsDao->fetchCityThreads($city,$pos,$num);
//        var_dump($threads);exit;
        $tids = array_keys($threads);
        $threadsNativeDao = Wekit::loadDao('native.dao.PwThreadsNativeDao');
        $threads_native = $threadsNativeDao->fetchByTids($tids);
        foreach($threads as $k=>$v){
            $threads[$k]['from_type'] = isset($threads_native[$k]['from_type']) ? $threads_native[$k]['from_type'] : 0;
            $threads[$k]['created_address'] = isset($threads_native[$k]['created_address']) ? $threads_native[$k]['created_address'] : '';
        }
        var_dump($threads);exit;
    }
    
    
    /**
     * 每天定时计算帖子的权重值作业，触发条件待定
     * @access public
     * @return string
     * @example
      <pre>
      /index.php?m=mobile&c=list&a=weight
      post:
      response: {err:"",data:""}
      </pre>
     */
    public function weightAction(){
//        echo ini_get('date.timezone')."<br>";
//        echo date_default_timezone_get()."<br>";
//        echo time()."<br>";
//        echo date("Y-m-d H:i:s",time())."<br>";
//        date_default_timezone_set('Asia/Shanghai');
//        ini_set('date.timezone','Asia/Shanghai');
//        echo ini_get('date.timezone')."<br>";
//        echo date_default_timezone_get()."<br>";
//        echo time()."<br>";
//        echo date("Y-m-d H:i:s",time())."<br>";
//        exit;
//        echo date("Y-m-d H:i:s",1419302691);exit;
//        var_dump(101/20,ceil(101/20));exit;
        set_time_limit(0);
        ignore_user_abort(true);
        date_default_timezone_set('Asia/Shanghai');
        $threadsWeightDao = Wekit::loadDao('native.dao.PwThreadsWeightDao');
        $res = $threadsWeightDao->getMaxCreateTime();
        $last_create_time = isset($res['last_create_time']) && $res['last_create_time'] ? $res['last_create_time'] : 0;
        $current_hour = intval(date("H"));
//        if($current_hour >= 1 && $current_hour<= 8 && time() > ($last_create_time+36000)){//作业的触发距离最后一条记录生成要大于10小时
        if(1){//测试
            //执行权重计算逻辑
            $threadsWeightDao->deleteAll();//删除旧表数据
            $current_time = time();
            $stop_time = $current_time-604800;//获取7天前的数据进行计算
//            $stop_time = $current_time-1604800;//获取更早前的数据
//            echo $stop_time;exit;
            $nativeThreadsDao = Wekit::loadDao('native.dao.PwNativeThreadsDao');
            //获取指定时间前的帖子条数
            $res = $nativeThreadsDao->getCountByTime($stop_time);
            $threads_count = intval($res['count']);
            $threads_count = $threads_count>1000 ? 1000 : $threads_count;//权重计算默认只取1000条
            $num = 50;//一次处理的记录数
            $pages = ceil($threads_count/$num);        
            for($i=1;$i<=$pages;$i++){
//                $starttime_test = time();
                $page = $i;
                $start = ($page-1)*$num;//开始位置偏移
                $res = $nativeThreadsDao->fetchThreadsData($stop_time,$start,$num);
                $weight_values = array();
                if($res){
                    foreach($res as $k=>$v){
                        $weight = $v['like_count']*2+
                                  $v['replies']*4+
                                  $v['reply_like_count']+
                                  floor(($current_time-$v['lastpost_time'])/86400)*-4+
                                  floor(($current_time-$v['created_time'])/86400)*-20;
//                        $res[$k]['weight'] = $weight;
                        $weight_values[] = "({$v['tid']},$weight,$current_time,1)";
                    }
                    $weight_values = implode(',', $weight_values);
                    //将权重计算结果插入权重表
                    $threadsWeightDao->insertValues($weight_values);
                }
//                $endtime_test = time();
//                echo ($endtime_test-$starttime_test)."<br>";
            }
            //对管理员设置的热帖去重处理
            $threadsHotDao = Wekit::loadDao('native.dao.PwThreadsHotDao');
            $res = $threadsHotDao->fetchTidsByTime($current_time);//查找管理员设置的热帖用来去重，最大取500条
            $tids = array();
            foreach($res as $value){
                $tids[] = $value['tid'];
            }
            $tids = implode(',', $tids);
            $threadsWeightDao->deleteByTids($tids);
            //只保留权重最高的500条记录
            $res = $threadsWeightDao->getWeightByPos(499);
            /*
            $sql = "SELECT `weight` FROM `${prefix}bbs_threads_weight` ORDER BY `weight` DESC LIMIT 499,1";
            $res = $dao->fetchOne($sql);
             */        
            if($res){
                $weight = $res['weight'];
                $res = $threadsWeightDao->deleteByWeight($weight);
                /*
                $sql = "DELETE FROM `${prefix}bbs_threads_weight` WHERE `weight`<$weight;";
                $dao->query($sql);
                 */
            }
            echo "SCRIPT EXCUTE FINISHED";
        }else{
            echo "SCRIPT IS EXCUTED TODAY";exit;
        }
        exit;
//        var_dump($res,$last_create_time);exit;
    }
    
    
   

}