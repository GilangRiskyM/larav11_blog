@props(['name', 'id'])
<select name="{{ $name }}" id="{{ $id }}"
    class="border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
    {{ $slot }}
</select>
