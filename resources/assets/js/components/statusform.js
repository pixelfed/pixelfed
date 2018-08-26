$(document).ready(function() {

    pixelfed.create = {};
    pixelfed.filters = {};
    pixelfed.create.hasGeneratedSelect = false;
    pixelfed.create.selectedFilter = false;
    pixelfed.create.currentFilterName = false;
    pixelfed.create.currentFilterClass = false;

    pixelfed.filters.list = [
        ['1977','filter-1977'], 
        ['Aden','filter-aden'], 
        ['Amaro','filter-amaro'], 
        ['Ashby','filter-ashby'], 
        ['Brannan','filter-brannan'], 
        ['Brooklyn','filter-brooklyn'], 
        ['Charmes','filter-charmes'], 
        ['Clarendon','filter-clarendon'], 
        ['Crema','filter-crema'], 
        ['Dogpatch','filter-dogpatch'], 
        ['Earlybird','filter-earlybird'], 
        ['Gingham','filter-gingham'], 
        ['Ginza','filter-ginza'], 
        ['Hefe','filter-hefe'], 
        ['Helena','filter-helena'], 
        ['Hudson','filter-hudson'], 
        ['Inkwell','filter-inkwell'], 
        ['Kelvin','filter-kelvin'], 
        ['Kuno','filter-juno'], 
        ['Lark','filter-lark'], 
        ['Lo-Fi','filter-lofi'], 
        ['Ludwig','filter-ludwig'], 
        ['Maven','filter-maven'], 
        ['Mayfair','filter-mayfair'], 
        ['Moon','filter-moon'], 
        ['Nashville','filter-nashville'], 
        ['Perpetua','filter-perpetua'], 
        ['Poprocket','filter-poprocket'], 
        ['Reyes','filter-reyes'], 
        ['Rise','filter-rise'], 
        ['Sierra','filter-sierra'], 
        ['Skyline','filter-skyline'], 
        ['Slumber','filter-slumber'], 
        ['Stinson','filter-stinson'], 
        ['Sutro','filter-sutro'], 
        ['Toaster','filter-toaster'], 
        ['Valencia','filter-valencia'], 
        ['Vesper','filter-vesper'], 
        ['Walden','filter-walden'], 
        ['Willow','filter-willow'], 
        ['X-Pro II','filter-xpro-ii']
    ];

    function previewImage(input) {
        if (input.files && input.files[0]) {
          var reader = new FileReader();
          reader.onload = function(e) {
            $('.filterPreview').attr('src', e.target.result);
          }
          reader.readAsDataURL(input.files[0]);
        }
    }

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

    $(document).on('change', '#fileInput', function() {
        previewImage(this);
        $('#statusForm .form-filters.d-none').removeClass('d-none');
        $('#statusForm .form-preview.d-none').removeClass('d-none');
        $('#statusForm #collapsePreview').collapse('show');
        if(!pixelfed.create.hasGeneratedSelect) {
          generateFilterSelect();
        }
    });

    $(document).on('change', '#filterSelectDropdown', function() {
        let el = $(this);
        let filter = el.val();
        let oldFilter = pixelfed.create.currentFilterClass;
        if(filter == 'none') {
            $('input[name=filter_class]').val('');
            $('input[name=filter_name]').val('');
            $('.filterContainer').removeClass(oldFilter);
            pixelfed.create.currentFilterClass = false;
            pixelfed.create.currentFilterName = 'None';
            $('.form-group.form-preview .form-text').text('Current Filter: No filter selected');
            return;
        } else {
            $('.filterContainer').removeClass(oldFilter).addClass(filter);
            pixelfed.create.currentFilterClass = filter;
            pixelfed.create.currentFilterName = el.find(':selected').text();
            $('.form-group.form-preview .form-text').text('Current Filter: ' + pixelfed.create.currentFilterName);
            $('input[name=filter_class]').val(pixelfed.create.currentFilterClass);
            $('input[name=filter_name]').val(pixelfed.create.currentFilterName);
            return;
        }
    });

    $(document).on('keyup keydown', '#statusForm textarea[name=caption]', function() {
      const el = $(this);
      const len = el.val().length;
      const limit = el.data('limit');
      if(len > limit) {
        const diff = limit - len;
        $('#statusForm .caption-counter').text(diff).addClass('text-danger');
      } else {
          $('#statusForm .caption-counter').text(len).removeClass('text-danger');
      }
    });

    $(document).on('focus', '#statusForm textarea[name=caption]', function() {
      const el = $(this);
      el.attr('rows', '3');
    });
});