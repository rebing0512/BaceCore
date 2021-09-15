<?php

namespace MBCore\MCore\Libraries;

class Setting
{
    public static function handleBaseSetting($base, $extra = null)
    {
        $extra = array_filter($extra, function ($key) {
            return in_array($key, [
                "add", "reduce", "change"
            ]);
        }, ARRAY_FILTER_USE_KEY);
        if (!$extra) return $base;
        if ($extra["add"]) {
            foreach ($extra["add"] as $item) {
                if (!$item["position"]) {
                    if (is_int($item["key"])) array_splice($base, $item["key"], 0, [$item["content"]]);
                    if (is_string($item["key"]) && !isset($base[$item["key"]])) $base[$item["key"]] = $item["content"];
                } else {
                    $position = explode(".", $item["position"]);
                    $deep = count($position);
                    $check = $base;

                    foreach ($position as $key => $value) {
                        if (isset($check[$value])) {
                            $check = $check[$value];
                            if ($deep <= $key + 1) {
                                if (is_string($item["key"]) && !isset($check[$item["key"]]) && is_array($check)) {
                                    $code_str = str_replace(".", "\"][\"", '$base["'.$item["position"].'"]["'.$item["key"].'"]=$item["content"];');
                                    eval($code_str);
                                }
                                if (is_int($item["key"])){
                                    $code_str = str_replace(".", "\"][\"", 'array_splice($base["'.$item["position"].'"], $item["key"], 0, [$item["content"]]);');
                                    eval($code_str);
                                }
                            }
                        } else {
                            break 2;
                        }
                    }
                }
            }
        }
        if ($extra["reduce"]) {
            foreach ($extra["reduce"] as $item) {
                if (!$item["position"]) {
                    unset($base[$item["key"]]);
                } else {
                    $position = explode(".", $item["position"]);
                    $deep = count($position);
                    $check = $base;

                    foreach ($position as $key => $value) {
                        if (isset($check[$value])) {
                            $check = $check[$value];
                            if ($deep <= $key + 1) {
                                $code_str = str_replace(".", "\"][\"", 'unset($base["'.$item["position"].'"]["'.$item["key"].'"]);');
                                eval($code_str);
                                if (is_numeric($item["key"])) {
                                    $format_code_str = str_replace(".", "\"][\"", '$base["'.$item["position"].'"]=array_values($base["'.$item["position"].'"]);');
                                    eval($format_code_str);
                                }
                            }
                        } else {
                            break 2;
                        }
                    }
                }
            }
        }
        if ($extra["change"]) {
            foreach ($extra["change"] as $item) {
                if (!$item["position"]) {
                    if ($base[$item["key"]]) $base[$item["key"]] = $item["content"];
                } else {
                    $position = explode(".", $item["position"]);
                    $deep = count($position);
                    $check = $base;

                    foreach ($position as $key => $value) {
                        if (isset($check[$value])) {
                            $check = $check[$value];
                            if ($deep <= $key + 1) {
                                if (isset($check[$item["key"]])) {
                                    $code_str = str_replace(".", "\"][\"", '$base["'.$item["position"].'"]["'.$item["key"].'"]=$item["content"];');
                                    eval($code_str);
                                }
                            }
                        } else {
                            break 2;
                        }
                    }
                }
            }
        }
        return $base;
    }
}