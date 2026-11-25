/**
 * Sliding Active Indicator for Administrator and Receptionist Sidebars
 * Uses plain CSS transitions and vanilla JavaScript with sessionStorage persistence.
 */
(function () {
    'use strict';

    function setupSidebarIndicator(navList) {
        if (!navList || navList.dataset.indicatorInitialized) {
            return;
        }
        navList.dataset.indicatorInitialized = 'true';

        // Ensure relative positioning on the container
        navList.classList.add('sidebar-nav-list', 'position-relative');

        // Locate or create the single absolutely positioned highlight indicator element
        var indicator = navList.querySelector('.sidebar-active-indicator');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.className = 'sidebar-active-indicator';
            indicator.setAttribute('aria-hidden', 'true');
            navList.prepend(indicator);
        }

        // Determine unique storage key per sidebar
        var sidebarId = navList.id || (navList.closest('.app-sidebar') && navList.closest('.app-sidebar').id) || 'sidebar';
        var isReceptionist = navList.id.indexOf('receptionist') !== -1 || (navList.closest('.app-sidebar') && navList.closest('.app-sidebar').id.indexOf('receptionist') !== -1);
        var storagePrefix = isReceptionist ? 'hotel_sidebar_receptionist' : 'hotel_sidebar_admin';
        var keyTop = storagePrefix + '_offsetTop';
        var keyHeight = storagePrefix + '_offsetHeight';

        function getActiveItem() {
            return navList.querySelector('.sidebar-nav-link.active') ||
                   navList.querySelector('.nav-link.active');
        }

        function setPosition(top, height, left, width, smooth) {
            if (smooth) {
                indicator.style.transition = 'top 0.28s cubic-bezier(0.4, 0, 0.2, 1), height 0.28s cubic-bezier(0.4, 0, 0.2, 1), left 0.28s cubic-bezier(0.4, 0, 0.2, 1), width 0.28s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.2s ease';
            } else {
                indicator.style.transition = 'none';
            }

            indicator.style.top = top + 'px';
            indicator.style.height = height + 'px';
            if (typeof left === 'number' && !isNaN(left)) {
                indicator.style.left = left + 'px';
            }
            if (typeof width === 'number' && !isNaN(width)) {
                indicator.style.width = width + 'px';
            }

            indicator.classList.add('is-visible');

            if (!smooth) {
                // Force layout reflow
                void indicator.offsetHeight;
                indicator.style.transition = '';
            }
        }

        function updateToActiveItem(smooth) {
            var activeItem = getActiveItem();
            if (!activeItem) {
                indicator.classList.remove('is-visible');
                return;
            }

            var top = activeItem.offsetTop;
            var height = activeItem.offsetHeight;
            var left = activeItem.offsetLeft;
            var width = activeItem.offsetWidth;

            setPosition(top, height, left, width, smooth);

            try {
                sessionStorage.setItem(keyTop, top);
                sessionStorage.setItem(keyHeight, height);
            } catch (e) {}
        }

        // 1. Check and restore saved position & height from sessionStorage
        var savedTop = null;
        var savedHeight = null;
        try {
            savedTop = sessionStorage.getItem(keyTop);
            savedHeight = sessionStorage.getItem(keyHeight);
        } catch (e) {}

        navList.classList.add('has-sliding-indicator');

        var initialActive = getActiveItem();
        var defaultLeft = initialActive ? initialActive.offsetLeft : 4;
        var defaultWidth = initialActive ? initialActive.offsetWidth : (navList.clientWidth - 8);

        if (savedTop !== null && savedHeight !== null && !isNaN(parseFloat(savedTop)) && !isNaN(parseFloat(savedHeight))) {
            // Restore saved position immediately without transition animation
            setPosition(parseFloat(savedTop), parseFloat(savedHeight), defaultLeft, defaultWidth, false);

            // Recalculate and update the indicator position after page loading to match the actual active item
            requestAnimationFrame(function () {
                setTimeout(function () {
                    updateToActiveItem(true);
                }, 10);
            });
        } else {
            // First time load: position directly to the current active item
            updateToActiveItem(false);
        }

        // 2. Click handlers on navigation links: glide smoothly and update sessionStorage
        var links = navList.querySelectorAll('.sidebar-nav-link, .nav-link');
        links.forEach(function (link) {
            link.addEventListener('click', function (e) {
                // Ignore modal triggers (such as Logout)
                if (this.getAttribute('data-bs-toggle') === 'modal' || this.closest('[data-bs-toggle="modal"]')) {
                    return;
                }

                // Smoothly glide indicator to the clicked item
                var targetTop = this.offsetTop;
                var targetHeight = this.offsetHeight;
                var targetLeft = this.offsetLeft;
                var targetWidth = this.offsetWidth;

                setPosition(targetTop, targetHeight, targetLeft, targetWidth, true);

                try {
                    sessionStorage.setItem(keyTop, targetTop);
                    sessionStorage.setItem(keyHeight, targetHeight);
                } catch (err) {}

                // Update active state visual classes
                links.forEach(function (l) { l.classList.remove('active'); });
                this.classList.add('active');
            });
        });

        // 3. Keep positioned on resize and collapse / dropdown expansion
        window.addEventListener('resize', function () {
            updateToActiveItem(false);
        });

        if (window.ResizeObserver) {
            var ro = new ResizeObserver(function () {
                updateToActiveItem(false);
            });
            ro.observe(navList);
        }

        var aside = navList.closest('aside') || document;
        aside.addEventListener('shown.bs.offcanvas', function () {
            updateToActiveItem(false);
        });
        aside.addEventListener('shown.bs.collapse', function () {
            updateToActiveItem(true);
        });
        aside.addEventListener('hidden.bs.collapse', function () {
            updateToActiveItem(true);
        });
        aside.addEventListener('shown.bs.dropdown', function () {
            updateToActiveItem(true);
        });
        aside.addEventListener('hidden.bs.dropdown', function () {
            updateToActiveItem(true);
        });
        aside.addEventListener('transitionend', function () {
            updateToActiveItem(false);
        });
    }

    function initAllSidebarIndicators() {
        var navLists = document.querySelectorAll('.sidebar-nav-list, #adminSidebarNavList, #receptionistSidebarNavList, .app-sidebar ul.nav');
        navLists.forEach(function (list) {
            setupSidebarIndicator(list);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAllSidebarIndicators);
    } else {
        initAllSidebarIndicators();
    }

    window.addEventListener('pageshow', function () {
        initAllSidebarIndicators();
    });
})();
