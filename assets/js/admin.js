var firstClickOnProductLinksTab = true;
jQuery(document).ready(function($) {
	if ( typeof($.fn.DataTable) != 'undefined' && $('.productLinks_options').length)
	{
		$('.productLinks_options').on('click', function(){
			if (firstClickOnProductLinksTab)
			{
				firstClickOnProductLinksTab = false;
				setTimeout(function(){
					$('#productLinksTable').DataTable( {
						order: [[ 1, "desc" ]],
						colReorder: true,
						scrollX: true,
						pagingType: 'full',
						language: {
									paginate: {
										first:    '«',
										previous: '‹',
										next:     '›',
										last:     '»'
									}
								},
					} );
				}, 500);
			}
		});
			
	}
	
	jQuery('.single_add_to_cart_button.button.alt').on('click', function(e) {
		if (link_redirect_after_add_to_cart !== '') {
			e.preventDefault();
			window.open(link_redirect_after_add_to_cart, target_for_add_to_cart_redirect_link);
		}
	})
});