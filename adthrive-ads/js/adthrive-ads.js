function load_adthrive_terms(taxonomy) {
	return function (query, callback) {
		jQuery.ajax({
			url: ajaxurl,
			type: 'GET',
			data: {
				action: 'adthrive_terms',
				taxonomy: taxonomy,
				query: query,
			},
			error: function () {
				callback();
			},
			success: callback,
		});
	};
}

jQuery(document).ready(function ($) {
	$('#disabled_tags').selectize({
		preload: true,
		load: load_adthrive_terms('post_tag'),
	});

	$('#disabled_categories').selectize({
		preload: true,
		load: load_adthrive_terms('category'),
	});
});
