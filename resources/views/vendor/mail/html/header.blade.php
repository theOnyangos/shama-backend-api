@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'Shama Rugby Foundation')
<img src="https://www.srf.co.ke/wp-content/uploads/2020/12/logo.png" class="logo" alt="Shama Logo">
@else
{{ $slot }}
@endif
</a>
</td>
</tr>
