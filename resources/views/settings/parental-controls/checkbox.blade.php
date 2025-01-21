@php
$id = str_random(6) . '_' . str_slug($name);
$defaultChecked = isset($checked) && $checked ? 'checked=""' : '';
@endphp<div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="{{$id}}" name="{{$name}}" {!!$defaultChecked!!}>
                <label class="custom-control-label pl-2" for="{{$id}}">{{ $title }}</label>
            </div>
