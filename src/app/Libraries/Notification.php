<?php
/**
 * Created by PhpStorm.
 * User: wangjian
 * Date: 2019/03/04 0007
 * Time: 13:40
 */
namespace Jenson\Core\Libraries;

use GuzzleHttp\Client;

class Notification
{

    /**
     * @return \Illuminate\Config\Repository|mixed
     *
     * pmServiceUrl
     */
    public static function pmServiceUrl(){
        $url = config('mbcore_mcore.pmservice_url','http://mbdev-service.pettyb.com/');
        return $url;
    }


    /**
     * @param $form_params
     * @return mixed|string
     * @throws \Exception
     *
     * 消息通知
     */
    public static function messageNotification($form_params){
        $pm_service_url = static::pmServiceUrl();
        $url = $pm_service_url.'api/push/PushByAlias';
        $client = new Client(['verify'=>false]);
        $result = $client->post($url,[
            'form_params'=>$form_params
        ]);
        try{
            $data = $result->getBody()->getContents();
            if(stripos($data,"Error")!== false){
                throw new \Exception('接口请求内容错误');
            }
            $data = json_decode($data,1);
        }Catch(\Exception $e){
            throw new \Exception($e->getMessage());
        }
        return $data;
    }

    /**
     * @param $params
     * @return array
     *
     * Message Form Params
     */
    public static function form_params($params){
        $openid = $params['open_id'];
        $group_id  = $params['group_id'];
        $template_id = $params['template_id'];
        $target = $params['target'];
        $alert = $params['alert'];
        $message = $params['message'];
        $url = $params['url'];
        $title = $params['title'];
        $back_title = $params['back_title']?:'';
        $back_uri = $params['back_uri']?:'';
        $storage_message = $params['storage_message']?:'';
        if($openid) {
            # 已绑定微信
            $form_params = [
                'group_id'=>$group_id,
                'channel' => ['app','weixin'],
                'template_id' => $template_id,
                'app'=>[
                    'target'=>$target,
                    'alert' => $alert,
                    'message' => $message,
                    'data'=>[
                        "title"=>$title,
                        "uri"=>$url,
                        "back_title"=>$back_title,
                        "back_uri"=>$back_uri
                    ],
                ],
                'weixin' => [
                    'touser' => $openid,
                    'url' => $url,
                    'data' => $params['data_weixin']
                ],
                'message'=>$storage_message
            ];
        }else{
            $form_params = [
                'group_id'=>$group_id,
                'channel' => ['app'],
                'template_id' => $template_id,
                'app'=>[
                    'target'=>$target,
                    'alert' => $alert,
                    'message' => $message,
                    'data'=>[
                        "title"=>$title,
                        "uri"=>$url,
                        "back_title"=>$back_title,
                        "back_uri"=>$back_uri
                    ],
                ],
                'message'=>$storage_message
            ];
        }
        return $form_params;
    }

    /**
     * @param $form_params
     * @return mixed|string
     * @throws \Exception
     *
     * get_app_bind_relation
     */
    public static function get_app_bind_relation($form_params){
        $get_app_bind_relation_url = Helper::pmCoreUrl().'apiCenter/get_app_bind_relation';
        $client = new Client(['verify'=>false]);
        $result = $client->post($get_app_bind_relation_url,[
            'form_params'=>$form_params
        ]);
        try{
            $data = $result->getBody()->getContents();
            if(stripos($data,"Error")!== false){
                throw new \Exception('接口请求内容错误');
            }
            $data = json_decode($data,1);
        }Catch(\Exception $e){
            throw new \Exception($e->getMessage());
        }
        return $data;
    }


}