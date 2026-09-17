@props([
    /* Surface name to gate on (login, register, forgot_password, password_reset,
       forgot_email, curated_register). Ignored when :show is passed explicitly. */
    'surface' => null,
    /* Explicit visibility override for compound conditions (login triggers,
       curated registration). When null, visibility is derived from $surface. */
    'show' => null,
    /* Optional widget theme, e.g. "dark". */
    'theme' => null,
    /* Render a "Captcha" label above the widget. */
    'label' => false,
    /* Classes for the label element. */
    'labelClass' => 'font-weight-bold small text-muted',
    /* Render the validation error message below the widget. */
    'showError' => false,
    /* Wrapper element classes around the widget itself. */
    'wrapperClass' => 'd-flex justify-content-center my-3',
])

@php
    $visible = ! is_null($show)
        ? (bool) $show
        : ($surface ? \App\Facades\Captcha::activeOn($surface) : false);
    $captchaAttrs = $theme ? ['data-theme' => $theme] : [];
    $captchaField = \App\Facades\Captcha::active()->responseField();
@endphp

@if($visible)
    @if($label)
        <label class="{{ $labelClass }}">Captcha</label>
    @endif
    <div class="{{ $wrapperClass }}">
        @captcha($captchaAttrs)
        @captchaScripts
    </div>
    @if($showError && $errors->has($captchaField))
        <div class="text-danger small mb-3">
            <strong>{{ $errors->first($captchaField) }}</strong>
        </div>
    @endif
@endif
