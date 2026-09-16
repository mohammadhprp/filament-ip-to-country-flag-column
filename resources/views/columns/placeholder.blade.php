<div
    data-ip-country-flag-token="{{ $token }}"
    @if ($whenVisible) data-ip-country-flag-when-visible="1" @endif
    @if ($retryable) data-ip-country-flag-retryable="1" @endif
    data-ip-country-flag-error-label="{{ __('ip-to-country-flag-column::ip-to-country-flag-column.error') }}"
    @if ($retryable) data-ip-country-flag-retry-label="{{ __('ip-to-country-flag-column::ip-to-country-flag-column.retry') }}" @endif
    x-init="$store.ipCountryFlagColumn?.register($el)"
    @if ($retryable) x-on:click="$el.dataset.ipCountryFlagError === '1' && $store.ipCountryFlagColumn?.retry($el)" @endif
>{{ $loadingState }}</div>
