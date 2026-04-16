const siteHeader = document.querySelector(".site-header");
const menuToggle = document.querySelector(".menu-toggle");
const navPanel = document.querySelector(".nav-panel");
const navLinks = document.querySelectorAll('.nav-links a[href^="#"]');
const revealItems = document.querySelectorAll(".reveal");
const impactValues = document.querySelectorAll(".impact-value");

const updateHeaderState = () => {
    if (!siteHeader) {
        return;
    }

    siteHeader.classList.toggle("scrolled", window.scrollY > 24);
};

const toggleMenu = () => {
    if (!menuToggle || !navPanel) {
        return;
    }

    const isOpen = navPanel.classList.toggle("is-open");
    menuToggle.setAttribute("aria-expanded", String(isOpen));
};

const closeMenu = () => {
    if (!menuToggle || !navPanel) {
        return;
    }

    navPanel.classList.remove("is-open");
    menuToggle.setAttribute("aria-expanded", "false");
};

const animateCounter = (element) => {
    const target = Number(element.dataset.count || 0);
    const duration = 1400;
    const start = performance.now();

    const frame = (time) => {
        const progress = Math.min((time - start) / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        const value = Math.floor(target * eased);
        element.textContent = value.toLocaleString();

        if (progress < 1) {
            requestAnimationFrame(frame);
        }
    };

    requestAnimationFrame(frame);
};

if (menuToggle) {
    menuToggle.addEventListener("click", toggleMenu);
}

navLinks.forEach((link) => {
    link.addEventListener("click", () => {
        closeMenu();
    });
});

window.addEventListener("scroll", updateHeaderState);
window.addEventListener("load", updateHeaderState);

const revealObserver = new IntersectionObserver(
    (entries, observer) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) {
                return;
            }

            entry.target.classList.add("is-visible");

            const counter = entry.target.classList.contains("impact-value")
                ? entry.target
                : entry.target.querySelector(".impact-value");

            if (counter && !counter.dataset.animated) {
                counter.dataset.animated = "true";
                animateCounter(counter);
            }

            observer.unobserve(entry.target);
        });
    },
    {
        threshold: 0.18,
        rootMargin: "0px 0px -40px 0px",
    }
);

revealItems.forEach((item, index) => {
    item.style.transitionDelay = `${Math.min(index * 60, 240)}ms`;
    revealObserver.observe(item);
});

impactValues.forEach((item) => {
    if (!item.classList.contains("reveal")) {
        revealObserver.observe(item);
    }
});
