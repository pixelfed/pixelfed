@extends('layouts.app')

@section('content')
<div class="container my-5">
  <div class="col-12 col-lg-8 offset-lg-2">
    <div class="card mt-4">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span><a href="{{route('timeline.personal')}}" class="text-muted"><i class="fas fa-times fa-lg"></i></a></span>
        <span>
          <div class="media">
            <div class="media-body">
              <p class="mb-0">
                <span class="font-weight-bold">Compose</span>
              </p>
            </div>
          </div>
        </span>
        <div>
          <button class="btn btn-link text-muted" type="button" id="composeMenu">
            <i class="fas fa-cog fa-lg"></i>
          </button>
        </div>
      </div>
      <div class="card-body bg-light">
        <div class="composeLoader d-none text-center">
          <div class="lds-ring"><div></div><div></div><div></div><div></div></div>
        </div>
        <div class="d-none preview-pagination">
          <div class="d-flex justify-content-between align-items-center">
            <p class="prev text-light" onclick="pixelfed.uploader.previous()"><i class="fas fa-chevron-left"></i></p>
            <p class="font-weight-bold">
              <span class="cursor">1</span>
              <span>of</span>
              <span class="total">2</span>
            </p>
            <p class="next" onclick="pixelfed.uploader.next()"><i class="fas fa-chevron-right"></i></p>
          </div>
          <div class="preview-thumbs">
            <div class="d-flex justify-content-center" style="overflow-x: auto;">

            </div>
          </div>
        </div>
        <div class="preview row"></div>
        <div class="preview-meta mb-4 d-none form-inline">
          <div class="form-group">

            <div class="btn-group mr-3" role="group" aria-label="First group">
              <button type="button" class="btn btn-outline-secondary btn-sm py-1" id="cw" data-toggle="tooltip" data-placement="bottom" title="A content warning or nsfw warning is required for certain content."><i class="far fa-eye d-none d-md-block"></i> CW/NSFW</button>
              <button type="button" class="btn btn-outline-secondary btn-sm py-1" id="alt"><i class="fab fa-accessible-icon d-none d-md-block"></i> Media Description</button>
              <button type="button" class="btn btn-outline-secondary btn-sm py-1" id="license"><i class="fas fa-balance-scale d-none d-md-block"></i> Licence</button>
              {{-- <button type="button" class="btn btn-outline-secondary btn-sm py-1" id="crop"><i class="fas fa-crop d-none d-md-block"></i> Crop</button>
              <button type="button" class="btn btn-outline-secondary btn-sm py-1" id="exif" data-toggle="tooltip" data-placement="bottom" title="Preserve EXIF data for this photo or video. This can expose location data."><i class="fas fa-info-circle d-none d-md-block"></i> EXIF</button> --}}
            </div>
          </div>
          <div class="form-group">
            <label class="font-weight-bold text-muted mr-2">Filter:</label>
            <select class="form-control form-control-sm d-inline" id="filterSelectDropdown">
              <option value="none">No filter</option>
            </select>
          </div>
        </div>
        <input type="file" name="media" class="d-none file-input" multiple="">
        <div class="caption d-none">
          <div class="form-group mb-0">
            <textarea class="form-control input-elevated font-weight-bold" placeholder="Add an optional caption"></textarea>
          </div>
        </div>
        <div class="options"></div>
        <div class="welcome-text">
          <p class="text-muted lead text-center mb-0">Select a photo or video.</p>
        </div>
      </div>
      <div class="card-footer border-top-0 bg-light d-flex align-items-center justify-content-center">
        <span class="small font-weight-bold d-none visibility">
          <span class="text-muted pr-2">Visibility:</span>
          <div class="dropdown d-inline">
            <button class="btn btn-outline-secondary btn-sm py-0 dropdown-toggle" type="button" id="visibility" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              Public
            </button>
            <div class="dropdown-menu" aria-labelledby="visibility">
              <a class="dropdown-item active" href="#" data-id="public" data-title="Public">
                <div class="row">
                  <div class="col-12 col-sm-2 px-0 text-center">
                    <i class="fas fa-globe"></i>
                  </div>
                  <div class="col-12 col-sm-10 pl-2">
                    <p class="font-weight-bold mb-0">Public</p>
                    <p class="small mb-0">Anyone can see</p>
                  </div>
                </div>
              </a>
              <a class="dropdown-item" href="#" data-id="private" data-title="Followers Only">
                <div class="row">
                  <div class="col-12 col-sm-2 px-0 text-center">
                    <i class="fas fa-lock"></i>
                  </div>
                  <div class="col-12 col-sm-10 pl-2">
                    <p class="font-weight-bold mb-0">Followers Only</p>
                    <p class="small mb-0">Only followers can see</p>
                  </div>
                </div>
              </a>
              {{-- <a class="dropdown-item" href="#" data-id="direct" data-title="Direct Message">
                <div class="row">
                  <div class="col-12 col-sm-2 px-0 text-center">
                    <i class="fas fa-envelope"></i>
                  </div>
                  <div class="col-12 col-sm-10 pl-2">
                    <p class="font-weight-bold mb-0">Direct Message</p>
                    <p class="small mb-0">Recipients only</p>
                  </div>
                </div>
              </a> --}}
            </div>
          </div>
        </span>
        <span class="float-right">
          <button type="button" class="btn btn-outline-secondary font-weight-regular py-0 px-3 btn-block" id="addMedia">
          <span class="d-md-none"><i class="fas fa-camera-retro"></i></span>
          <span class="d-none d-md-block">Add photo/video</span>
          </button>
          <button type="button" class="btn btn-primary py-0 px-3 font-weight-regular d-none" id="create">Create Post</button>
        </span >
      </div>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
