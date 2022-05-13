@extends('settings.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">Import from Instagram</h3>
  </div>
  <hr>
  <section>
    <p class="lead">Before you proceed, you need to have a backup of your account from Instagram, you can do that <a href="https://www.instagram.com/download/request/">here</a>.</p>
  </section>
  <section class="mt-5 col-md-8 offset-md-2">
    <div class="card mb-3 step-one">
      <div class="card-body text-center">
        <p class="h3 font-weight-bold text-uppercase text-muted">Step 1</p>
        <p class="h5 font-weight-bold">Import <b>photos</b> directory</p>
        <p class="text-muted">50mb limit, if your photos directory exceeds that amount please follow this <a href="#">guide</a>.</p>
        <hr>
        <form enctype="multipart/form-data" class="">
          @csrf
          <input type="file" name="photos[]" multiple="" directory="" webkitdirectory="" mozdirectory="">
          <button type="submit" class="mt-4 btn btn-primary btn-block font-weight-bold">Upload Photos</button>
        </form>
      </div>
    </div>
    <div class="card mb-3 step-two">
      <div class="card-body text-center">
        <p class="h3 font-weight-bold text-uppercase text-muted">Step 2</p>
        <p class="h5 font-weight-bold">Import <b>media.json</b> file</p>
        <p class="text-muted">10mb limit, please only upload the media.json file</p>
        <hr>
        <form enctype="multipart/form-data" class="">
          @csrf
          <input type="file" name="media">
          <button type="submit" class="mt-4 btn btn-primary btn-block font-weight-bold">Upload media.json</button>
        </form>
      </div>
      </div>
    <div class="card mb-3 step-three">
      <div class="card-body text-center">
        <p class="h3 font-weight-bold text-uppercase text-muted">Step 3</p>
        <p class="h5 font-weight-bold">Audit Data</p>
        <p class="text-muted">Manually approve each import or bulk approve</p>
        <hr>
        <form enctype="multipart/form-data" class="">
          @csrf
          <button type="submit" class="mt-4 btn btn-primary btn-block font-weight-bold">Start Audit</button>
        </form>
      </div>
      </div>
    </div>
  </section>

@endsection

@push('scripts')
<script type="text/javascript">
  $(document).ready(function() {
      const currentStep = 1;
      const stepOne = $('.step-one');
      const stepTwo = $('.step-two');
      const stepThree = $('.step-three');

      if(currentStep == 1) {

      stepTwo.addClass('card-disabled').find('form').hide();
      stepThree.addClass('card-disabled').find('form').hide();

    } else if(currentStep == 2) {

      stepOne.addClass('card-disabled').find('form').hide();
      stepThree.addClass('card-disabled').find('form').hide();

    } else if(currentStep == 3) {

      stepOne.addClass('card-disabled').find('form').hide();
      stepTwo.addClass('card-disabled').find('form').hide();

    }
  });
</script>
@endpush