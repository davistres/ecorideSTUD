document.addEventListener("DOMContentLoaded", function () {
    // Menu Burger
    const burger = document.getElementById("burger");
    const mobileMenu = document.getElementById("mobile-menu");
    const closeMenu = document.getElementById("close-menu");
    let isMenuOpen = false;

    function toggleMenu() {
        isMenuOpen = !isMenuOpen;

        if (mobileMenu) {
            if (isMenuOpen) {
                mobileMenu.classList.add("active");
            } else {
                mobileMenu.classList.remove("active");
            }
        }
    }

    burger?.addEventListener("click", function (e) {
        e.stopPropagation();
        toggleMenu();
    });

    closeMenu?.addEventListener("click", function (e) {
        e.stopPropagation();
        toggleMenu();
    });

    document.addEventListener("click", (e) => {
        if (isMenuOpen && !mobileMenu?.contains(e.target) && !burger?.contains(e.target)) {
            toggleMenu();
        }
    });

    window.addEventListener("resize", () => {
        if (window.innerWidth >= 768) {
            mobileMenu?.classList.remove("active");
            isMenuOpen = false;
        }
    });
});
