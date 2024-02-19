@php
$id = str_random(14);
@endphp
<h1 class="text-center">Before you continue.</h1>
@if(config_cache('app.rules') && strlen(config_cache('app.rules')) > 5)
<p class="lead text-center">Let's go over a few basic guidelines established by the server's administrators.</p>

@include('auth.curated-register.partials.server-rules')
@else
<p class="lead text-center mt-4"><span class="opacity-5">The admins have not specified any community rules, however we suggest youreview the</span> <a href="/site/terms" target="_blank" class="text-white font-weight-bold">Terms of Use</a> <span class="opacity-5">and</span> <a href="/site/privacy" target="_blank" class="text-white font-weight-bold">Privacy Policy</a>.</p>
@endif

<div class="action-btns">
    <form method="post" id="{{$id}}" class="flex-grow-1">
        @csrf
        <input type="hidden" name="step" value="1">
        <button type="button" class="btn btn-primary rounded-pill font-weight-bold btn-block flex-grow-1" onclick="onSubmit()">Accept</button>
    </form>

    <a class="btn btn-outline-muted rounded-pill" href="/">Go back</a>
</div>

<div class="small-links">
    <a href="/login">Login</a>
    <span>·</span>
    <a href="/auth/sign_up/resend-confirmation">Re-send confirmation</a>
    <span>·</span>
    <a href="{{route('help.curated-onboarding')}}" target="_blank">Help</a>
</div>

@push('scripts')
<script>
    function onSubmit() {
        @if ($errors->any())
        document.getElementById('{{$id}}').submit();
        return;
        @endif
        swal({
            text: "Please select the region you are located in",
            icon: "info",
            buttons: {
                cancel: false,
                usa: {
                    text: "United States",
                    className: "swal-button--cancel",
                    value: "usa"
                },
                uk: {
                    text: "UK",
                    className: "swal-button--cancel",
                    value: "uk"
                },
                eu: {
                    text: "EU",
                    className: "swal-button--cancel",
                    value: "eu"
                },
                other: {
                    text: "Other",
                    className: "swal-button--cancel",
                    value: "other"
                }
            },
            dangerMode: false,
        }).then((region) => {
            handleRegion(region);
        })
    }

    function handleRegion(region) {
        if(!region) {
            return;
        }
        let minAge = 16;
        if(['usa', 'uk', 'other'].includes(region)) {
            minAge = 13;
        }
        swal({
            title: "Enter Your Date of Birth",
            text: "We require your birthdate solely to confirm that you meet our age requirement.\n\n Rest assured, this information is not stored or used for any other purpose.",
            content: {
                element: "input",
                attributes: {
                    placeholder: "Enter your birthday in YYYY-MM-DD format",
                    type: "date",
                    min: '1900-01-01',
                    max: getToday(),
                    pattern: "\d{4}-\d{2}-\d{2}",
                    required: true
                }
            },
            buttons: {
                cancel: false,
                confirm: {
                    text: 'Confirm Birthdate',
                    className: "swal-button--cancel",
                }
            }
        }).then((inputValue) => {
            if (inputValue === false) return;

            if (inputValue === "") {
                swal("Oops!", "You need to provide your date of birth to proceed!", "error");
                return false;
            }

            const dob = new Date(inputValue);
            if (isValidDate(dob)) {
                const age = calculateAge(dob);
                // swal(`Your age is ${age}`);
                if(age >= 120 || age <= 5) {
                    swal({
                        title: "Ineligible to join",
                        text: "Sorry, the birth date you provided is not valid and we cannot process your request at this time.\n\nIf you entered your birthdate incorrectly you can try again, otherwise if you attempt to bypass our minimum age requirements, your account may be suspended.",
                        icon: "error",
                        buttons: {
                            cancel: "I understand"
                        }
                    }).then(() => {
                        window.location.href = '/'
                    });
                    return;
                }
                if (age >= minAge) {
                    document.getElementById('{{$id}}').submit();
                } else {
                    swal({
                        title: "Ineligible to join",
                        text: `Sorry, you must be at least ${minAge} years old to join our service according to the laws of your country or region.`,
                        icon: "error",
                        buttons: {
                            cancel: "I understand"
                        }
                    }).then(() => {
                        window.location.href = '/'
                    });
                }
            } else {
                swal("Invalid date format!");
                return false;
            }
        });
    }

    function calculateAge(dob) {
        const diff_ms = Date.now() - dob.getTime();
        console.log(diff_ms);
        const age_dt = new Date(diff_ms);
        return Math.abs(age_dt.getUTCFullYear() - 1970);
    }

    function getToday() {
        var today = new Date();
        var dd = today.getDate();
        var mm = today.getMonth() + 1;
        var yyyy = today.getFullYear();

        if (dd < 10) {
           dd = '0' + dd;
        }

        if (mm < 10) {
           mm = '0' + mm;
        }

        yyyy = yyyy - 10;

        return yyyy + '-' + mm + '-' + dd;
    }

    function isValidDate(d) {
        return d instanceof Date && !isNaN(d);
    }
</script>
@endpush
