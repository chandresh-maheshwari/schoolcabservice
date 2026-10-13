(function (window) {
    'use strict';

    let pendingAlerts = Promise.resolve();

    window.showAppAlert = function (message) {
        const text = String(message === undefined ? '' : message);
        pendingAlerts = pendingAlerts.then(function () {
            if (window.Swal && typeof window.Swal.fire === 'function') {
                return window.Swal.fire({
                    icon: 'warning',
                    title: 'Notice',
                    text: text,
                    confirmButtonText: 'OK',
                    allowOutsideClick: false
                });
            }

            return window.swal({
                icon: 'warning',
                title: 'Notice',
                text: text,
                button: 'OK',
                closeOnClickOutside: false
            });
        }).catch(function (error) {
            console.error('Unable to show application alert:', error);
        });

        return pendingAlerts;
    };

    // Keep legacy alert calls using the same application popup.
    window.alert = function (message) {
        window.showAppAlert(message);
    };
})(window);
