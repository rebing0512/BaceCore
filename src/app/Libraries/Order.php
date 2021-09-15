<?php
namespace MBCore\MCore\Libraries;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Carbon\Carbon;
use MBCore\MPaySDK\Controllers\OrderController;


class Order
{

    /**
     * @return string
     *
     * PMCore Url
     */
    public static function pmCoreUrl(){
        $url = config('mbcore_mcore.pmcore_url','https://pmcore.mbcore.com/');
        return $url;
    }

    /**
     * @param $orderNumber
     * @return mixed|string
     * @throws \Exception
     *
     * get System Order Query
     */
    public static function getSystemOrderQuery($orderNumber){
        $url = static::pmCoreUrl().'mpay/api/order/query';
        $client = new Client();
        $result = $client->post($url,[
            'form_params'=>[
                'hash'=>$orderNumber,
            ]
        ]);
        try{
            $data = $result->getBody()->getContents();
            if(stripos($data,"Error")!== false){

                throw new \Exception('接口请求内容错误');
            }
            $data = json_decode($data,1);
        }Catch(\Exception $e){
            $data = [];
        }

        return $data;
    }
    /**
     * @param $hash
     * @return mixed|string
     * @throws \Exception
     *
     * Finish System Order
     */
    public static function finishSystemOrder($hash){
        $url = static::pmCoreUrl().'mpay/api/order/finished';
        $client = new Client();
        $form_params = [
            'hash'=>$hash
        ];

        $result = $client->post($url,[
            'form_params'=>$form_params
        ]);

        try{
            $data = $result->getBody()->getContents();
//            return json_decode($data,1);
            if(stripos($data,"Error")!== false){

                throw new \Exception('接口请求内容错误');
            }
            $data = json_decode($data,1);
        }Catch(\Exception $e){
//            $data = [];
            throw new \Exception($e->getMessage());
        }


        return $data;
    }
    /**
     * @param $fn
     * @param $params
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     *
     * 订单SDK
     */
    public static function SDK($fn,$params){

        $sdk = new OrderController();

        $result = $sdk->$fn($params);

        return $result;
    }

    /**
     * @param $system_order
     * @param null $type
     * @param null $from
     * @return string
     *
     * 支付中心URL
     */
    public static function payUrl($system_order,$type = null,$from = null){
        if(!$from){
            $payUrl  = static::pmCoreUrl().'mpay/order/pay?id='.$system_order.'&type='.$type;
        }else{
            $payUrl  = static::pmCoreUrl().'mpay/order/pay?id='.$system_order.'&type='.$type.'&from='.$from;
        }

        return $payUrl;
    }
}