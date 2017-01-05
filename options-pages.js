
	jQuery(document).ready(function($){
		//console.log($('li[class*="acfop-fontawesome-"]'));
		$('li[class*="acfop-fontawesome-"]').each(function(index, element) {
			//console.log($(element));
			var $classes = element.className.split(/\s+/);
			var $class = '';
			for (i=0; i<$classes.length; i++) {
				if ($classes[i].substr(0, 18) == 'acfop-fontawesome-') {
					$class = $classes[i];
					break;
				}
			}
			$class = $class.substr(18);
			$classes = $class.split(/_/);
			//console.log($classes);
			$class = $class.replace(/_/, ' ');
			//console.log($class);
			$(element).find('.wp-menu-image').each(function(index, element) {
				$(element).empty();
				$(element).removeClass('dashicons-before');
				$(element).append('<i class="'+$class+'" style="font-size:20px;margin:8px 0;color:rgba(240,245,250,0.6);"></i>');
				$(element).append('<br />');
				for (i=0; i<$classes.length; i++) {
					//$(element).addClass($classes[i]);
				}
			});
		});
		
	});