(function($) {
	"use strict";
	// Internationalization Support.
	const { __, _x, _n, _nx } = wp.i18n;
	$(window).load(function() {
		// Initiate dataTable
		$("#users-role-shortcode").dataTable({
			serverSide: true,
			// use Ajax
			ajax: {
				url: ajax_object.ajax_url,
				method: "POST",
				// Add custom form data.
				data: {
					action: "users-role-shortcode",
					users_role_shortcode_nonce: ajax_object.ajax_nonce
				},
				dataSrc: "dataTable"
			},
			columns: [{ data: "user_name" }, { data: "display_name" }, { data: "role" }],
			columnDefs: [
				// Remove order on Role column.
				{ targets: 2, orderable: false },
				// Add link to user_name column.
				{
					targets: 0,
					render: function(data, type, row, meta) {
						if (type === "display") {
							data = '<a href="/wp-admin/user-edit.php?user_id=' + encodeURIComponent(row.id) + '">' + data + "</a>";
						}
						return data;
					}
				}
			],
			// Run after the ajax has been returned and table has been setup.
			initComplete: function(settings, json) {
				// Add filtering to role column.
				var role_column = this.api().column(2);
				var select = $('<select><option value="">Role</option></select>')
					.appendTo($(role_column.header()).empty())
					.on("change", function() {
						var val = $.fn.dataTable.util.escapeRegex($(this).val());

						role_column.search(val ? "^" + val + "$" : "", true, false).draw();
					});
				// Add jason.dataRole to dropdown.
				if (typeof json.dataRoles !== "undefined") {
					select.append('<option value="">----------</option>');
					$.each(json.dataRoles, function(key, value) {
						select.append('<option value="' + value + '">' + value + "</option>");
					});
				}
			}
		});
	});
})(jQuery);
