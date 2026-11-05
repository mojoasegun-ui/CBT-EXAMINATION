"use strict";

(() => {
	const root = document.documentElement;
	const body = document.body;
	const header = document.querySelector("[data-site-header]");
	const menu = document.querySelector("#mobile-menu");
	const openMenuButton = document.querySelector("[data-menu-open]");
	const closeMenuButton = document.querySelector("[data-menu-close]");
	const liveStatus = document.querySelector("#action-status");

	root.classList.add("js-ready");

	const updateHeader = () => {
		header?.classList.toggle("is-scrolled", window.scrollY > 80);
	};

	updateHeader();
	window.addEventListener("scroll", updateHeader, { passive: true });

	if (menu && openMenuButton && closeMenuButton) {
		let lastMenuTrigger = null;

		const focusableInMenu = () => [...menu.querySelectorAll('a[href], button:not([disabled]), input:not([disabled])')];

		const closeMenu = () => {
			menu.hidden = true;
			body.classList.remove("menu-open");
			openMenuButton.setAttribute("aria-expanded", "false");
			openMenuButton.setAttribute("aria-label", "Open navigation menu");
			(lastMenuTrigger || openMenuButton).focus();
		};

		openMenuButton.addEventListener("click", () => {
			lastMenuTrigger = document.activeElement;
			menu.hidden = false;
			body.classList.add("menu-open");
			openMenuButton.setAttribute("aria-expanded", "true");
			openMenuButton.setAttribute("aria-label", "Close navigation menu");
			closeMenuButton.focus();
		});

		closeMenuButton.addEventListener("click", closeMenu);
		menu.querySelectorAll("[data-menu-link]").forEach((link) => link.addEventListener("click", closeMenu));

		menu.addEventListener("keydown", (event) => {
			if (event.key === "Escape") {
				event.preventDefault();
				closeMenu();
				return;
			}
			if (event.key !== "Tab") return;

			const focusable = focusableInMenu();
			const first = focusable[0];
			const last = focusable[focusable.length - 1];
			if (event.shiftKey && document.activeElement === first) {
				event.preventDefault();
				last.focus();
			} else if (!event.shiftKey && document.activeElement === last) {
				event.preventDefault();
				first.focus();
			}
		});
	}

	document.querySelectorAll("[data-coming-soon]").forEach((link) => {
		link.addEventListener("click", (event) => {
			event.preventDefault();
			if (liveStatus) liveStatus.textContent = link.dataset.comingSoon || "This destination is not available yet.";
		});
	});

	const slider = document.querySelector("[data-slider]");
	if (slider) {
		const slides = [...slider.querySelectorAll("[data-slide]")];
		const dots = [...slider.querySelectorAll("[data-slide-dot]")];
		const status = slider.querySelector("[data-slide-status]");
		const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
		let activeIndex = 0;
		let autoplayTimer = null;
		let pointerStart = null;

		const showSlide = (nextIndex) => {
			activeIndex = (nextIndex + slides.length) % slides.length;
			slides.forEach((slide, index) => {
				const active = index === activeIndex;
				slide.hidden = !active;
				slide.classList.toggle("is-active", active);
			});
			dots.forEach((dot, index) => {
				const active = index === activeIndex;
				dot.classList.toggle("is-active", active);
				dot.setAttribute("aria-pressed", String(active));
			});
			if (status) status.textContent = `Preview ${activeIndex + 1} of ${slides.length}`;
		};

		const stopAutoplay = () => {
			if (autoplayTimer !== null) window.clearInterval(autoplayTimer);
			autoplayTimer = null;
		};

		const startAutoplay = () => {
			stopAutoplay();
			if (reducedMotion.matches || slider.dataset.autoplay !== "true") return;
			autoplayTimer = window.setInterval(() => showSlide(activeIndex + 1), 6000);
		};

		slider.querySelector("[data-slide-prev]")?.addEventListener("click", () => showSlide(activeIndex - 1));
		slider.querySelector("[data-slide-next]")?.addEventListener("click", () => showSlide(activeIndex + 1));
		dots.forEach((dot) => dot.addEventListener("click", () => showSlide(Number(dot.dataset.slideDot))));
		slider.addEventListener("keydown", (event) => {
			if (event.key === "ArrowLeft") {
				event.preventDefault();
				showSlide(activeIndex - 1);
			} else if (event.key === "ArrowRight") {
				event.preventDefault();
				showSlide(activeIndex + 1);
			}
		});
		slider.addEventListener("mouseenter", stopAutoplay);
		slider.addEventListener("mouseleave", startAutoplay);
		slider.addEventListener("focusin", stopAutoplay);
		slider.addEventListener("focusout", (event) => {
			if (!slider.contains(event.relatedTarget)) startAutoplay();
		});
		slider.addEventListener("touchstart", (event) => {
			pointerStart = event.changedTouches[0]?.clientX ?? null;
		}, { passive: true });
		slider.addEventListener("touchend", (event) => {
			const end = event.changedTouches[0]?.clientX;
			if (pointerStart === null || typeof end !== "number") return;
			const distance = end - pointerStart;
			if (Math.abs(distance) > 48) showSlide(activeIndex + (distance < 0 ? 1 : -1));
			pointerStart = null;
		}, { passive: true });

		const motionPreferenceChanged = () => reducedMotion.matches ? stopAutoplay() : startAutoplay();
		if (typeof reducedMotion.addEventListener === "function") {
			reducedMotion.addEventListener("change", motionPreferenceChanged);
		} else {
			reducedMotion.addListener(motionPreferenceChanged);
		}

		showSlide(0);
		if ("IntersectionObserver" in window) {
			const sliderObserver = new IntersectionObserver(([entry]) => {
				if (entry.isIntersecting) startAutoplay();
				else stopAutoplay();
			}, { threshold: 0.25 });
			sliderObserver.observe(slider);
		} else {
			startAutoplay();
		}
	}

	if ("IntersectionObserver" in window && !window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
		const revealObserver = new IntersectionObserver((entries, observer) => {
			entries.forEach((entry) => {
				if (!entry.isIntersecting) return;
				entry.target.setAttribute("data-visible", "true");
				observer.unobserve(entry.target);
			});
		}, { threshold: 0.12 });

		document.querySelectorAll("[data-reveal]").forEach((element) => revealObserver.observe(element));
		root.classList.add("reveal-active");
	}

	document.querySelector("[data-newsletter]")?.addEventListener("submit", (event) => {
		event.preventDefault();
		const form = event.currentTarget;
		const message = form.querySelector("[data-newsletter-status]");
		if (message) message.textContent = "Newsletter signup is not connected in this preview.";
	});
})();