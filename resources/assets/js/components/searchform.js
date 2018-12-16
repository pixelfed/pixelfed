$(document).ready(function() {

  let queryEngine = new Bloodhound({
    datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
    queryTokenizer: Bloodhound.tokenizers.whitespace,
    remote: {
      url: process.env.MIX_API_SEARCH + '/%QUERY%',
      wildcard: '%QUERY%'
    }
  });

  $('.search-form .search-form-input').typeahead(null, {
    name: 'search',
    display: 'value',
    source: queryEngine,
    limit: 40,
    templates: {
      empty: [
        '<div class="alert alert-info mb-0 font-weight-bold">',
          'No Results Found',
        '</div>'
      ].join('\n'),
      suggestion: function(data) {
        let type = data.type;
        let res = false;
        switch(type) {
          case 'hashtag':
            res = '<a href="'+data.url+'?src=search">' +
            '<div class="media d-flex align-items-center">' +
            '<div class="mr-3 h4 text-muted"><span class="fas fa-hashtag"></span></div>' +
            '<div class="media-body text-truncate">' +
            '<p class="mt-0 mb-0 font-weight-bold">'+data.value+'</p>' +
            '<p class="text-muted mb-0">'+data.count+' posts</p>' +
            '</div>' +
            '</div>' +
            '</a>';
          break;
          case 'profile':
            res = '<a href="'+data.url+'?src=search">' +
            '<div class="media d-flex align-items-center">' +
            '<div class="mr-3 h4 text-muted"><span class="far fa-user"></span></div>' +
            '<div class="media-body text-truncate">' +
            '<p class="mt-0 mb-0 font-weight-bold">'+data.name+'</p>' +
            '<p class="text-muted mb-0">'+data.value+'</p>' +
            '</div>' +
            '</div>' +
            '</a>';
          break;
          case 'status':
            res = '<a href="'+data.url+'?src=search">' +
            '<div class="media d-flex align-items-center">' +
            '<div class="mr-3 h4 text-muted"><img src="'+data.thumb+'" width="32px"></div>' +
            '<div class="media-body text-truncate">' +
            '<p class="mt-0 mb-0 font-weight-bold">'+data.name+'</p>' +
            '<p class="text-muted mb-0 small">'+data.value+'</p>' +
            '</div>' +
            '</div>' +
            '</a>';
          break;
          default:
            res = false;
          break;
        }
        if(res !== false) {
          return res;
        }
      }
    }
  });

});