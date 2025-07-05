// Navigation JavaScript (No Animations)
document.addEventListener('DOMContentLoaded', function() {
    const navbar = document.querySelector('.navbar');
    const navLinks = document.querySelectorAll('.nav-link');
    const navbarBrand = document.querySelector('.navbar-brand');
    const navbarToggler = document.querySelector('.navbar-toggler');
    const navbarCollapse = document.querySelector('.navbar-collapse');

    // Scroll effect for navbar
    function handleScroll() {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    }
    window.addEventListener('scroll', handleScroll);

    // Active link highlighting
    function setActiveLink() {
        const currentLocation = window.location.pathname;
        navLinks.forEach(link => {
            const linkPath = link.getAttribute('href');
            if (linkPath === currentLocation || 
                (currentLocation === '/consultancy/' && linkPath === 'index.php') ||
                (currentLocation === '/consultancy/index.php' && linkPath === 'index.php')) {
                link.parentElement.classList.add('active');
            } else {
                link.parentElement.classList.remove('active');
            }
        });
    }
    setActiveLink();

    // Enhanced hover effects for nav links
    navLinks.forEach(link => {
        link.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px) scale(1.05)';
            this.style.filter = 'brightness(1.1)';
        });
        link.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
            this.style.filter = 'brightness(1)';
        });
    });

    // Smooth scrolling for anchor links
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href.startsWith('#')) {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    const offsetTop = target.offsetTop - 80;
                    window.scrollTo({
                        top: offsetTop,
                        behavior: 'smooth'
                    });
                }
            }
        });
    });

    // Remove manual toggling of .show and .active
    // Use Bootstrap's collapse events to animate toggler icon
    if (navbarToggler && navbarCollapse) {
        navbarCollapse.addEventListener('show.bs.collapse', function() {
            navbarToggler.classList.add('active');
        });
        navbarCollapse.addEventListener('hide.bs.collapse', function() {
            navbarToggler.classList.remove('active');
        });
        // Removed navLinks.forEach click handler that closed the menu
    }

    // Enhanced navbar brand effects
    if (navbarBrand) {
        navbarBrand.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px) scale(1.05)';
            this.style.filter = 'drop-shadow(0 8px 16px rgba(52, 152, 219, 0.4))';
        });
        navbarBrand.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
            this.style.filter = 'drop-shadow(0 8px 16px rgba(52, 152, 219, 0.3))';
        });
    }

    // Initialize navbar state
    handleScroll();
});

// Add CSS for navbar states
const style = document.createElement('style');
style.textContent = `
    .navbar-toggler.active .navbar-toggler-icon {
        transform: rotate(45deg);
    }
    .navbar-toggler.active .navbar-toggler-icon::before {
        transform: rotate(90deg);
    }
    .navbar-toggler.active .navbar-toggler-icon::after {
        transform: rotate(90deg);
    }
`;
document.head.appendChild(style); 