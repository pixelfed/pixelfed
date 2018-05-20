$(document).ready(function() {

  let queryEngine = new Bloodhound({
    datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
    queryTokenizer: Bloodhound.tokenizers.whitespace,
    remote: {
      url: process.env.MIX_API_SEARCH + '/%QUERY',
      wildcard: '%QUERY'
    }
  });

  $('.search-form .search-form-input').typeahead(null, {
    name: 'search',
    display: 'value',
    source: queryEngine,
    limit: 20,
    templates: {
      empty: [
        '<div class="alert alert-danger mb-0">',
          'unable to find any matches',
        '</div>'
      ].join('\n'),
      suggestion: function(data) {
        let type = data.type;
        let res = null;
        switch(type) {
          case 'hashtag':
            res = '<a href="'+data.url+'">' +
            '<div class="media d-flex align-items-center">' +
            '<div class="mr-3 h4 text-muted">#</div>' +
            '<div class="media-body text-truncate">' +
            '<p class="mt-0 mb-0 font-weight-bold">'+data.value+'</p>' +
            '<p class="text-muted mb-0">'+data.count+' posts</p>' +
            '</div>' +
            '</div>' +
            '</a>';
          break;
          case 'profile':
            res = '<a href="'+data.url+'">' +
            '<div class="media d-flex align-items-center">' +
            '<div class="mr-3 h4 text-muted"><span class="icon-user"></span></div>' +
            '<div class="media-body text-truncate">' +
            '<p class="mt-0 mb-0 font-weight-bold">'+data.name+'</p>' +
            '<p class="text-muted mb-0">'+data.value+'</p>' +
            '</div>' +
            '</div>' +
            '</a>';
          break;
        }
        return res;
      }
    }
  });

});