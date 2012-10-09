jQuery(document).ready(function() {
	
		jQuery('.category').change(function() {
			jQuery("option:selected",this).attr("selected","selected").css('font-weight','bold');
		});
		jQuery('.category').each(function(){   
			var currentCat = jQuery(this).parent().attr('class');
			jQuery(this).val(currentCat);
		});
});



		function set_input_values(num) {
			


			var cat = jQuery('#category-' + num + ' option:selected').val();
			var h = document.getElementById('href-' + num);
			
			h.href = h.href + '&notice=' + document.getElementById('notice-' + num).value + '&active=' + document.getElementById('active-' + num).checked + '&valid=' + document.getElementById('valid-' + num).value + '&day=' + document.getElementById('day-' + num).value + '&month=' + document.getElementById('month-' + num).value + '&year=' + document.getElementById('year-' + num).value + '&category=' + cat;
		}