<style>
/* Scoped styles for the mobile sticky menu */
:root {
    --sm-bgColorMenu: #1d1d27;
    --sm-duration: .7s;
}

@media screen and (max-width: 767px) {
    .mobile-sticky-menu-wrapper {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        z-index: 1000;
        display: flex;
        justify-content: center;
        background-color: transparent;
        pointer-events: none; /* Let clicks pass through the wrapper itself */
    }

    .mobile-sticky-menu-wrapper * {
        box-sizing: border-box;
    }

    .mobile-sticky-menu-wrapper .menu {
        margin: 0;
        display: flex;
        width: 100%;
        font-size: 1.5em;
        padding: 0 1.5em; /* Adjusted padding for better fit on small screens */
        position: relative;
        align-items: center;
        justify-content: space-between; /* Spread items evenly */
        background-color: var(--sm-bgColorMenu);
        pointer-events: auto; /* Re-enable clicks for the menu */
        border-top-left-radius: 20px; /* Optional: smooth corners if not full width */
        border-top-right-radius: 20px;
    }

    .mobile-sticky-menu-wrapper .menu__item {
        all: unset;
        flex-grow: 1;
        z-index: 100;
        display: flex;
        cursor: pointer;
        position: relative;
        border-radius: 50%;
        align-items: center;
        will-change: transform;
        justify-content: center;
        padding: 0.55em 0 0.85em;
        transition: transform var(--sm-timeOut , var(--sm-duration));
        -webkit-tap-highlight-color: transparent;
    }

    .mobile-sticky-menu-wrapper .menu__item::before {
        content: "";
        z-index: -1;
        width: 4.2em;
        height: 4.2em;
        border-radius: 50%;
        position: absolute;
        transform: scale(0);
        transition: background-color var(--sm-duration), transform var(--sm-duration);
    }

    .mobile-sticky-menu-wrapper .menu__item.active {
        transform: translate3d(0, -.8em , 0);
    }

    .mobile-sticky-menu-wrapper .menu__item.active::before {
        transform: scale(1);
        background-color: var(--sm-bgColorItem);
    }

    .mobile-sticky-menu-wrapper .icon {
        width: 2.6em;
        height: 2.6em;
        stroke: white;
        fill: transparent;
        stroke-width: 1pt;
        stroke-miterlimit: 10;
        stroke-linecap: round;
        stroke-linejoin: round;
        stroke-dasharray: 400;
    }

    .mobile-sticky-menu-wrapper .menu__item.active .icon {
        animation: strok 1.5s reverse;
    }

    @keyframes strok {
        100% {
            stroke-dashoffset: 400;
        }
    }

    .mobile-sticky-menu-wrapper .menu__border {
        left: 0;
        bottom: 99%;
        width: 10.9em;
        height: 2.4em;
        position: absolute;
        clip-path: url(#sm-menu-clip);
        will-change: transform;
        background-color: var(--sm-bgColorMenu);
        transition: transform var(--sm-timeOut , var(--sm-duration));
    }

    .mobile-sticky-menu-wrapper .svg-container {
        width: 0;
        height: 0;
        position: absolute;
    }
}
@media screen and (min-width: 768px) {
    .mobile-sticky-menu-wrapper {
        display: none !important;
    }
}
</style>

<div class="mobile-sticky-menu-wrapper" style="--sm-timeOut: none;">
  <menu class="menu" id="sm-menu">
    <a href="{$addons.mobile_sticky_menu.link_1}" class="menu__item active" style="--sm-bgColorItem: #ff8c00;">
      <svg class="icon" viewBox="0 0 24 24">
        <path d="M3.8,6.6h16.4"></path>
        <path d="M20.2,12.1H3.8"></path>
        <path d="M3.8,17.5h16.4"></path>
      </svg>
    </a>

    <a href="{$addons.mobile_sticky_menu.link_2}" class="menu__item" style="--sm-bgColorItem: #f54888;">
      <svg class="icon" viewBox="0 0 24 24">
        <path d="M6.7,4.8h10.7c0.3,0,0.6,0.2,0.7,0.5l2.8,7.3c0,0.1,0,0.2,0,0.3v5.6c0,0.4-0.4,0.8-0.8,0.8H3.8
        C3.4,19.3,3,19,3,18.5v-5.6c0-0.1,0-0.2,0.1-0.3L6,5.3C6.1,5,6.4,4.8,6.7,4.8z"></path>
        <path d="M3.4,12.9H8l1.6,2.8h4.9l1.5-2.8h4.6"></path>
      </svg>
    </a>

    <a href="{$addons.mobile_sticky_menu.link_3}" class="menu__item" style="--sm-bgColorItem: #4343f5;">
      <svg class="icon" viewBox="0 0 24 24">
      <path d="M3.4,11.9l8.8,4.4l8.4-4.4"></path>
      <path d="M3.4,16.2l8.8,4.5l8.4-4.5"></path>
      <path d="M3.7,7.8l8.6-4.5l8,4.5l-8,4.3L3.7,7.8z"></path>
    </svg>
    </a>

    <a href="{$addons.mobile_sticky_menu.link_4}" class="menu__item" style="--sm-bgColorItem: #e0b115;">
      <svg class="icon" viewBox="0 0 24 24">
        <path d="M5.1,3.9h13.9c0.6,0,1.2,0.5,1.2,1.2v13.9c0,0.6-0.5,1.2-1.2,1.2H5.1c-0.6,0-1.2-0.5-1.2-1.2V5.1
          C3.9,4.4,4.4,3.9,5.1,3.9z"></path>
        <path d="M4.2,9.3h15.6"></path>
        <path d="M9.1,9.5v10.3"></path>
    </svg>
    </a>

    <a href="{$addons.mobile_sticky_menu.link_5}" class="menu__item" style="--sm-bgColorItem:#65ddb7;">
      <svg class="icon" viewBox="0 0 24 24">
        <path d="M5.1,3.9h13.9c0.6,0,1.2,0.5,1.2,1.2v13.9c0,0.6-0.5,1.2-1.2,1.2H5.1c-0.6,0-1.2-0.5-1.2-1.2V5.1
          C3.9,4.4,4.4,3.9,5.1,3.9z"></path>
        <path d="M5.5,20l9.9-9.9l4.7,4.7"></path>
        <path d="M10.4,8.8c0,0.9-0.7,1.6-1.6,1.6c-0.9,0-1.6-0.7-1.6-1.6C7.3,8,8,7.3,8.9,7.3C9.7,7.3,10.4,8,10.4,8.8z"></path>
      </svg>
    </a>

    <div class="menu__border" id="sm-menu-border" style="transform: translate3d(0px, 0px, 0px);"></div>
  </menu>

  <div class="svg-container">
    <svg viewBox="0 0 202.9 45.5">
      <clipPath id="sm-menu-clip" clipPathUnits="objectBoundingBox" transform="scale(0.0049285362247413 0.021978021978022)">
        <path d="M6.7,45.5c5.7,0.1,14.1-0.4,23.3-4c5.7-2.3,9.9-5,18.1-10.5c10.7-7.1,11.8-9.2,20.6-14.3c5-2.9,9.2-5.2,15.2-7
          c7.1-2.1,13.3-2.3,17.6-2.1c4.2-0.2,10.5,0.1,17.6,2.1c6.1,1.8,10.2,4.1,15.2,7c8.8,5,9.9,7.1,20.6,14.3c8.3,5.5,12.4,8.2,18.1,10.5
          c9.2,3.6,17.6,4.2,23.3,4H6.7z"></path>
      </clipPath>
    </svg>
  </div>
</div>

<script>
(function() {
    "use strict";

    // Initialize once the DOM is ready
    document.addEventListener("DOMContentLoaded", function() {
        const wrapper = document.querySelector(".mobile-sticky-menu-wrapper");
        if (!wrapper) return;

        const menu = wrapper.querySelector(".menu");
        const menuItems = menu.querySelectorAll(".menu__item");
        const menuBorder = menu.querySelector(".menu__border");
        let activeItem = menu.querySelector(".active");

        function offsetMenuBorder(element, menuBorder) {
            if (!element || !menuBorder) return;
            const offsetActiveItem = element.getBoundingClientRect();
            // Wait for proper layout rendering before calculating left position
            if (offsetActiveItem.width === 0) return;

            const left = Math.floor(offsetActiveItem.left - menu.getBoundingClientRect().left - (menuBorder.offsetWidth - offsetActiveItem.width) / 2) + "px";
            menuBorder.style.transform = `translate3d(${left}, 0 , 0)`;
        }

        // Find the active item based on current URL
        const currentUrl = window.location.pathname + window.location.search;
        let foundActive = false;
        menuItems.forEach((item) => {
            item.classList.remove("active");
            // Basic matching - if the href exactly matches or is a prefix of the current URL (if not just '/')
            const href = item.getAttribute("href");
            if (href && href !== '/' && currentUrl.indexOf(href) !== -1) {
                activeItem = item;
                foundActive = true;
            }
        });

        // Default to the first item if none matched, or apply the matched one
        if (!foundActive && menuItems.length > 0) {
            activeItem = menuItems[0];
        }

        if (activeItem) {
            activeItem.classList.add("active");
            // Set slight timeout to allow CSS to render before calculating border offset
            setTimeout(() => {
                 offsetMenuBorder(activeItem, menuBorder);
            }, 100);
        }

        menuItems.forEach((item) => {
            item.addEventListener("click", function(e) {
                // Remove timeout constraint for smooth animation
                wrapper.style.removeProperty("--sm-timeOut");

                if (activeItem == item) return;

                if (activeItem) {
                    activeItem.classList.remove("active");
                }

                item.classList.add("active");
                activeItem = item;
                offsetMenuBorder(activeItem, menuBorder);
            });
        });

        window.addEventListener("resize", () => {
            offsetMenuBorder(activeItem, menuBorder);
            wrapper.style.setProperty("--sm-timeOut", "none");
        });
    });
})();
</script>
