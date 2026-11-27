@props(['user', 'size' => null])
@php
    $initials = collect(preg_split('/\s+/', trim($user->name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
@endphp
<span {{ $attributes->class(['avatar', 'c'.($user->id % 6), $size]) }} title="{{ $user->name }}">{{ $initials }}</span>
