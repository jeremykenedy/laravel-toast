@php
    use Jeremykenedy\LaravelToast\Support\ToastSettings;

    $enabled = ToastSettings::enabled();
    $data = $enabled ? ToastSettings::payload() : null;
    $bsType = ['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info'];
    $iconPath = [
        'success' => 'M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z',
        'error'   => 'M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293 5.354 4.646z',
        'warning' => 'M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z',
        'info'    => 'M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z',
    ];
@endphp
{!! \Jeremykenedy\LaravelToast\Support\ToastAnimations::styleTag('bootstrap5') !!}
<div class="w-100" style="max-width:64rem;">
    <div class="mb-4">
        <h2 class="h4 mb-1">{{ __('toast::toast.settings.title') }}</h2>
        <p class="text-body-secondary mb-0">{{ __('toast::toast.settings.description') }}</p>
    </div>

    @if(!$enabled)
        <div class="alert alert-warning" role="alert">{{ __('toast::toast.settings.disabled') }}</div>
    @else
        @unless($data['ready'])
            <div class="alert alert-warning" role="alert">{{ __('toast::toast.settings.not_ready') }}</div>
        @endunless

        <form method="POST" action="{{ $data['urls']['update'] }}" data-toast-settings>
            @csrf
            @method('PUT')
            <style data-toast-preview-style></style>

            <section class="card mb-4" aria-labelledby="toast-colors-heading">
                <div class="card-body">
                    <h3 id="toast-colors-heading" class="h6">{{ __('toast::toast.settings.colors') }}</h3>
                    <p class="text-body-secondary small">{{ __('toast::toast.settings.colors_help') }}</p>

                    @foreach($data['types'] as $type)
                        <div class="border rounded p-3 mb-3">
                            <h4 class="h6 mb-3">{{ __('toast::toast.settings.types.'.$type) }}</h4>
                            <div class="row g-4">
                                @foreach(['light', 'dark'] as $mode)
                                    <div class="col-lg-6">
                                        <p class="text-uppercase text-body-secondary fw-medium small mb-2">{{ __('toast::toast.settings.'.$mode) }}</p>
                                        @foreach($data['parts'] as $part)
                                            @php $value = $data['colors'][$type][$mode][$part] ?? null; @endphp
                                            <div class="d-flex align-items-center gap-2 mb-2" data-toast-color-row data-default-label="{{ __('toast::toast.settings.default') }}" @if($value) data-custom="true" @endif>
                                                <span class="small flex-shrink-0" style="width:8rem;">{{ __('toast::toast.settings.parts.'.$part) }}</span>
                                                <input type="color" data-toast-color-input value="{{ $value ?? '#888888' }}" aria-label="{{ __('toast::toast.settings.types.'.$type) }} {{ __('toast::toast.settings.'.$mode) }} {{ __('toast::toast.settings.parts.'.$part) }}" class="form-control form-control-color">
                                                <input type="hidden" name="colors[{{ $type }}][{{ $mode }}][{{ $part }}]" value="{{ $value }}" data-toast-color-value="{{ $type }}.{{ $mode }}.{{ $part }}">
                                                <span data-toast-color-label class="small font-monospace text-body-secondary" style="width:5rem;">{{ $value ?? __('toast::toast.settings.default') }}</span>
                                                <button type="button" data-toast-color-clear class="btn btn-link btn-sm p-0">{{ __('toast::toast.settings.clear') }}</button>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="card mb-4" aria-labelledby="toast-preview-heading">
                <div class="card-body">
                    <h3 id="toast-preview-heading" class="h6 mb-3">{{ __('toast::toast.settings.preview') }}</h3>
                    <div class="row g-4">
                        @foreach(['light', 'dark'] as $mode)
                            <div class="col-lg-6">
                                <div data-toast-preview="{{ $mode }}" data-bs-theme="{{ $mode }}" class="rounded p-3 {{ $mode === 'dark' ? 'dark bg-dark text-light' : 'bg-light' }}">
                                    <p class="text-uppercase fw-medium small mb-3 opacity-75">{{ __('toast::toast.settings.'.$mode) }}</p>
                                    @foreach($data['types'] as $type)
                                        <div data-laravel-toast="preview" data-css-framework="bootstrap5" data-toast-type="{{ $type }}" class="toast show w-100 mb-2 overflow-hidden rounded-3 shadow text-bg-{{ $bsType[$type] }}">
                                            <div data-toast-part="track" style="height:3px;background:rgba(255,255,255,0.3);"><div data-toast-part="bar" style="height:100%;width:66%;background:rgba(255,255,255,0.7);"></div></div>
                                            <div class="d-flex">
                                                <div class="toast-body d-flex align-items-center gap-2">
                                                    <span data-toast-part="icon" class="flex-shrink-0 d-inline-flex"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="{{ $iconPath[$type] }}"/></svg></span>
                                                    <div class="text-break">
                                                        <strong class="d-block">{{ __('toast::toast.settings.types.'.$type) }}</strong>
                                                        {{ __('toast::toast.settings.sample_message') }}
                                                    </div>
                                                </div>
                                                <span data-toast-part="close" class="btn-close btn-close-white me-2 m-auto flex-shrink-0" aria-hidden="true"></span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="card mb-4" aria-labelledby="toast-options-heading">
                <div class="card-body">
                    <h3 id="toast-options-heading" class="h6 mb-3">{{ __('toast::toast.settings.options') }}</h3>
                    <div class="row g-3">
                        @foreach($data['fields'] as $key => $field)
                            @php $current = $data['options'][$key]; $label = __('toast::toast.settings.fields.'.$key); @endphp
                            <div class="col-sm-6 col-lg-4">
                                @if($field['type'] === 'checkbox')
                                    <div class="form-check">
                                        <input type="hidden" name="options[{{ $key }}]" value="0">
                                        <input type="checkbox" id="toast-opt-{{ $key }}" name="options[{{ $key }}]" value="1" @checked($current) @if(in_array($key, ['show_icons', 'show_close', 'show_progress', 'show_border'])) data-toast-toggle="{{ ['show_icons' => 'icon', 'show_close' => 'close', 'show_progress' => 'track', 'show_border' => 'border'][$key] }}" @endif class="form-check-input">
                                        <label class="form-check-label" for="toast-opt-{{ $key }}">{{ $label }}</label>
                                    </div>
                                @else
                                    <label class="form-label" for="toast-opt-{{ $key }}">{{ $label }}</label>
                                    @if($field['type'] === 'select')
                                        <select id="toast-opt-{{ $key }}" name="options[{{ $key }}]" class="form-select">
                                            @foreach($field['options'] as $option)
                                                <option value="{{ $option }}" @selected($current === $option)>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <input type="number" id="toast-opt-{{ $key }}" name="options[{{ $key }}]" value="{{ $current }}" min="{{ $field['min'] }}" max="{{ $field['max'] }}" step="{{ $field['step'] }}" @if($key === 'opacity') data-toast-opacity @endif class="form-control">
                                    @endif
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <div class="d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary" @disabled(!$data['ready'])>{{ __('toast::toast.settings.save') }}</button>
                <button type="submit" form="toast-settings-reset" data-toast-confirm="{{ __('toast::toast.settings.reset_confirm') }}" class="btn btn-outline-secondary" @disabled(!$data['ready'])>{{ __('toast::toast.settings.reset') }}</button>
            </div>
        </form>

        <form id="toast-settings-reset" method="POST" action="{{ $data['urls']['destroy'] }}" class="d-none">
            @csrf
            @method('DELETE')
        </form>

        @include('toast-shared::settings-script')
    @endif
</div>
