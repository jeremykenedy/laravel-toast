@php
    use Jeremykenedy\LaravelToast\Support\ToastAnimations;

    $globalPosition = config('toast.position', 'top-right');
    $positionMap = [
        'top-left'=>'top:0.5rem;left:0.5rem;','top-center'=>'top:0.5rem;left:50%;transform:translateX(-50%);',
        'top-right'=>'top:0.5rem;right:0.5rem;','bottom-right'=>'bottom:0.5rem;right:0.5rem;',
        'bottom-left'=>'bottom:0.5rem;left:0.5rem;','bottom-center'=>'bottom:0.5rem;left:50%;transform:translateX(-50%);',
    ];
    $grouped = [];
    $displayToasts = $toasts;
    foreach ($displayToasts as $t) {
        $pos = $t['position'] ?? $globalPosition;
        if (!isset($positionMap[$pos])) $pos = 'top-right';
        $grouped[$pos][] = $t;
    }
    $typeMap = ['success'=>'success','error'=>'danger','warning'=>'warning','info'=>'info'];
    $iconMap = [
        'success'=>'<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/></svg>',
        'error'=>'<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293 5.354 4.646z"/></svg>',
        'warning'=>'<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/></svg>',
        'info'=>'<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/></svg>',
    ];
@endphp
<div>
@if(count($displayToasts) > 0)
{!! ToastAnimations::styleTag('bootstrap5') !!}
@foreach($grouped as $pos => $posToasts)
<div class="position-fixed" style="{{ $positionMap[$pos] }} z-index:9999; width:min(400px, calc(100vw - 1rem)); pointer-events:none;">
    <div class="toast-container">
        @foreach($posToasts as $toast)
        @php
            $bsType = $typeMap[$toast['type']] ?? 'info';
            $enterStyle = (($toast['enter_animation'] ?? 'none') !== 'none') ? 'animation:toast-enter-' . $toast['enter_animation'] . ' ' . ($toast['enter_duration'] ?? 0.5) . 's ease forwards;' : '';
            $opacityStyle = (($toast['opacity'] ?? 1) < 1) ? 'opacity:' . $toast['opacity'] . ';' : '';
            $borderClass = (($toast['show_border'] ?? true) === false) ? ' border-0' : '';
        @endphp
        <div wire:key="{{ $toast['id'] }}"
             id="lw-toast-{{ $toast['id'] }}"
         data-laravel-toast="livewire" data-css-framework="bootstrap5"
             class="toast show align-items-center mb-2 w-100 text-bg-{{ $bsType }} overflow-hidden rounded-3 shadow{{ $borderClass }}"
             role="alert" aria-live="{{ $toast['type'] === 'error' ? 'assertive' : 'polite' }}" aria-atomic="true"
             dir="{{ $toast['dir'] ?? 'ltr' }}"
             style="pointer-events:auto;cursor:default;{{ $opacityStyle }}{{ $enterStyle }}"
             data-auto-dismiss="{{ ($toast['auto_dismiss'] ?? true) ? 'true' : 'false' }}"
             data-duration="{{ $toast['duration'] }}"
             data-pause-on-hover="{{ ($toast['pause_on_hover'] ?? true) ? 'true' : 'false' }}"
             data-exit-animation="{{ $toast['exit_animation'] ?? 'none' }}"
             data-exit-duration="{{ $toast['exit_duration'] ?? 0.5 }}">
            @if(($toast['auto_dismiss'] ?? true) && ($toast['show_progress'] ?? true) !== false && ($toast['duration'] ?? 0) > 0 && ($toast['progress_position'] ?? 'top') === 'top')
            <div style="height:3px;background:rgba(255,255,255,0.3);"><div class="toast-progress-bar" style="height:100%;width:100%;background:rgba(255,255,255,0.7);transition:none;{{ ($toast['progress_direction'] ?? 'rtl') === 'rtl' ? 'margin-left:auto;' : '' }}" data-duration="{{ $toast['duration'] }}"></div></div>
            @endif
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center gap-2" style="cursor:default;">
                    @if(($toast['show_icon'] ?? true) !== false)
                        <span class="flex-shrink-0 d-inline-flex">{!! $toast['custom_icon'] ?? ($iconMap[$toast['type']] ?? $iconMap['info']) !!}</span>
                    @endif
                    <div class="text-break">
                        @if($toast['title'] ?? null) <strong class="d-block">{{ $toast['title'] }}</strong> @endif
                        {{ $toast['message'] }}
                    </div>
                </div>
                @if(($toast['show_close'] ?? true) !== false)
                <button type="button" data-toast-dismiss class="btn-close btn-close-white me-2 m-auto flex-shrink-0" style="cursor:pointer;" aria-label="{{ __('toast::toast.dismiss') }}"></button>
                @endif
            </div>
            @if(($toast['auto_dismiss'] ?? true) && ($toast['show_progress'] ?? true) !== false && ($toast['duration'] ?? 0) > 0 && ($toast['progress_position'] ?? 'top') !== 'top')
            <div style="height:3px;background:rgba(255,255,255,0.3);"><div class="toast-progress-bar" style="height:100%;width:100%;background:rgba(255,255,255,0.7);transition:none;{{ ($toast['progress_direction'] ?? 'rtl') === 'rtl' ? 'margin-left:auto;' : '' }}" data-duration="{{ $toast['duration'] }}"></div></div>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endforeach
@endif
@include('toast-livewire::partials.timer-script')
</div>
