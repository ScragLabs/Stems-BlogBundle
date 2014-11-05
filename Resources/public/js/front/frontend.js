$(document).ready(function() {

	/**
	 * Show/hide blog comment form
	 */
	$('body').on('click', '.article .comments a.add-comment', function() {
		var commentForm = $(this).parent().parent().children('.add-comment-form');
		if (commentForm.length) {
			commentForm.slideToggle();
		}
	});

});