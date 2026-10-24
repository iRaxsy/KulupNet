/* KulüpNet — Scroll-triggered Animations & Counter */
(function () {
    // --- IntersectionObserver for [data-animate] ---
    const animateEls = document.querySelectorAll('[data-animate]');
    if (animateEls.length > 0) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        animateEls.forEach(el => observer.observe(el));
    }

    // --- Counter animation for [data-count] ---
    const countEls = document.querySelectorAll('[data-count]');
    if (countEls.length > 0) {
        const counterObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) return;
                const el = entry.target;
                const target = parseInt(el.dataset.count, 10);
                const duration = 1200; // ms
                const start = performance.now();

                function tick(now) {
                    const elapsed = now - start;
                    const progress = Math.min(elapsed / duration, 1);
                    // Ease out cubic
                    const eased = 1 - Math.pow(1 - progress, 3);
                    el.textContent = Math.round(target * eased);
                    if (progress < 1) requestAnimationFrame(tick);
                }
                requestAnimationFrame(tick);
                counterObserver.unobserve(el);
            });
        }, { threshold: 0.5 });

        countEls.forEach(el => counterObserver.observe(el));
    }
})();
