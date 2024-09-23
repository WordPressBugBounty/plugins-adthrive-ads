jQuery('[data-slug="adthrive-ads"] .deactivate > a').click(function (event) {
	var message =
		'Deactivating this plugin will turn off your Raptive ads. This plugin must remain active in order for your ads to continue running and earning revenue. Are you sure you want to deactivate this plugin?';
	if (!window.confirm(message)) {
		event.preventDefault();
	}
});
