 <section class="step-wizard">
    <ul class="step-wizard-list">
        <li class="step-wizard-item {{ $step === 1 ? 'current-item':'' }}">
            <span class="progress-count">1</span>
            <span class="progress-label">Review Rules</span>
        </li>
        <li class="step-wizard-item {{ $step === 2 ? 'current-item':'' }}">
            <span class="progress-count">2</span>
            <span class="progress-label">Your Details</span>
        </li>
        <li class="step-wizard-item {{ $step === 3 ? 'current-item':'' }}">
            <span class="progress-count">3</span>
            <span class="progress-label">Confirm Email</span>
        </li>
        <li class="step-wizard-item {{ $step === 4 ? 'current-item':'' }}">
            <span class="progress-count">4</span>
            <span class="progress-label">Await Review</span>
        </li>
    </ul>
</section>

@push('styles')
<style type="text/css">
.step-wizard {
    width: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 1rem auto;
}

.step-wizard-list{
    background: transparent;
    color: #fff;
    list-style-type: none;
    border-radius: 10px;
    display: flex;
    padding: 20px 10px;
    position: relative;
    z-index: 10;
}

.step-wizard-item {
    padding: 0 10px;
    flex-basis: 0;
    -webkit-box-flex: 1;
    -ms-flex-positive:1;
    flex-grow: 1;
    max-width: 100%;
    display: flex;
    flex-direction: column;
    text-align: center;
    min-width: 20px;
    position: relative;
}
@media (min-width: 600px) {
    .step-wizard-item {
        padding: 0 20px;
        min-width: 140px;
    }
}
.step-wizard-item + .step-wizard-item:after{
    content: "";
    position: absolute;
    left: 0;
    top: 19px;
    background: var(--primary);
    width: 100%;
    height: 2px;
    transform: translateX(-50%);
    z-index: -10;
}
.progress-count{
    height: 40px;
    width:40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-weight: 600;
    margin: 0 auto;
    position: relative;
    z-index:10;
    color: transparent;
}
.progress-count:after{
    content: "";
    height: 40px;
    width: 40px;
    background: var(--primary);
    position: absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    border-radius: 50%;
    z-index: -10;
}
.progress-count:before{
    content: "";
    height: 10px;
    width: 20px;
    border-left: 3px solid #fff;
    border-bottom: 3px solid #fff;
    position: absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -60%) rotate(-45deg);
    transform-origin: center center;
}
.progress-label{
    font-size: 14px;
    font-weight: 600;
    margin-top: 10px;
}
.current-item .progress-count:before,
.current-item ~ .step-wizard-item .progress-count:before{
    display: none;
}
.current-item ~ .step-wizard-item .progress-count:after{
    height:10px;
    width:10px;
}
.current-item ~ .step-wizard-item .progress-label{
    opacity: 0.5;
}
.current-item .progress-count:after{
    background: #080e2b;
    border: 2px solid var(--primary);
}
.current-item .progress-count{
    color: #fff;
}
</style>
@endpush
