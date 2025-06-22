// Switch entre login et inscription
function switchForm() {
    document.getElementById('formContainer').classList.toggle('switch');
}

// Connexion
document.getElementById('loginForm').addEventListener('submit', function(e) {
    // empêche le rechargement de la page
    e.preventDefault();

    const formData = {
    email: this.email.value,
    password: this.password.value
    };

    fetch('http://localhost/ReseauSocial/api/login_register.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify(formData)
    })
    .then(res => res.json())
    .then(data => {
    const msg = document.getElementById('loginMessage');
    if (data.status === 'success') {
        msg.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
        sessionStorage.setItem('user', JSON.stringify(data.user));
        setTimeout(() => window.location.href = 'vues/clients/home.html', 800);
    } else {
        msg.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
    }
    });
});

// Inscription
document.getElementById('registerForm').addEventListener('submit', function(e) {
    // empêche le rechargement de la page
    e.preventDefault();

    const formData = {
    firstname: this.firstname.value,
    lastname: this.lastname.value,
    email: this.email.value,
    password: this.password.value
    };

    fetch('http://localhost/ReseauSocial/api/login_register.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify(formData)
    })
    .then(res => res.json())
    .then(data => {
    const msg = document.getElementById('registerMessage');
    if (data.status === 'success') {
        msg.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
        this.reset();
        setTimeout(() => switchForm(), 1500);
    } else {
        msg.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
    }
    });
});
