(function() {
	if (!OCA.GpgMailer) {
		OCA.GpgMailer = {};
	}
})();

function setPrivateKey(keydata) {
	var url = OC.generateUrl('/apps/gpgmailer/key/upload');
	$.ajax({
		type: 'POST',
		url: url,
		data: {keydata:keydata},
		async: true
	}).done(function (response) {
		OC.Notification.showTemporary(
			t('gpgmailer', response['message'])
		);

	}).fail(function() {
		OC.Notification.showTemporary(
			t('gpgmailer', 'Failed to save Public Key')
		);
	});
}

$(document).ready(function() {
	$("#keydata").on('change', function() {
		setPrivateKey($("#keydata").val());
	});
});
