{{-- A titled card that groups related form fields. --}}
@props(['title', 'description' => null])

<section {{ $attributes->class(['card yq-form-section']) }}>
    <div class="card-header pb-0">
        <h2 class="h6 mb-0">{{ $title }}</h2>
        @if ($description)
            <p class="text-sm text-muted mb-0 mt-1">{{ $description }}</p>
        @endif
    </div>
    <div class="card-body">
        {{ $slot }}
    </div>
</section>
