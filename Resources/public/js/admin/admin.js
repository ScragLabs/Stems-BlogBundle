$(document).ready(function() {

	/**
	 * Update the blog slug
	 */
	var admin_post_type_slug = $('#admin_post_type_slug');
	var admin_post_type_excerpt = $('#admin_post_type_excerpt');
	var admin_post_type_title = $('#admin_post_type_title');
	
	function updateBlogPostSlug() {
		admin_post_type_slug.val(slugify(admin_post_type_title.val()+' '+admin_post_type_excerpt.val()));
	}

	admin_post_type_title.on('keyup', function(e){
		updateBlogPostSlug();
	});

	admin_post_type_excerpt.on('keyup', function(e){
		updateBlogPostSlug();
	});

	/** 
	 * Add section to blog post
	 */
	$('.available-sections').on('click', 'a', function(e){
		e.preventDefault();
		var originator =  $(this);
		var buttonText = originator.html();
		var buttonWidth = originator.css('width');

		originator.css('width', buttonWidth);
		originator.html('<i class="fa fa-circle-o-notch fa-spin"></i>');

		$.get('/admin/blog/rest/add-section-type/'+$(this).data('type-id')).done(function(data) {
			// $('.layout-editor').append(data.html);
			var section = $(data.html);
			section.draggable({
                    grid: [ 480, 15 ]
                });
			$('#packery-editor').append(section); //.packery('appended', section);
			originator.html(buttonText);
            section.css('top', $('.layout-editor').css('height'));
            section.find('.section-y').val($('.layout-editor').css('height'));
            updateLayoutEditorHeight();
		});
	});

	/** 
	 * Remove section from blog post
	 */
	$('.admin-editor').on('click', 'button.remove-section',  function(e){
		e.preventDefault();
		var button = $(this);
		confirm('Are you sure you want to delete this section?');

		$.get('/admin/blog/rest/remove-section/'+button.data('id')).done(function(data) {
			if (data.status == 'success') {
				button.parent().parent().remove();
			} else {
				createFlashMessage(data.status, data.message);
			}
		});
	});

	/** 
	 * Update the image on the blog sections
	 */
	$('.admin-blog-editor').on('change', '.feature-image', function(e){
		var header_section = $('.section-blog-header .image');
		if (header_section.length) {
			header_section.css('background-image', $(this).css('background-image'));
		} else {
			var image = $('<div class="image" style="background-image: url('+$(this).css('background-image')+')></div>');
			$('.section-blog-header').prepend(image);
			console.log('test');
		}
	});

	/**
	 * Update headings on update
	 */
	$('.layout-editor').on('keyup', '.section-heading textarea', function() {
		$(this).siblings('h6').html($(this).val());
	});

	/**
	 * Update text sections on update
	 */
	$('.layout-editor').on('keyup', '.section-text textarea', function() {
		$(this).siblings('p').html($(this).val());
	});

});