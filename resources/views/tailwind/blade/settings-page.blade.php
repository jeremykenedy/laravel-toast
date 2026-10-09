@extends(config('toast.settings.layout') ?: 'toast::layout')

@section(config('toast.settings.section', 'content'))
    @include('toast::settings')
@endsection
