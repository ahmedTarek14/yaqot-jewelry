@extends('layouts.admin.master')

@section('title', __('admin.nav.dashboard'))

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body p-4">
                    <h2 class="h4 mb-2">{{ __('home::home.dashboard.welcome', ['name' => auth('admin')->user()->name]) }}</h2>
                    <p class="mb-0">{{ __('home::home.dashboard.intro') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body p-4 text-center text-muted">
                    {{ __('home::home.dashboard.empty_stats') }}
                </div>
            </div>
        </div>
    </div>
@endsection
