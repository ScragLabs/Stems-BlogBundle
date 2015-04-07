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
			var section = $(data.html);
			section.draggable({
                    grid: [ 480, 15 ]
                });
			$('.layout-editor').append(section);
			originator.html(buttonText);
            section.css('top', $('.layout-editor').css('height'));
            section.find('.section-y').val(section.position().top);
            updateLayoutEditorHeight();
		});
	});

	/** 
	 * Remove section from blog post
	 */
	$('.admin-editor').on('click', 'a.remove-section',  function(e){
		e.preventDefault();
		var button = $(this);
		confirm('Are you sure you want to delete this section?');

		$.get('/admin/blog/rest/remove-section/'+button.data('id')).done(function(data) {
			if (data.status == 'success') {
				button.closest('section').remove();
                updateLayoutEditorHeight();
			} else {
				createFlashMessage(data.status, data.message);
			}
		});
	});

    /**
     * Increase section column span
     */
    $('.admin-editor').on('click', 'a.column-span',  function(e){

        e.preventDefault();
        var className = 'col-' + $(this).text();
        $(this).closest('section').removeClass('col-1 col-2').addClass(className).find('.section-width').val($(this).text());

        updateLayoutEditorHeight();
    });

    /**
     * Change section heading style
     */
    $('.admin-editor').on('click', 'a.heading-style',  function(e){

        e.preventDefault();
        $(this).closest('section').children('.preview').children().last().changeElementType($(this).text());

        updateLayoutEditorHeight();
    });

    /**
     * Change section alignment
     */
    $('.admin-editor').on('click', 'a.alignment',  function(e){

        e.preventDefault();
        var className = 'alignment-' + $(this).data('value');
        $(this).closest('section').children('.preview').removeClass('alignment-left alignment-center alignment-right').addClass(className).find('.section-alignment').val($(this).text());
        $(this).closest('section').find('.selected-alignment > i').removeClass().addClass('fa fa-align-' + $(this).data('value'));
        updateLayoutEditorHeight();
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