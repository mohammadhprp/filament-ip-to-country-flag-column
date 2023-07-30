@php
    $ipAddress = $getIP();
    $countryFlag = $getFlag();
    $location = $getLocation();

    $locationPosition = $getLocationPosition();
    $flagPosition = $getFlagPosition();

    $hideIP = $getHideIP();
    $hideFlag = $getHideFlag();
    $hideLocation = $getHideLocation();
@endphp

<div>
    @if($locationPosition == 'above' && !$hideLocation)
        <div class="text-sm text-gray-500">
            {{ $location }}
        </div>
    @endif

    <span>
        @if($flagPosition == 'left' && !$hideFlag)
            {{ $countryFlag }}
        @endif

        @unless($hideIP)
            {{ $ipAddress }}
        @endunless

        @if($flagPosition == 'right' && !$hideFlag)
            {{ $countryFlag }}
        @endif
    </span>

    @if($locationPosition == 'below' && !$hideLocation)
        <div class="text-sm text-gray-500">
            {{ $location }}
        </div>
    @endif
</div>
