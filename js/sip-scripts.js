document.addEventListener('DOMContentLoaded', () => {
	const $rootEl = document.documentElement;
	function openModal($el) {
		$el.classList.add('is-active');
		$rootEl.classList.add( 'is-clipped' );
	}

	function closeModal($el) {
		$el.classList.remove('is-active');
		$rootEl.classList.remove( 'is-clipped' );
	}

	function closeAllModals() {
		(document.querySelectorAll('.modal') || []).forEach(($modal) => {
			closeModal($modal);
		});
	}

	// Add a click event on buttons to open a specific modal
	(document.querySelectorAll('.js-modal-trigger') || []).forEach(($trigger) => {
		const modal = $trigger.dataset.modalId;
		const $target = document.getElementById(modal);

		$trigger.addEventListener('click', () => {
			openModal($target);
		});
	});

	// Add a click event on various child elements to close the parent modal
	(document.querySelectorAll('.modal-background, .modal-close, .modal-card-head .delete, .modal-card-foot .button') || []).forEach(($close) => {
		const $target = $close.closest('.modal');

		$close.addEventListener('click', () => {
			closeModal($target);
		});
	});

	// Add a keyboard event to close all modals
	document.addEventListener('keydown', (event) => {
		if(event.key === "Escape") {
			closeAllModals();
		}
	});

	(document.querySelectorAll(".js-close-notification.delete") || []).forEach(($delete) => {
		const $notification = $delete.closest( '.sip-notification-message' );

		$delete.addEventListener("click", () => {
			$notification.parentNode.removeChild($notification);
		});
	});
});
