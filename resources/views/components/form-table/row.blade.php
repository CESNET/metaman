<tr x-data class="hover:bg-blue-50 dark:hover:bg-gray-700" role="button"
    @click="checkbox = $el.querySelector('input[type=checkbox]'); checkbox.checked = !checkbox.checked">

    {{$slot}}

</tr>
