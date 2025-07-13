<div class="flex flex-wrap gap-1">
    @foreach($getRecord()->roles as $role)
        @php
            $color = match ($role->name) {
                'Admin' => 'success',
                'Merchant' => 'primary',
                'Customer' => 'warning',
                default => 'gray',
            };
        @endphp

        <x-filament::badge :color="$color">
            {{ $role->name }}
        </x-filament::badge>
    @endforeach
</div>