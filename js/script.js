$(function() {
	if ($('select[name=country] option').length < 2) $('select[name=country]').closest('.row').hide();
});