pixelfed.uploader = {
  ids: [],
  media: [],
  meta: [],
  cursor: 1,
  visibility: 'public',
  cropmode: false,
  croppie: false,
  limit: {{ config('pixelfed.max_album_length') }},
  acceptedMimes: "{{config('pixelfed.media_types')}}"
};

function generateFilterSelect() {
    let filters = pixelfed.filters.list;
    for(var i = 0, len = filters.length; i < len; i++) {
        let filter = filters[i];
        let name = filter[0];
        let className = filter[1];
        let select = $('#filterSelectDropdown');
        var template = '<option value="' + className + '">' + name + '</option>';
        select.append(template);
    }
    pixelfed.create.hasGeneratedSelect = true;
}

$(document).ready(function() {
  generateFilterSelect();
});


pixelfed.uploader.updateFilter = function(cursor, oldFilter, filter) {
    let card = $('.preview-card[data-id='+cursor+'] img');
    if(filter == 'none') {
      if(oldFilter && card.hasClass(oldFilter.filter)) {
        card.removeClass(oldFilter.filter);
      }
    } else {
      if(oldFilter && card.hasClass(oldFilter.filter)) {
        card.removeClass(oldFilter.filter);
      }
      card.addClass(filter);
    }
}

pixelfed.uploader.addPreview = function(d) {
  let el = $('.preview');
  $('#addMedia').removeClass('btn-block');
  $('.card-footer').removeClass('justify-content-center').addClass('justify-content-between');
  if(!el.hasClass('pb-1')) {
    el.addClass('pb-1');
  }
  if($('.preview-meta').hasClass('d-none')) {
    $('.preview-meta').removeClass('d-none');
  }
  let card = $('<div>');
  if(pixelfed.uploader.ids.length > 1) {
    card.addClass('preview-card col-12 mb-2 d-none');
  } else {
    card.addClass('preview-card col-12 mb-2');
  }
  card.attr('data-id', pixelfed.uploader.ids.length);
  let img = $('<img>').addClass('w-100 h-100').attr('src', d.url);
  card = card.append(img);
  el.append(card);
  let thumb = $('<img>');
  let thumbrow = $('.preview-thumbs .d-flex');
  thumbrow.addClass('mb-2');
  thumb.addClass('mx-2').attr('width', '79px').attr('height', '60px').attr('src', d.url);
  thumb.attr('data-cursor', pixelfed.uploader.ids.length);
  thumbrow.append(thumb);
  $('.visibility').removeClass('d-none');
  $('#create').removeClass('d-none');
  $('.caption').removeClass('d-none');
}

pixelfed.uploader.goto = function(i)
{
  let cursor = pixelfed.uploader.cursor;
  let prev = $('.preview-card[data-id='+cursor+']');
  let next = $('.preview-card[data-id='+i+']');
  prev.addClass('d-none');
  next.removeClass('d-none');
  pixelfed.uploader.cursor = i;
  pixelfed.uploader.loadMeta();
  $('.preview-pagination .cursor').text(i);
}

pixelfed.uploader.paginate = function(d) {
  let el = $('.preview-pagination');
  let len = pixelfed.uploader.media.length;
  if(len > 1) {
    el.find('.total').text(len);
    if(el.hasClass('d-none')) {
      el.removeClass('d-none');
    }
  }
  $('.prev').addClass('text-light');
  pixelfed.uploader.cursor = 1;
}

pixelfed.uploader.previous = function() {
    let cursor = pixelfed.uploader.cursor;
    let len = pixelfed.uploader.ids.length;
    let i = cursor - 1;
    if(cursor <= 1) {
      return;
    }
    $('.next').removeClass('text-light')
    let cur = $('.preview-card[data-id='+cursor+']');
    let next = $('.preview-card[data-id='+i+']');
    cur.addClass('d-none');
    next.removeClass('d-none');
    pixelfed.uploader.cursor = i;
    pixelfed.uploader.loadMeta();
    $('.preview-pagination .cursor').text(i);
};

