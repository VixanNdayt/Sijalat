@props(['user' => null, 'label' => null])

@php
    $user = $user ?? auth()->user();
    $name = $user?->name ?? 'Pengguna';
    $photoUrl = $user?->profilePhotoUrl();
    $initials = $user?->initials() ?? 'U';
@endphp

<span
    {{ $attributes->merge(['class' => 'user-avatar']) }}
    role="img"
    aria-label="{{ $label ?? $name }}"
>
    @if ($photoUrl)
        <img src="{{ $photoUrl }}" alt="">
    @else
        <span class="user-avatar-initials">{{ $initials }}</span>
    @endif
</span>
