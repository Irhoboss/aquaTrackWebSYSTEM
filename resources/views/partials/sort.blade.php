@php $dir = request('sort') === $col && request('dir') === 'asc' ? 'desc' : 'asc'; @endphp
<a href="{{ request()->fullUrlWithQuery(['sort' => $col, 'dir' => $dir, 'page' => null]) }}">{{ $label }}@if(request('sort') === $col)<i class="bi bi-caret-{{ request('dir') === 'asc' ? 'up' : 'down' }}-fill small"></i>@endif</a>
