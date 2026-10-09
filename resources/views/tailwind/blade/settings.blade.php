@php
    use Jeremykenedy\LaravelToast\Support\ToastSettings;

    $enabled = ToastSettings::enabled();
    $data = $enabled ? ToastSettings::payload() : null;
    $palette = [
        'success' => ['box' => 'bg-green-50 text-green-800 border-green-200 dark:bg-green-950 dark:text-green-200 dark:border-green-800', 'track' => 'bg-green-200 dark:bg-green-900', 'bar' => 'bg-green-500 dark:bg-green-400', 'icon' => 'text-green-500 dark:text-green-400', 'path' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
        'error'   => ['box' => 'bg-red-50 text-red-800 border-red-200 dark:bg-red-950 dark:text-red-200 dark:border-red-800', 'track' => 'bg-red-200 dark:bg-red-900', 'bar' => 'bg-red-500 dark:bg-red-400', 'icon' => 'text-red-500 dark:text-red-400', 'path' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'],
        'warning' => ['box' => 'bg-amber-50 text-amber-800 border-amber-200 dark:bg-amber-950 dark:text-amber-200 dark:border-amber-800', 'track' => 'bg-amber-200 dark:bg-amber-900', 'bar' => 'bg-amber-500 dark:bg-amber-400', 'icon' => 'text-amber-500 dark:text-amber-400', 'path' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
        'info'    => ['box' => 'bg-blue-50 text-blue-800 border-blue-200 dark:bg-blue-950 dark:text-blue-200 dark:border-blue-800', 'track' => 'bg-blue-200 dark:bg-blue-900', 'bar' => 'bg-blue-500 dark:bg-blue-400', 'icon' => 'text-blue-500 dark:text-blue-400', 'path' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
    ];
@endphp
<div class="w-full max-w-5xl text-gray-900 dark:text-gray-100">
    <div class="mb-6">
        <h2 class="text-xl font-semibold tracking-tight">{{ __('toast::toast.settings.title') }}</h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('toast::toast.settings.description') }}</p>
    </div>

    @if(!$enabled)
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200" role="alert">{{ __('toast::toast.settings.disabled') }}</div>
    @else
        @unless($data['ready'])
            <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200" role="alert">{{ __('toast::toast.settings.not_ready') }}</div>
        @endunless

        <form method="POST" action="{{ $data['urls']['update'] }}" data-toast-settings class="space-y-8">
            @csrf
            @method('PUT')
            <style data-toast-preview-style></style>

            <section aria-labelledby="toast-colors-heading" class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <h3 id="toast-colors-heading" class="text-base font-semibold">{{ __('toast::toast.settings.colors') }}</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('toast::toast.settings.colors_help') }}</p>

                <div class="mt-6 space-y-8">
                    @foreach($data['types'] as $type)
                        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <h4 class="mb-4 text-sm font-semibold">{{ __('toast::toast.settings.types.'.$type) }}</h4>
                            <div class="grid gap-6 lg:grid-cols-2">
                                @foreach(['light', 'dark'] as $mode)
                                    <div>
                                        <p class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('toast::toast.settings.'.$mode) }}</p>
                                        <div class="space-y-2">
                                            @foreach($data['parts'] as $part)
                                                @php $value = $data['colors'][$type][$mode][$part] ?? null; @endphp
                                                <div class="flex items-center gap-3" data-toast-color-row data-default-label="{{ __('toast::toast.settings.default') }}" @if($value) data-custom="true" @endif>
                                                    <span class="w-32 shrink-0 text-sm text-gray-700 dark:text-gray-300">{{ __('toast::toast.settings.parts.'.$part) }}</span>
                                                    <input type="color" data-toast-color-input value="{{ $value ?? '#888888' }}" aria-label="{{ __('toast::toast.settings.types.'.$type) }} {{ __('toast::toast.settings.'.$mode) }} {{ __('toast::toast.settings.parts.'.$part) }}" class="h-8 w-10 cursor-pointer rounded border border-gray-300 bg-white p-0.5 dark:border-gray-600 dark:bg-gray-800">
                                                    <input type="hidden" name="colors[{{ $type }}][{{ $mode }}][{{ $part }}]" value="{{ $value }}" data-toast-color-value="{{ $type }}.{{ $mode }}.{{ $part }}">
                                                    <span data-toast-color-label class="w-20 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $value ?? __('toast::toast.settings.default') }}</span>
                                                    <button type="button" data-toast-color-clear class="text-xs text-gray-600 underline hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">{{ __('toast::toast.settings.clear') }}</button>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section aria-labelledby="toast-preview-heading" class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <h3 id="toast-preview-heading" class="text-base font-semibold">{{ __('toast::toast.settings.preview') }}</h3>
                <div class="mt-4 grid gap-6 lg:grid-cols-2">
                    @foreach(['light', 'dark'] as $mode)
                        <div data-toast-preview="{{ $mode }}" @if($mode === 'dark') class="dark rounded-lg bg-gray-900 p-4" data-bs-theme="dark" @else class="rounded-lg bg-gray-100 p-4" @endif>
                            <p class="mb-3 text-xs font-medium uppercase tracking-wide {{ $mode === 'dark' ? 'text-gray-400' : 'text-gray-500' }}">{{ __('toast::toast.settings.'.$mode) }}</p>
                            <div class="space-y-3">
                                @foreach($data['types'] as $type)
                                    <div data-laravel-toast="preview" data-toast-type="{{ $type }}" class="overflow-hidden rounded-xl border shadow-lg {{ $palette[$type]['box'] }}">
                                        <div data-toast-part="track" class="h-1 w-full {{ $palette[$type]['track'] }}"><div data-toast-part="bar" class="h-full w-2/3 {{ $palette[$type]['bar'] }}"></div></div>
                                        <div class="flex items-start gap-3 p-4">
                                            <div data-toast-part="icon" class="mt-0.5 shrink-0 {{ $palette[$type]['icon'] }}"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $palette[$type]['path'] }}" /></svg></div>
                                            <div class="min-w-0 flex-1">
                                                <p class="text-sm font-semibold tracking-tight">{{ __('toast::toast.settings.types.'.$type) }}</p>
                                                <p class="text-sm leading-relaxed">{{ __('toast::toast.settings.sample_message') }}</p>
                                            </div>
                                            <span data-toast-part="close" class="shrink-0 rounded-md p-1 opacity-60"><svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg></span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section aria-labelledby="toast-options-heading" class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <h3 id="toast-options-heading" class="text-base font-semibold">{{ __('toast::toast.settings.options') }}</h3>
                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($data['fields'] as $key => $field)
                        @php $current = $data['options'][$key]; $label = __('toast::toast.settings.fields.'.$key); @endphp
                        @if($field['type'] === 'checkbox')
                            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input type="hidden" name="options[{{ $key }}]" value="0">
                                <input type="checkbox" name="options[{{ $key }}]" value="1" @checked($current) @if(in_array($key, ['show_icons', 'show_close', 'show_progress', 'show_border'])) data-toast-toggle="{{ ['show_icons' => 'icon', 'show_close' => 'close', 'show_progress' => 'track', 'show_border' => 'border'][$key] }}" @endif class="h-4 w-4 rounded border-gray-300 text-blue-600 dark:border-gray-600 dark:bg-gray-800">
                                <span>{{ $label }}</span>
                            </label>
                        @else
                            <label class="block text-sm text-gray-700 dark:text-gray-300">
                                <span class="mb-1 block">{{ $label }}</span>
                                @if($field['type'] === 'select')
                                    <select name="options[{{ $key }}]" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800">
                                        @foreach($field['options'] as $option)
                                            <option value="{{ $option }}" @selected($current === $option)>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="number" name="options[{{ $key }}]" value="{{ $current }}" min="{{ $field['min'] }}" max="{{ $field['max'] }}" step="{{ $field['step'] }}" @if($key === 'opacity') data-toast-opacity @endif class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800">
                                @endif
                            </label>
                        @endif
                    @endforeach
                </div>
            </section>

            <div class="flex flex-wrap items-center gap-3">
                <button type="submit" @disabled(!$data['ready']) class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-blue-500 dark:hover:bg-blue-600">{{ __('toast::toast.settings.save') }}</button>
                <button type="submit" form="toast-settings-reset" data-toast-confirm="{{ __('toast::toast.settings.reset_confirm') }}" @disabled(!$data['ready']) class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">{{ __('toast::toast.settings.reset') }}</button>
            </div>
        </form>

        <form id="toast-settings-reset" method="POST" action="{{ $data['urls']['destroy'] }}" class="hidden">
            @csrf
            @method('DELETE')
        </form>

        @include('toast-shared::settings-script')
    @endif
</div>
