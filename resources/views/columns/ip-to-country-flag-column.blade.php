@php
    $ip = $getIpWithFlag();
    $info = $getIpInfo();
@endphp

<div>
    <span>
        {{ $ip }}
    </span>
    <div @class([
            'text-sm text-gray-500'
        ])>
        {{ $info }}
    </div>
</div>
