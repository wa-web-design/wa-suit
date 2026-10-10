document.addEventListener('DOMContentLoaded', function () {
	const selectAll = document.getElementById('wa-suit-enable-all');
	const moduleCheckboxes = document.querySelectorAll('[data-wa-suit-module]');

	if (!selectAll) {
		return;
	}

	const updateSelectAll = function () {
		const checkedCount = document.querySelectorAll('[data-wa-suit-module]:checked').length;
		selectAll.checked = moduleCheckboxes.length > 0 && checkedCount === moduleCheckboxes.length;
		selectAll.indeterminate = checkedCount > 0 && checkedCount < moduleCheckboxes.length;
	};

	selectAll.addEventListener('change', function () {
		moduleCheckboxes.forEach(function (checkbox) {
			checkbox.checked = selectAll.checked;
		});
		selectAll.indeterminate = false;
	});

	moduleCheckboxes.forEach(function (checkbox) {
		checkbox.addEventListener('change', updateSelectAll);
	});

	updateSelectAll();
});
