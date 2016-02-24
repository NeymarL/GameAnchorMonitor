<?php

function getAnchors()
{
    // 读取主播列表
    $json_file = fopen("anchor.json", "r") or die("Unable to open file!");
    $json = fread($json_file, filesize("anchor.json"));
    fclose($json_file);
    $all_anchors = json_decode($json, true);
    // var_dump($all_anchors);
    return $all_anchors;
}

function getMemCache()
{
    // 连接memcache
    $memcache = new Memcache;
    $memcache->connect("127.0.0.1", 12000);
    return $memcache;
}

function update($memcache, $time)
{
    $all_anchors = getAnchors();
    foreach ($all_anchors as $type_anchors) {
        // var_dump($type_anchors["type"]);
        foreach ($type_anchors["anchors"] as $anchor) {
            $html = '';
            if ($anchor["platform"] == "斗鱼TV" || $anchor["platform"] == "虎牙直播" ||
                $anchor["platform"] == '龙珠直播') {
                $html = httpRequest($anchor["url"]);
            }
            //echo $anchor["platform"] . "\t";
            if ($anchor["platform"] == "熊猫TV" || $anchor["platform"] == "战旗TV" ||
                $anchor["platform"] == "全民直播" || $anchor["platform"] == "火猫TV") {
                # 正在直播的条件 : 正在直播列表中存在该房间
                $url = '';
                if ($anchor["platform"] == "熊猫TV") {
                    $url = 'http://www.panda.tv/cate/';
                } else if ($anchor["platform"] == "战旗TV") {
                    $url = 'http://www.zhanqi.tv/games/';
                } else if ($anchor["platform"] == "全民直播") {
                    $url = 'http://www.quanmin.tv/game/';
                } else if ($anchor["platform"] == "火猫TV") {
                    $url = 'http://www.huomaotv.cn/channel/';
                }
                if ($type_anchors["type"] == "DOTA2") {
                    $url .= 'dota2';
                } else if ($type_anchors["type"] == "LOL") {
                    $url .= 'lol';
                } else if ($type_anchors["type"] == "炉石") {
                    if ($anchor["platform"] == "熊猫TV" || $anchor["platform"] == "全民直播") {
                        $url .= 'hearthstone';
                    } else if ($anchor["platform"] == "战旗TV") {
                        $url .= 'how';
                    }
                }
                $domain = explode('/', $anchor["url"]);
                $room_name = $domain[3];
                $subject = httpRequest($url);
                if (preg_match('/' . $room_name . '/', $subject)) {
                    $memcache->set($anchor["name"], 1, 0, $time); // 1 代表在直播
                    // echo $anchor["name"] . "\t正在直播\n";
                } else {
                    $memcache->set($anchor["name"], 0, 0, $time); // 0 代表不在直播
                    // echo $anchor["name"] . "\t不在直播\n";
                }

            } else if ($anchor["platform"] == "斗鱼TV") {
                # 正在直播的条件 : 出现 '举报该房间'
                $pattern = '#举报该房间#';
                if (preg_match($pattern, $html)) {
                    $memcache->set($anchor["name"], 1, 0, $time); // 1 代表在直播
                    // echo $anchor["name"] . "\t正在直播\n";
                } else {
                    $memcache->set($anchor["name"], 0, 0, $time); // 0 代表不在直播
                    // echo $anchor["name"] . "\t不在直播\n";
                }

            } else if ($anchor["platform"] == "虎牙直播") {
                # 正在直播的条件 : 出现 '个观众' 或者不出现 '历史直播时长'
                $pattern = '#个观众#';
                if (preg_match($pattern, $html)) {
                    $memcache->set($anchor["name"], 1, 0, $time); // 1 代表在直播
                    // echo $anchor["name"] . "\t正在直播\n";
                } else {
                    $memcache->set($anchor["name"], 0, 0, $time); // 0 代表不在直播
                    // echo $anchor["name"] . "\t不在直播\n";
                }

            } else if ($anchor["platform"] == "龙珠直播") {
                # 正在直播的条件 : 不出现 '主播现在不在，看下其它直播和视频吧'
                $pattern = '#<div id="live-title" class="live-title">主播现在不在，看下其它直播和视频吧</div>#';
                if (preg_match($pattern, $html) == 0) {
                    $memcache->set($anchor["name"], 1, 0, $time); // 1 代表在直播
                    // echo $anchor["name"] . "\t正在直播\n";
                } else {
                    $memcache->set($anchor["name"], 0, 0, $time); // 0 代表不在直播
                    // echo $anchor["name"] . "\t不在直播\n";
                }
            }
        }
    }
}

function display($all_anchors, $memcache, $game_type)
{
    $file = fopen("header.html", "r") or die("Unable to open file!");
    echo fread($file, filesize("header.html"));
    if ($game_type == "hs") {
        $game_type = "炉石";
        echo '<a class="control-item ui-link" href="dota2.php"><strong>DOTA2</strong></a>
            <a class="control-item ui-link" href="lol.php"><strong>LOL</strong></a>
            <a class="control-item ui-link active" href="hs.php"><strong>炉石</strong> </a>';
    } else if ($game_type == "LOL") {
        echo '<a class="control-item ui-link" href="dota2.php"><strong>DOTA2</strong></a>
            <a class="control-item ui-link active" href="lol.php"><strong>LOL</strong></a>
            <a class="control-item ui-link" href="hs.php"><strong>炉石</strong> </a>';
    } else if ($game_type == "DOTA2") {
        echo '<a class="control-item ui-link active" href="dota2.php"><strong>DOTA2</strong></a>
            <a class="control-item ui-link" href="lol.php"><strong>LOL</strong></a>
            <a class="control-item ui-link" href="hs.php"><strong>炉石</strong> </a>';
    }
    $file = fopen("middle.html", "r") or die("Unable to open file!");
    echo fread($file, filesize("middle.html"));
    $online = '';
    $offlne = '';
    foreach ($all_anchors as $type_anchors) {
        if ($type_anchors["type"] == $game_type) {
            foreach ($type_anchors["anchors"] as $anchor) {
                $state = $memcache->get($anchor["name"]);
                if (empty($state) || $state == 0) {
                    $offlne .= '<div><span style="width:125px;float:left;">' . $anchor["name"] . '</span><span style="color:#666666;width:40px;"><strong>离线</strong></span><span style="margin-left:25px;">
                        ' . $anchor["url"] . '</span></div>';
                } else {
                    $online .= '<div><span style="width:145px;float:left;"><strong>' . $anchor["name"] . '</strong></span>
                            <span style="color:red;width:40px;"><strong>直播</strong></span>
                            <span style="margin-left:25px;">' . $anchor["url"] . '</span></div>';
                }
            }
        }
    }
    echo $online . "</div>";
    echo '<div id="divHSOfflineContent" class="divOfflineContent" style="display: block;">';
    echo $offlne . "</div>";
    echo "</div></body></html>";
}

function httpRequest($url)
{
    //$header[0] = "Host: www.panda.tv";
    $ch = curl_init(); // 初始化
    curl_setopt($ch, CURLOPT_URL, $url); // 需要获取的URL地址
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET"); // get的方式
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 不进行任何验证
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 将curl_exec()获取的信息以文件流的形式返回，而不是直接输出
    $output = curl_exec($ch); // 发出请求
    curl_close($ch); // 关闭cURL
    return $output;
}
