document.addEventListener('DOMContentLoaded', () => {

    // 1. Mobile Navigation Menu Toggle
    const menuIcon = document.querySelector('#menu-icon');
    const navbar = document.querySelector('.navbar') || document.querySelector('#nav-links');

    if (menuIcon && navbar) {
        menuIcon.onclick = () => {
            menuIcon.classList.toggle('bx-x');
            navbar.classList.toggle('active');
        };
    }

    // 2. Scroll Handler: Active Navigation Links & Mobile Menu Dismissal
    window.onscroll = () => {
        if (menuIcon && navbar) {
            menuIcon.classList.remove('bx-x');
            navbar.classList.remove('active');
        }

        const sections = document.querySelectorAll('section');
        const navLinks = document.querySelectorAll('header nav a');

        sections.forEach(sec => {
            let top = window.scrollY;
            let offset = sec.offsetTop - 150;
            let height = sec.offsetHeight;
            let id = sec.getAttribute('id');

            if (top >= offset && top < offset + height) {
                navLinks.forEach(link => {
                    link.classList.remove('active');
                    const targetLink = document.querySelector('header nav a[href*=' + id + ']');
                    if (targetLink) targetLink.classList.add('active');
                });
            }
        });
    };

    // 3. Typed.js Subtitle Animation
    if (document.querySelector('.multiple-text')) {
        new Typed('.multiple-text', {
            strings: ['Software Engineering Student', 'Web Developer', 'System Architect', 'Game Developer'],
            typeSpeed: 70,
            backSpeed: 70,
            backDelay: 1200,
            loop: true
        });
    }

    // 4. About Modal Handler (Safe Guarded)
    const modal = document.getElementById('about-modal');
    const openModalBtn = document.getElementById('open-modal-btn');
    const closeModalBtn = document.getElementById('close-modal-btn');

    if (modal && openModalBtn && closeModalBtn) {
        openModalBtn.onclick = () => modal.style.display = 'flex';
        closeModalBtn.onclick = () => modal.style.display = 'none';
        window.onclick = (e) => { if (e.target === modal) modal.style.display = 'none'; };
    }

    // 5. Dark / Light Theme Switching Logic
    const themeToggleBtn = document.getElementById('theme-toggle');
    const themeIcon = document.getElementById('theme-icon');
    const themeText = document.getElementById('theme-text');

    const currentTheme = localStorage.getItem('portfolio-theme') || 'dark';

    if (currentTheme === 'light') {
        document.body.classList.add('light-mode');
        if (themeIcon) themeIcon.textContent = '☀️';
        if (themeText) themeText.textContent = 'Light';
    }

    themeToggleBtn?.addEventListener('click', () => {
        document.body.classList.toggle('light-mode');
        const isLight = document.body.classList.contains('light-mode');

        localStorage.setItem('portfolio-theme', isLight ? 'light' : 'dark');
        if (themeIcon) themeIcon.textContent = isLight ? '☀️' : '🌙';
        if (themeText) themeText.textContent = isLight ? 'Light' : 'Dark';
    });

    /*// 6. Live Contact Form AJAX Submission
    const contactForm = document.getElementById('contact-form');
    const formStatus = document.getElementById('form-status');
    const submitBtn = document.getElementById('form-submit-btn');

    if (contactForm) {
        contactForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Sending...';
            }
            if (formStatus) formStatus.textContent = '';

            const formData = new FormData(contactForm);

            try {
                const response = await fetch(contactForm.action, {
                    method: 'POST',
                    body: formData,
                    headers: { 'Accept': 'application/json' }
                });

                if (response.ok) {
                    if (formStatus) {
                        formStatus.style.color = '#10B981';
                        formStatus.textContent = '✓ Message sent successfully! I will get back to you soon.';
                    }
                    contactForm.reset();
                } else {
                    throw new Error('Server response error');
                }
            } catch (error) {
                if (formStatus) {
                    formStatus.style.color = '#EF4444';
                    formStatus.textContent = '❌ Failed to send message. Please try again.';
                }
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Send Message';
                }
            }
        });
    }*/

    // 7. Interactive Filter & URL Sync System
    const filterButtons = document.querySelectorAll('.filter-btn');
    const projectCards = document.querySelectorAll('.project-card');

    function applyFilter(category) {
        filterButtons.forEach(btn => {
            const btnCategory = btn.getAttribute('data-filter');
            if (btnCategory === category) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });

        projectCards.forEach(card => {
            const cardCategory = card.getAttribute('data-category');
            if (category === 'all' || cardCategory === category) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }

    filterButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            const selectedCategory = button.getAttribute('data-filter');

            if (selectedCategory) {
                e.preventDefault();
                history.pushState(null, null, `#${selectedCategory}`);
                applyFilter(selectedCategory);
            }
        });
    });

    const initialHash = window.location.hash.replace('#', '');
    if (initialHash && document.querySelector(`.filter-btn[data-filter="${initialHash}"]`)) {
        applyFilter(initialHash);
    }

});