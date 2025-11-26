(function () {
	tinymce.PluginManager.add(
		"starg_email_shortcode_buttons",
		function (editor, url) {
			editor.addButton("starg_email_shortcode_buttons", {
				type: 'menubutton',
				text: 'Shortcodes',
				icon: false,
				menu: [
					{
						text: "email",
						icon: "link",
						onclick: function () {
							editor.insertContent("[starg_email] ... [/starg_email]");
						},
					},
				]
			});
		}
	);
})();