pixelfed.uploader.next = function() {
    let cursor = pixelfed.uploader.cursor;
    let len = pixelfed.uploader.ids.length;
    let i = cursor + 1;
    if(cursor >= len) {
      return;
    }
    let cur = $('.preview-card[data-id='+cursor+']');
    let next = $('.preview-card[data-id='+i+']');
    $('.prev').removeClass('text-light');
    cur.addClass('d-none');
    next.removeClass('d-none');
    pixelfed.uploader.cursor = i;
    pixelfed.uploader.loadMeta();
    $('.preview-pagination .cursor').text(i);
};

pixelfed.uploader.loadMeta = function() {
  $('#filterSelectDropdown').val('none');
  let cursor = pixelfed.uploader.cursor;
  let meta = pixelfed.uploader.meta[cursor - 1];
  let filter = meta.filter;
  if(filter) {
    $('#filterSelectDropdown').val(filter);
  }
  $('.prev').addClass('text-light');
  $('.next').addClass('text-light');
  if(cursor == 1) {
    $('.prev').addClass('text-light');
    if(pixelfed.uploader.ids.length > 1) {
      $('.next').removeClass('text-light');
    }
  }
  if(cursor > 1) {
      $('.prev').removeClass('text-light');
    if(pixelfed.uploader.ids.length > cursor) {
      $('.next').removeClass('text-light');
    }
  }
  if(meta.cw == true) {
    $('#cw').removeClass('btn-outline-secondary').addClass('btn-danger');
  } else {
    $('#cw').removeClass('btn-danger').addClass('btn-outline-secondary');
  }
  let exif = $('#exif');
  if(meta.preserve_exif) {
    if(exif.hasClass('btn-danger') == false) {
      exif.removeClass('btn-outline-secondary').addClass('btn-danger');
    }
  } else {
    if(exif.hasClass('btn-outline-secondary') == false) {
      exif.removeClass('btn-danger').addClass('btn-outline-secondary');
    }
  }
}

$(document).on('change', '.file-input', function(e) {
  let io = document.querySelector('.file-input');
  Array.prototype.forEach.call(io.files, function(io, i) {
    if(pixelfed.uploader.ids.length + i >= pixelfed.uploader.limit) {
      let el = $('#addMedia');
      el.remove();
      return;
    }
    let type = io.type;
    let acceptedMimes = pixelfed.uploader.acceptedMimes.split(',');
    let validated = $.inArray(type, acceptedMimes);
    if(validated == -1) {
      swal('Invalid File Type', 'The file you are trying to add is not a valid mime type. Please upload a '+pixelfed.uploader.acceptedMimes+' only.', 'error');
      return;
    }
    if($('.welcome-text').hasClass('d-none') == false) {
      $('.welcome-text').addClass('d-none');
    }
    $('.composeLoader').removeClass('d-none');
    let form = new FormData();
    form.append('file', io);

    let config = {
      onUploadProgress: function(progressEvent) {
        var percentCompleted = Math.round( (progressEvent.loaded * 100) / progressEvent.total );
      }
    };

    axios.post('/api/v1/media', form, config)
    .then(function(e) {
      pixelfed.uploader.ids.push(e.data.id);
      let meta = {
        'id': e.data.id,
        'cursor': pixelfed.uploader.ids.length,
        'cw': false,
        'alt': null,
        'filter': null,
        'license': null,
        'preserve_exif': false,
      };
      pixelfed.uploader.meta.push(meta);
      pixelfed.uploader.media.push(e.data);
      $('.composeLoader').addClass('d-none');
      pixelfed.uploader.addPreview(e.data);
      pixelfed.uploader.paginate(e.data);
      if(pixelfed.uploader.ids.length >= pixelfed.uploader.limit) {
        let el = $('#addMedia');
        el.remove();
      }
    }).catch(function(e) {
      swal('Oops, something went wrong!', 'An unexpected error occurred.', 'error');
    });
    io.value = null;
  });
});

$(document).on('click', '#addMedia', function(e) {
  e.preventDefault();
  let el = $(this);
  el.attr('disabled', '');
  if(pixelfed.uploader.ids.length >= pixelfed.uploader.limit) {
    el.remove();
    return;
  }
  let fi = $('.file-input');
  fi.trigger('click');
  el.blur();
  el.removeAttr('disabled');
});

