window.notify = function(type, message, title = '') {
    try {
        switch(type) {
            case 'success':
                toastr.success(message, title || 'Success');
                break;
            case 'error':
                toastr.error(message, title || 'Error');
                break;
            case 'info':
                toastr.info(message, title || 'Info');
                break;
            case 'warning':
                toastr.warning(message, title || 'Warning');
                break;
            default:
                toastr.info(message, title);
        }
    } catch (e) {
        Swal.fire({
            icon: type,
            title: title || type.charAt(0).toUpperCase() + type.slice(1),
            text: message
        });
    }
}
