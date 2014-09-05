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
			$('ul.sections').append('<li>'+data.html+'</li>');
			originator.html(buttonText);
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
				button.parent().remove();
			} else {
				createFlashMessage(data.status, data.message);
			}
		});
	});

});