$(document).on('change', '#filterSelectDropdown', function() {
    let el = $(this);
    let filter = el.val();
    let cursor = pixelfed.uploader.cursor;
    let oldFilter = pixelfed.uploader.meta[cursor - 1];
    pixelfed.uploader.updateFilter(cursor, oldFilter, filter);
    pixelfed.uploader.meta[cursor - 1].filter = filter;
});

$(document).on('click', '#cw', function() {
  let el = $(this);
  let cursor = pixelfed.uploader.cursor;
  let meta = pixelfed.uploader.meta[cursor - 1];
  let title = 'A content warning or nsfw warning is required for certain content.';
  if(el.hasClass('btn-outline-secondary')) {
    el.find('i.far').addClass('fa-eye-slash').removeClass('fa-eye');
    el.removeClass('btn-outline-secondary');
    el.addClass('btn-danger');
    el.attr('data-original-title', 'Status: CW/NSFW Enabled. '+title);
    meta.cw = true;
  } else {
    el.find('i.far').addClass('fa-eye').removeClass('fa-eye-slash');
    el.removeClass('btn-danger');
    el.addClass('btn-outline-secondary');
    el.attr('data-original-title', 'Status: CW/NSFW Disabled. '+title);
    meta.cw = false;
 }
 el.blur();
});

$(document).on('click', '#alt', function() {
  swal("Add a media description:", {
    content: {
      element: "input",
      attributes: {
        placeholder: "A good description helps visually impaired users.",
        value: pixelfed.uploader.meta[pixelfed.uploader.cursor - 1].alt,
      },
    },
  })
  .then((value) => {
    let cursor = pixelfed.uploader.cursor - 1;
    if(value) {
      pixelfed.uploader.meta[cursor].alt = value;
    }
  });
});

$(document).on('click', '#license', function() {
  swal("Add a license:", {
    content: {
      element: "input",
      attributes: {
        placeholder: "Add an optional license to your photo or video.",
        value: pixelfed.uploader.meta[pixelfed.uploader.cursor - 1].license,
      },
    },
  })
  .then((value) => {
    let cursor = pixelfed.uploader.cursor - 1;
    if(value) {
      pixelfed.uploader.meta[cursor].license = value;
    }
  });
});

{{-- $(document).on('click', '#exif', function() {
  let el = $(this);
  let cursor = pixelfed.uploader.cursor;
  let meta = pixelfed.uploader.meta[cursor - 1];
  if(el.hasClass('btn-outline-secondary')) {
    el.removeClass('btn-outline-secondary');
    el.addClass('btn-outline-danger');
    meta.preserve_exif = true;
  } else {
    el.removeClass('btn-outline-danger');
    el.addClass('btn-outline-secondary');
    meta.preserve_exif = false;
 }
 el.blur();
}); --}}

$(document).on('click', '.preview-thumbs img', function(e) {
  e.preventDefault();
  let el = $(this);
  let id = el.data('cursor');
  pixelfed.uploader.goto(id);
})

$(document).on('click', '#create', function(e) {
  e.preventDefault();
  let el = $(this);
  el.attr('disabled', '');
  $('.composeLoader').removeClass('d-none');
  let data = {
    media: pixelfed.uploader.meta,
    caption: $('.caption textarea').val(),
    visibility: pixelfed.uploader.visibility,
  };
  axios.post('/api/local/status/compose', data)
    .then(res => {
      let data = res.data;
      window.location.href = data;
    }).catch(err => {
      swal('Oops, something went wrong!', 'An unexpected error occurred.', 'error');
    });
})

$('.visibility .dropdown-item').on('click', function(e) {
  e.preventDefault();
  let el = $(this);
  $('.visibility .dropdown-item').each(function(e) {
    $(this).removeClass('active');
  });
  el.addClass('active');
  $('#visibility').text(el.data('title'));
  pixelfed.uploader.visibility = el.data('id');
});

$('#composeMenu').on('click', function(e) {
  e.preventDefault();
  swal(
    'Experimental Feature',
    'We’re still working on this feature.',
    'info'
  );
});

{{-- $('#crop').on('click', function(e) {
  e.preventDefault();
  $(this).blur();
  let mode = pixelfed.uploader.cropmode;
  if(mode == false) {
    $('.preview-meta').addClass('mt-5');
    $(this).removeClass('btn-outline-secondary').addClass('btn-outline-success');
    $('.preview-card img').croppie({
     viewport: {
          width: 400,
          height: 300
      }
    });
    pixelfed.uploader.cropmode = true;
  } else {
    $('.preview-meta').removeClass('mt-5');
    $(this).addClass('btn-outline-secondary').removeClass('btn-outline-success');
    $('.preview-card img').croppie('destroy');
    pixelfed.uploader.cropmode = false;
    pixelfed.uploader.croppie = false;
  }
}); --}}

</script>
@endpush
