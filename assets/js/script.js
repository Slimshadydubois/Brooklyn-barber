document.addEventListener('DOMContentLoaded', () => {
    // Mobile Menu Toggle
    const hamburger = document.getElementById('hamburger');
    const navContent = document.getElementById('navContent');
    const navLinks = document.querySelectorAll('.nav-links a, .nav-btns a');

    if (hamburger && navContent) {
        hamburger.addEventListener('click', () => {
            hamburger.classList.toggle('active');
            navContent.classList.toggle('active');
            // Prevent scrolling when menu is open
            document.body.style.overflow = navContent.classList.contains('active') ? 'hidden' : 'initial';
        });

        // Close menu when clicking a link
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                hamburger.classList.remove('active');
                navContent.classList.remove('active');
                document.body.style.overflow = 'initial';
            });
        });

        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!navContent.contains(e.target) && !hamburger.contains(e.target) && navContent.classList.contains('active')) {
                hamburger.classList.remove('active');
                navContent.classList.remove('active');
                document.body.style.overflow = 'initial';
            }
        });
    }

    // Hero Carousel
    const slides = document.querySelectorAll('.slide');
    let currentSlide = 0;

    function nextSlide() {
        slides[currentSlide].classList.remove('active');
        currentSlide = (currentSlide + 1) % slides.length;
        slides[currentSlide].classList.add('active');
    }

    if (slides.length > 0) {
        setInterval(nextSlide, 5000);
    }

    // Services Carousel
    const servicesGrid = document.getElementById('servicesGrid');
    const servicesPrev = document.getElementById('servicesPrev');
    const servicesNext = document.getElementById('servicesNext');
    
    if (servicesGrid && servicesGrid.children.length > 0) {
        const items = Array.from(servicesGrid.children);
        const originalItemsCount = items.length;
        
        // Clone items for seamless infinite loop (clone 3 to cover desktop view)
        const cloneCount = 3;
        
        // Append clones of the first few items
        for (let i = 0; i < cloneCount; i++) {
            const clone = items[i % originalItemsCount].cloneNode(true);
            servicesGrid.appendChild(clone);
        }
        
        // Prepend clones of the last few items
        for (let i = 0; i < cloneCount; i++) {
            const clone = items[(originalItemsCount - 1 - i) % originalItemsCount].cloneNode(true);
            servicesGrid.insertBefore(clone, servicesGrid.firstChild);
        }

        let serviceIndex = cloneCount; // Start at the first original item
        let isTransitioning = false;

        function updateServicesCarousel(animate = true) {
            if (!servicesGrid.children.length) return;
            
            if (animate) {
                servicesGrid.style.transition = 'transform 0.5s cubic-bezier(0.4, 0, 0.2, 1)';
            } else {
                servicesGrid.style.transition = 'none';
            }

            const gap = 30; // Matches CSS gap
            const cardWidth = servicesGrid.children[0].offsetWidth;
            const moveAmount = (cardWidth + gap) * serviceIndex;
            
            servicesGrid.style.transform = `translateX(-${moveAmount}px)`;
        }

        servicesNext.addEventListener('click', () => {
            if (isTransitioning) return;
            isTransitioning = true;
            serviceIndex++;
            updateServicesCarousel(true);
        });

        servicesPrev.addEventListener('click', () => {
            if (isTransitioning) return;
            isTransitioning = true;
            serviceIndex--;
            updateServicesCarousel(true);
        });

        servicesGrid.addEventListener('transitionend', () => {
            isTransitioning = false;
            
            // Check if we reached the appended clones
            if (serviceIndex >= originalItemsCount + cloneCount) {
                serviceIndex = cloneCount;
                updateServicesCarousel(false);
            } 
            // Check if we reached the prepended clones
            else if (serviceIndex < cloneCount) {
                serviceIndex = originalItemsCount + serviceIndex;
                updateServicesCarousel(false);
            }
        });

        // Initial setup and responsiveness
        const initCarousel = () => {
            if (!servicesGrid.children.length) return;
            // Force a layout recalculation before setting the position
            void servicesGrid.offsetWidth; 
            updateServicesCarousel(false);
        };

        // Run on load, resize, and with a small delay to be safe
        window.addEventListener('load', initCarousel);
        window.addEventListener('resize', initCarousel);
        
        // Use a more robust check for initialization
        if (document.readyState === 'complete') {
            initCarousel();
        } else {
            window.addEventListener('load', initCarousel);
        }
        
        // MutationObserver to handle dynamic content if needed, 
        // but for now, a simple timeout should suffice for PHP-rendered content
        setTimeout(initCarousel, 250);
    }

    // Smooth Scroll for Nav Links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                const navHeight = document.querySelector('nav').offsetHeight;
                window.scrollTo({
                    top: targetElement.offsetTop - (window.innerWidth <= 768 ? 60 : 80),
                    behavior: 'smooth'
                });
            }
        });
    });

    // Navbar scroll effect
    window.addEventListener('scroll', () => {
        const nav = document.querySelector('nav');
        if (window.scrollY > 50) {
            nav.style.padding = (window.innerWidth <= 768) ? '0.8rem 5%' : '1rem 5%';
        } else {
            nav.style.padding = (window.innerWidth <= 768) ? '1rem 5%' : '1.5rem 5%';
        }
    });
});
