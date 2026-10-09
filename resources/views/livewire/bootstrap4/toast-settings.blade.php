@php
    $modes = ['light', 'dark'];
    $bsType = ['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info'];
    $iconPath = [
        'success' => 'M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z',
        'error'   => 'M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293 5.354 4.646z',
        'warning' => 'M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z',
        'info'    => 'M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z',
    ];
@endphp
<div class="w-100" style="max-width:64rem;">
    {!! \Jeremykenedy\LaravelToast\Support\ToastAnimations::styleTag('bootstrap4') !!}
    <div class="mb-4">
        <h2 class="h4 mb-1">{{ __('toast::toast.settings.title') }}</h2>
        <p class="text-muted mb-0">{{ __('toast::toast.settings.description') }}</p>
    </div>

    @if(!$enabled)
        <div class="alert alert-warning" role="alert">{{ __('toast::toast.settings.disabled') }}</div>
    @else
        @unless($ready)
            <div class="alert alert-warning" role="alert">{{ __('toast::toast.settings.not_ready') }}</div>
        @endunless

        <form wire:submit="save">
            <style>{!! $previewCss !!}</style>

            <section class="card mb-4" aria-labelledby="toast-colors-heading">
                <div class="card-body">
                    <h3 id="toast-colors-heading" class="h6">{{ __('toast::toast.settings.colors') }}</h3>
                    <p class="text-muted small">{{ __('toast::toast.settings.colors_help') }}</p>

                    @foreach($types as $type)
                        <div class="border rounded p-3 mb-3">
                            <h4 class="h6 mb-3">{{ __('toast::toast.settings.types.'.$type) }}</h4>
                            <div class="row">
                                @foreach($modes as $mode)
                                    <div class="col-lg-6 mb-3">
                                        <p class="text-uppercase text-muted font-weight-bold small mb-2">{{ __('toast::toast.settings.'.$mode) }}</p>
                                        @foreach($parts as $part)
                                            @php $value = $colors[$type][$mode][$part] ?? ''; @endphp
                                            <div class="d-flex align-items-center mb-2">
                                                <span class="small flex-shrink-0" style="width:8rem;">{{ __('toast::toast.settings.parts.'.$part) }}</span>
                                                <input type="color" wire:model.live.debounce.150ms="colors.{{ $type }}.{{ $mode }}.{{ $part }}" aria-label="{{ __('toast::toast.settings.types.'.$type) }} {{ __('toast::toast.settings.'.$mode) }} {{ __('toast::toast.settings.parts.'.$part) }}" class="form-control p-1 mr-2" style="width:3rem;height:2rem;">
                                                <span class="small text-monospace text-muted mr-2" style="width:5rem;">{{ $value ?: __('toast::toast.settings.default') }}</span>
                                                <button type="button" wire:click="clearColor('{{ $type }}', '{{ $mode }}', '{{ $part }}')" class="btn btn-link btn-sm p-0">{{ __('toast::toast.settings.clear') }}</button>
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
                    <div class="row">
                        @foreach($modes as $mode)
                            <div class="col-lg-6 mb-3">
                                <div data-toast-preview="{{ $mode }}" class="rounded p-3 {{ $mode === 'dark' ? 'dark bg-dark text-light' : 'bg-light' }}">
                                    <p class="text-uppercase font-weight-bold small mb-3">{{ __('toast::toast.settings.'.$mode) }}</p>
                                    @foreach($types as $type)
                                        <div data-laravel-toast="preview" data-css-framework="bootstrap4" data-toast-type="{{ $type }}" class="alert alert-{{ $bsType[$type] }} mb-2 shadow-sm" role="presentation">
                                            <div data-toast-part="track" style="height:3px;background:rgba(0,0,0,0.1);margin:-.75rem -1.25rem .5rem;"><div data-toast-part="bar" style="height:100%;width:66%;background:rgba(0,0,0,0.25);"></div></div>
                                            <div class="d-flex align-items-start">
                                                <span data-toast-part="icon" class="mr-2 flex-shrink-0"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="{{ $iconPath[$type] }}"/></svg></span>
                                                <div class="flex-grow-1" style="min-width:0;">
                                                    <strong>{{ __('toast::toast.settings.types.'.$type) }}</strong><br>
                                                    {{ __('toast::toast.settings.sample_message') }}
                                                </div>
                                                <span data-toast-part="close" class="ml-2" aria-hidden="true">&times;</span>
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
                    <div class="form-row">
                        @foreach($fields as $key => $field)
                            @php $current = $options[$key]; $label = __('toast::toast.settings.fields.'.$key); @endphp
                            <div class="form-group col-sm-6 col-lg-4">
                                @if($field['type'] === 'checkbox')
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" id="toast-opt-{{ $key }}" wire:model.live="options.{{ $key }}" class="custom-control-input">
                                        <label class="custom-control-label" for="toast-opt-{{ $key }}">{{ $label }}</label>
                                    </div>
                                @else
                                    <label for="toast-opt-{{ $key }}">{{ $label }}</label>
                                    @if($field['type'] === 'select')
                                        <select id="toast-opt-{{ $key }}" wire:model.live="options.{{ $key }}" class="custom-select">
                                            @foreach($field['options'] as $option)
                                                <option value="{{ $option }}">{{ $option }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <input type="number" id="toast-opt-{{ $key }}" wire:model.live.debounce.300ms="options.{{ $key }}" min="{{ $field['min'] }}" max="{{ $field['max'] }}" step="{{ $field['step'] }}" class="form-control">
                                    @endif
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <button type="submit" class="btn btn-primary mr-2" @disabled(!$ready)>{{ __('toast::toast.settings.save') }}</button>
            <button type="button" wire:click="resetAll" wire:confirm="{{ __('toast::toast.settings.reset_confirm') }}" class="btn btn-outline-secondary" @disabled(!$ready)>{{ __('toast::toast.settings.reset') }}</button>
        </form>
    @endif
</div>
