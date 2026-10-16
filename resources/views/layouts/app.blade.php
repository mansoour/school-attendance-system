<x-admin-layout>
@isset($header)<div class="page-head"><div>{{ $header }}</div></div>@endisset
{{ $slot }}
</x-admin-layout>
