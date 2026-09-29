(() => {
	'use strict';

	const setupAssessment = (button) => {
		const dialogId = button.getAttribute('data-kaanbal-assessment-open');
		const dialog = dialogId ? document.getElementById(dialogId) : null;

		if (!(dialog instanceof HTMLDialogElement)) {
			return;
		}

		const closeButton = dialog.querySelector('[data-kaanbal-assessment-close]');
		const close = () => {
			if (dialog.open) {
				dialog.close();
			}
		};

		closeButton?.addEventListener('click', close);
		dialog.addEventListener('click', (event) => {
			if (event.target === dialog) {
				close();
			}
		});
		dialog.addEventListener('close', () => button.focus());
		button.addEventListener('click', () => dialog.showModal());
	};

	document.querySelectorAll('[data-kaanbal-assessment-open]').forEach((button) => {
		if (button instanceof HTMLButtonElement) {
			setupAssessment(button);
		}
	});
})();
