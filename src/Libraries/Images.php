<?php

namespace Jenson\Core\Libraries;

use Illuminate\Support\Facades\Cache;
use Jenson\Core\Models\Storage;

class Images
{
    /**
     * @param $serverIds
     * @param array $parameters
     * @return array|bool
     *
     *  获取真实图片地址
     * TODO:目前只考虑获取单个图片信息，以后在再扩展
     */
    public static function getRealImageUrl($serverIds, $parameters = [])
    {
        $result = self::PettyBGetImageUrl($serverIds, $parameters);
        return $result;
    }

    /**
     * @param $cmd
     * @param $key
     * @param array $data
     * @return bool
     *
     * 获取缓存图片信息
     */
    private static function PettyRedisImage($cmd, $key, $data = [])
    {
        $result = false;
        if ($cmd == "get") {
            $result = Cache::get($key, false);
        } elseif ($cmd == "set") {
            $result = Cache::forever($key, $data);
        }
        return $result;
    }
    /**
     * @param $serverIds
     * @param array $parameters
     * @return array|bool
     *
     * 新机制的获取图片的方式
     */
    private static function PettyBGetImageUrl($serverIds, $parameters = [])
    {
        $pattern = '@(?i)\b((?:[a-z][\w-]+:(?:/{1,3}|[a-z0-9%])|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|([\s()<>]+|(\([\s()<>]+))*\))+(?:([\s()<>]+|(\([\s()<>]+))*\)|[^\s`!(){};:\'".,<>?«»“”‘’]))@';
        if(preg_match($pattern, $serverIds)){
            return $serverIds;
        }
        if(strpos($serverIds,'-') === false) { # 不包含-
            $storage_url = config('mbcore_mcore.storage_url');
            $retInfo = $storage_url.'/api/avatar/'.$serverIds;
            return $retInfo;
        }
        # return 'serverid:'.$serverid;
        if (is_string($serverIds)) {
            $serverIds = explode(',', $serverIds);
        }
        if (!is_array($serverIds)) {
            return false;
        }
        # 真实Path 获得，如果不再path中则被忽略
        $storageConfig = config('mbcore_storage', []);
//        return $storageConfig;
        $storage_name = [];
        foreach ($storageConfig as $key => $val) {
            if (!isset($val['domains']) || !$val['domains']['https']){
                continue;
            }
            $storage_name[$key] = 'https://' . $val['domains']['https'] . '/';
        }
        $imageInfo = [];
        $pathInfo = [];
        $num = 0;
        # 处理redis中是否存在， PettyRedisImage (13号库)
        foreach ($serverIds as $key => $serverId) {
            $info = self::PettyRedisImage("get", $serverId);
            if ($info) {
                if ($storage_name[$info['storage_name']]) {
                    $pathInfo[$info['storage_name']] = $storage_name[$info['storage_name']];
                } else {
                    continue;
                }
                $num++;
                $imageInfo[$serverId] = $info;
                $imageInfo[$serverId]['dev'] = 'redis';
                unset($serverIds[$key]);
            }
        }
        # 如果缓存存在没有的数据，则查库【必须保证库中 serverId 与 pathKey 对应关系不变】
        if (count($serverIds) > 0) {
            $where = [
                "id" => $serverIds
            ];
            $data = Storage::query()->where($where)->get()->toArray();
            # 处理data
            if ($data) {
                foreach ($data as $item) {
                    if ($storage_name[$item['storage_name']]) {
                        $pathInfo[$item['storage_name']] = $storage_name[$item['storage_name']];
                    } else {
                        continue;
                    }
                    $num++;
                    $imageInfo[$item['id']] = [
                        'storage_name' => $item['storage_name'],
                        'uri' => $item['path'],
                        'width' => $item['width'],
                        'height' => $item['height']
                    ];
                    # 存储在 redis
                    self::PettyRedisImage("set", $item['id'], $imageInfo[$item['id']]);
                    $imageInfo[$item['id']]['dev'] = 'sdb';
                }
            }
        }
        $retInfo = array();
        if ($num == 0) {
            return false;
        }
        $parameters = array_merge(['type' => ''], $parameters);
        $type = $parameters['type'] ? '.' . $parameters['type'] : '';
        if ($num == 1) {
            $keys = array_keys($imageInfo);
            $key = $keys[0];
            $retInfo = [
                'url' => $pathInfo[$imageInfo[$key]['storage_name']] . $imageInfo[$key]['uri'] . $type,
                'width' => $imageInfo[$key]['width'],
                'height' => $imageInfo[$key]['height']
            ];
        } else {
            $temp = [];
            foreach ($imageInfo as $key => $val) {
                $temp[$key] = [
                    'url' => $pathInfo[$val['storage_name']] . $val['uri'] . $type,
                    'width' => $val['width'],
                    'height' => $val['height']
                ];
            }
            $retInfo = $temp;
        }

        return $retInfo;
    }
}