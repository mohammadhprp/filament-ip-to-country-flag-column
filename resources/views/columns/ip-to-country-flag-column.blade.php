@php
    $locationPosition = $getLocationPosition();
    $flagPosition = $getFlagPosition();

    $hideIP = $getHideIP();
    $hideFlag = $getHideFlag();
    $hideLocation = $getHideLocation();

    // 直接获取原始数据，避免在视图中调用方法
    $ipList = $getIpList();
 
    // 处理显示内容
    $displayContent = '';
    if (!empty($ipList) && count($ipList) > 0) {
        foreach ($ipList as $ipData) {
            $flag = $ipData['flag'] ?? '';
            $ip = $ipData['ip'] ?? '-';
            $displayContent .= '<div class="flex items-center my-1">';

            if ($flagPosition == 'left' && !$hideFlag) {
                $displayContent .= $flag . '&nbsp;&nbsp;';
            }

            if (!$hideIP) {
                $displayContent .= '<span class="font-mono">' . $ip . '</span>';
            }

            if ($flagPosition == 'right' && !$hideFlag) {
                $displayContent .= '&nbsp;&nbsp;' . $flag;
            }

            $displayContent .= '</div>';
        }
    }
@endphp

<div>{!! $displayContent !!}</div>
