<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Powerless < wzxaini9@gmail.com>
// +----------------------------------------------------------------------
namespace app\index\controller;

use app\user\model\ComplaintModel;
use app\user\model\PlanOrderModel;
use app\user\model\UserModel;
use cmf\controller\UserBaseController;
use think\Db;
use think\Validate;

class PlanController extends UserBaseController
{

    /**
     * 计划单列表
     * @throws
     */
    public function index()
    {
        $plan  = new PlanOrderModel();
        $request = input('request.');
        $page = intval(input('page'));
        $where = ['user_id'=>cmf_get_current_user_id(),'delete_time'=>0];

        //如果传入状态参数 则根据状态查询
        if (!empty($request['status'])){
            $where['status'] = intval($request['status']);
        }

        $limit = 10;
        $list = $plan->where($where)->order("create_time desc")->page($page,$limit)->select();

        $status = [1=>'待处理',2=>'已处理',3=>'已拒绝',4=>'已取消'];

        foreach ($list as $k => $value)
        {

            $complain =ComplaintModel::get(['plan_number'=>$value['number'],'user_id'=>cmf_get_current_user_id()]);

            $value['pouring_time']=date("Y-m-d H:i:s",$value['pouring_time']);
            $value['status_text']= @$status[$value['status']];
            $value['is_read'] = $complain?$complain->is_read:0;
            $list[$k]= $value;
        }

        $data['list'] =  $list;
        $data['page'] =  $page;
        $data['pages']=ceil( $plan->where($where)->count()/$limit );

        if (request()->isAjax()){
            $this->success('success',null,$data);
        }

        $this->assign('data',$data);
        return $this->fetch();
    }


    /**
     * @return mixed
     * @throws
     */
    public function detail(){
        $id = input("id",0,'intval');

        if($id){
            $data = PlanOrderModel::get($id);
            $data['isBack']= 0;
            $data['back']=['content'=>'','reply'=>''];
            $complain =ComplaintModel::get(['plan_number'=>$data['number'],'user_id'=>cmf_get_current_user_id()]);
            if(!empty($complain)){
                $data['isBack'] = 1;
                $data['back']=[
                    'content'=>$complain['content'],
                    'reply'=>$complain['reply'],
                ];

                $complain->is_read=2;
                $complain->save();

            }
            $this->assign('data',$data);
        }

        return $this->fetch();

    }

    public function cancel(){
        $id = input("id",0,'intval');
        if($id)
        {
            $plan = new PlanOrderModel();
            $result = $plan->where(['id'=>$id,'user_id'=>cmf_get_current_user_id(),'status'=>1])->update(["status"=>4]);
            if ($result){
                $this->success("取消成功!");
            }else{
                $this->error("取消失败,此计划单状态以改变或不存在");
            }
        }
        else {
            $this->error("抱歉,参数错误取消失败!");
        }
    }


    public function delete(){
        $id = input("id",0,'intval');
        if($id)
        {
            $plan = new PlanOrderModel();
            $result = $plan->where(['id'=>$id,'user_id'=>cmf_get_current_user_id(),'status'=>['in','3,4']])->delete();
            if ($result){
                $this->success("删除成功!");
            }else{
                $this->error("删除失败,此计划单您无权删除或不存在");
            }
        }
        else {
            $this->error("抱歉,参数错误删除失败!");
        }
    }

}
