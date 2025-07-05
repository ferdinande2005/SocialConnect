document.addEventListener('DOMContentLoaded', function() {
    if (!sessionStorage.getItem('csrf_token')) {
        window.location.href = '../../index.html';
    }
});