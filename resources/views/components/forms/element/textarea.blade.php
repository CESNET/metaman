<label>
<textarea @class([
    'text-sm dark:bg-transparent focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm border-gray-300 dark:border-gray-700 rounded-md',
    'border-red-500 border' => $errors->has($err),
    'border-green-500' => !$errors->has($err) && old('explanation') !== null,
    ]){{$slot}}>{{ old('$content') }}</textarea>
</label>

