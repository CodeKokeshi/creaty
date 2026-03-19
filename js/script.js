document.addEventListener("DOMContentLoaded", function () {
    var revealItems = document.querySelectorAll(".reveal");
    var toggleButtons = document.querySelectorAll(".toggle-visibility");
    var promoBanner = document.querySelector(".promo-banner");
    var promoSlides = promoBanner ? promoBanner.querySelectorAll(".promo-slide") : [];
    var promoPrev = promoBanner ? promoBanner.querySelector(".promo-arrow-left") : null;
    var promoNext = promoBanner ? promoBanner.querySelector(".promo-arrow-right") : null;
    var promoIndex = 0;
    var promoTimer = null;
    var promoDelay = 3000;
    var filterNav = document.querySelector(".section-nav-interactive");
    var filterToggles = filterNav ? filterNav.querySelectorAll(".filter-toggle") : [];
    var filterOptions = filterNav ? filterNav.querySelectorAll(".filter-option") : [];
    var productCards = document.querySelectorAll(".product-grid .product-card");
    var productEmpty = document.querySelector(".product-grid-empty");
    var detailGalleries = document.querySelectorAll("[data-gallery]");
    var packageSlideshows = document.querySelectorAll("[data-package-slideshow]");
    var packageSlideshowControllers = [];
    var activeFilters = {
        brand: "all",
        month: "all",
        day: "all",
        year: "all"
    };

    revealItems.forEach(function (item, index) {
        window.setTimeout(function () {
            item.classList.add("is-visible");
        }, 120 * (index + 1));
    });

    toggleButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            var targetId = button.getAttribute("data-target");
            var passwordInput = document.getElementById(targetId);
            var icon = button.querySelector("img");
            var visibleIcon = button.getAttribute("data-icon-on");
            var hiddenIcon = button.getAttribute("data-icon-off");

            if (!passwordInput || !icon) {
                return;
            }

            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                icon.src = visibleIcon;
                button.setAttribute("aria-label", "Hide password");
                return;
            }

            passwordInput.type = "password";
            icon.src = hiddenIcon;
            button.setAttribute("aria-label", "Show password");
        });
    });

    function showPromoSlide(nextIndex) {
        if (!promoSlides.length) {
            return;
        }

        promoIndex = (nextIndex + promoSlides.length) % promoSlides.length;

        promoSlides.forEach(function (slide, slideIndex) {
            var isActive = slideIndex === promoIndex;

            slide.classList.toggle("is-active", isActive);
            slide.setAttribute("aria-hidden", isActive ? "false" : "true");
        });
    }

    function startPromoTimer() {
        if (!promoSlides.length) {
            return;
        }

        window.clearInterval(promoTimer);
        promoTimer = window.setInterval(function () {
            showPromoSlide(promoIndex + 1);
        }, promoDelay);
    }

    function stopPromoTimer() {
        window.clearInterval(promoTimer);
        promoTimer = null;
    }

    function closeFilterPanels(exceptId) {
        filterToggles.forEach(function (toggle) {
            var panelId = toggle.getAttribute("aria-controls");
            var panel = document.getElementById(panelId);
            var isOpen = panelId === exceptId;

            toggle.setAttribute("aria-expanded", isOpen ? "true" : "false");

            if (panel) {
                panel.hidden = !isOpen;
            }
        });
    }

    function applyProductFilters() {
        if (!productCards.length) {
            return;
        }

        var visibleCount = 0;

        productCards.forEach(function (card) {
            var matches = Object.keys(activeFilters).every(function (key) {
                var selectedValue = activeFilters[key];
                var cardValue = (card.getAttribute("data-" + key) || "").toLowerCase();

                return selectedValue === "all" || selectedValue === cardValue;
            });

            card.classList.toggle("is-hidden", !matches);

            if (matches) {
                visibleCount += 1;
            }
        });

        if (productEmpty) {
            productEmpty.hidden = visibleCount !== 0;
        }
    }

    function setupDetailGallery(gallery) {
        var slides = gallery.querySelectorAll(".detail-gallery-slide");
        var prevButton = gallery.querySelector('[data-gallery-direction="prev"]');
        var nextButton = gallery.querySelector('[data-gallery-direction="next"]');
        var currentIndex = 0;

        function showSlide(nextIndex) {
            if (!slides.length) {
                return;
            }

            currentIndex = (nextIndex + slides.length) % slides.length;

            slides.forEach(function (slide, slideIndex) {
                var isActive = slideIndex === currentIndex;

                slide.classList.toggle("is-active", isActive);
                slide.setAttribute("aria-hidden", isActive ? "false" : "true");
            });
        }

        if (prevButton) {
            prevButton.addEventListener("click", function () {
                showSlide(currentIndex - 1);
            });
        }

        if (nextButton) {
            nextButton.addEventListener("click", function () {
                showSlide(currentIndex + 1);
            });
        }

        showSlide(0);
    }

    function setupPackageSlideshow(slideshow) {
        var slides = slideshow.querySelectorAll(".package-slide");
        var currentIndex = 0;
        var timer = null;
        var intervalBase = Number.parseInt(slideshow.getAttribute("data-autoplay-ms") || "6200", 10);

        if (!slides.length) {
            return null;
        }

        if (!Number.isFinite(intervalBase) || intervalBase < 2200) {
            intervalBase = 6200;
        }

        function getRandomIndexExcluding(current, total) {
            if (total < 2) {
                return current;
            }

            var next = current;

            while (next === current) {
                next = Math.floor(Math.random() * total);
            }

            return next;
        }

        function getRandomDelay() {
            var jitter = Math.floor(Math.random() * 1800);

            return intervalBase + jitter;
        }

        function showSlide(nextIndex) {
            currentIndex = (nextIndex + slides.length) % slides.length;

            slides.forEach(function (slide, slideIndex) {
                var isActive = slideIndex === currentIndex;

                slide.classList.toggle("is-active", isActive);
                slide.setAttribute("aria-hidden", isActive ? "false" : "true");
            });
        }

        function queueNext() {
            if (slides.length < 2) {
                return;
            }

            window.clearTimeout(timer);
            timer = window.setTimeout(function () {
                showSlide(getRandomIndexExcluding(currentIndex, slides.length));
                queueNext();
            }, getRandomDelay());
        }

        function start() {
            if (slides.length < 2) {
                return;
            }

            queueNext();
        }

        function stop() {
            window.clearTimeout(timer);
            timer = null;
        }

        showSlide(0);
        start();

        return {
            start: start,
            stop: stop
        };
    }

    if (promoSlides.length) {
        showPromoSlide(0);
        startPromoTimer();

        if (promoPrev) {
            promoPrev.addEventListener("click", function () {
                stopPromoTimer();
                showPromoSlide(promoIndex - 1);
                startPromoTimer();
            });
        }

        if (promoNext) {
            promoNext.addEventListener("click", function () {
                stopPromoTimer();
                showPromoSlide(promoIndex + 1);
                startPromoTimer();
            });
        }

        document.addEventListener("visibilitychange", function () {
            if (document.hidden) {
                stopPromoTimer();
                return;
            }

            startPromoTimer();
        });
    }

    if (filterNav) {
        filterToggles.forEach(function (toggle) {
            toggle.addEventListener("click", function () {
                var panelId = toggle.getAttribute("aria-controls");
                var expanded = toggle.getAttribute("aria-expanded") === "true";

                closeFilterPanels(expanded ? null : panelId);
            });
        });

        filterOptions.forEach(function (option) {
            option.addEventListener("click", function () {
                var group = option.getAttribute("data-filter-group");
                var value = option.getAttribute("data-filter-value");

                if (!group || !value) {
                    return;
                }

                activeFilters[group] = value.toLowerCase();

                filterNav.querySelectorAll('.filter-option[data-filter-group="' + group + '"]').forEach(function (groupOption) {
                    groupOption.classList.toggle("is-selected", groupOption === option);
                });

                applyProductFilters();
            });
        });

        document.addEventListener("click", function (event) {
            if (!filterNav.contains(event.target)) {
                closeFilterPanels(null);
            }
        });

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape") {
                closeFilterPanels(null);
            }
        });

        applyProductFilters();
    }

    detailGalleries.forEach(function (gallery) {
        setupDetailGallery(gallery);
    });

    packageSlideshows.forEach(function (slideshow) {
        var controller = setupPackageSlideshow(slideshow);

        if (controller) {
            packageSlideshowControllers.push(controller);
        }
    });

    if (packageSlideshowControllers.length) {
        document.addEventListener("visibilitychange", function () {
            packageSlideshowControllers.forEach(function (controller) {
                if (document.hidden) {
                    controller.stop();
                    return;
                }

                controller.start();
            });
        });
    }

    var profileToggleButtons = document.querySelectorAll("[data-profile-toggle]");
    var profileCancelButtons = document.querySelectorAll("[data-profile-cancel]");
    var cancelModal = document.getElementById("profile-cancel-modal");
    var openCancelModalButtons = document.querySelectorAll("[data-profile-open-cancel-modal]");
    var closeCancelModalButtons = document.querySelectorAll("[data-profile-close-cancel-modal]");

    function hideEditor(editorId) {
        var target = document.getElementById(editorId);

        if (!target) {
            return;
        }

        target.hidden = true;
    }

    function toggleEditor(editorId) {
        var target = document.getElementById(editorId);

        if (!target) {
            return;
        }

        target.hidden = !target.hidden;
    }

    profileToggleButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            var targetId = button.getAttribute("data-profile-toggle");

            if (!targetId) {
                return;
            }

            toggleEditor(targetId);
        });
    });

    profileCancelButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            var targetId = button.getAttribute("data-profile-cancel");

            if (!targetId) {
                return;
            }

            hideEditor(targetId);
        });
    });

    function openCancelModal() {
        if (!cancelModal) {
            return;
        }

        cancelModal.hidden = false;
    }

    function closeCancelModal() {
        if (!cancelModal) {
            return;
        }

        cancelModal.hidden = true;
    }

    openCancelModalButtons.forEach(function (button) {
        button.addEventListener("click", openCancelModal);
    });

    closeCancelModalButtons.forEach(function (button) {
        button.addEventListener("click", closeCancelModal);
    });

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") {
            closeCancelModal();
        }
    });

    var termsToggleButtons = document.querySelectorAll("[data-terms-toggle]");

    termsToggleButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            var contentId = button.getAttribute("aria-controls");
            var content = contentId ? document.getElementById(contentId) : null;
            var showLabel = button.getAttribute("data-label-show") || "Show Full Terms and Conditions";
            var hideLabel = button.getAttribute("data-label-hide") || "Hide Full Terms and Conditions";
            var isExpanded = button.getAttribute("aria-expanded") === "true";
            var nextExpanded = !isExpanded;

            button.setAttribute("aria-expanded", nextExpanded ? "true" : "false");
            button.textContent = nextExpanded ? hideLabel : showLabel;

            if (content) {
                content.hidden = !nextExpanded;
            }
        });
    });

    var cartStorageKey = "creaty_cart_v1";
    var bookingStorageKey = "creaty_booking_v1";

    function loadJsonStorage(storageKey, fallbackValue) {
        try {
            var raw = window.localStorage.getItem(storageKey);
            if (!raw) {
                return fallbackValue;
            }

            return JSON.parse(raw);
        } catch (error) {
            return fallbackValue;
        }
    }

    function saveJsonStorage(storageKey, value) {
        try {
            window.localStorage.setItem(storageKey, JSON.stringify(value));
        } catch (error) {
            // Keep UI usable even if localStorage quota is exceeded.
        }
    }

    function parseMoney(value) {
        var normalized = String(value || "").replace(/[^0-9.]/g, "");
        var amount = Number.parseFloat(normalized);

        if (!Number.isFinite(amount)) {
            return 0;
        }

        return amount;
    }

    function formatMoney(value) {
        var safeValue = Number.isFinite(value) ? value : 0;

        return "P " + safeValue.toFixed(2);
    }

    function getCartItems() {
        var parsed = loadJsonStorage(cartStorageKey, []);

        if (!Array.isArray(parsed)) {
            return [];
        }

        return parsed
            .map(function (item) {
                if (!item || typeof item !== "object") {
                    return null;
                }

                var qty = Number.parseInt(item.qty, 10);
                var days = Number.parseInt(item.days, 10);

                return {
                    id: String(item.id || ""),
                    type: String(item.type || "item"),
                    name: String(item.name || "Unnamed Item"),
                    copy: String(item.copy || ""),
                    image: String(item.image || ""),
                    price: parseMoney(item.price),
                    qty: Number.isFinite(qty) && qty > 0 ? qty : 1,
                    days: Number.isFinite(days) && days > 0 ? days : 1
                };
            })
            .filter(function (item) {
                return item && item.id;
            });
    }

    function saveCartItems(items) {
        saveJsonStorage(cartStorageKey, items);
    }

    function getCartCount(items) {
        var source = Array.isArray(items) ? items : getCartItems();

        return source.reduce(function (total, item) {
            return total + (Number.isFinite(item.qty) ? item.qty : 0);
        }, 0);
    }

    function syncCartCountBadges(items) {
        var count = getCartCount(items);
        var badges = document.querySelectorAll(".cart-count");

        badges.forEach(function (badge) {
            badge.textContent = String(count);
        });
    }

    function showCartToast(message) {
        var toast = document.getElementById("cart-toast");
        if (!toast) {
            toast = document.createElement("div");
            toast.id = "cart-toast";
            toast.className = "cart-toast";
            document.body.appendChild(toast);
        }

        toast.textContent = message;
        toast.classList.add("is-visible");

        window.setTimeout(function () {
            toast.classList.remove("is-visible");
        }, 1800);
    }

    function addOrUpdateCartItem(nextItem) {
        var items = getCartItems();
        var existingIndex = items.findIndex(function (item) {
            return item.id === nextItem.id;
        });

        if (existingIndex >= 0) {
            items[existingIndex].qty += 1;
        } else {
            items.push(nextItem);
        }

        saveCartItems(items);
        syncCartCountBadges(items);
    }

    function initializeAddToCartButtons() {
        var buttons = document.querySelectorAll("[data-add-cart]");

        buttons.forEach(function (button) {
            button.addEventListener("click", function () {
                var loginUrl = button.getAttribute("data-login-url");
                if (loginUrl) {
                    window.location.href = loginUrl;
                    return;
                }

                var itemId = button.getAttribute("data-item-id");
                if (!itemId) {
                    return;
                }

                addOrUpdateCartItem({
                    id: itemId,
                    type: button.getAttribute("data-item-type") || "item",
                    name: button.getAttribute("data-item-name") || "Unnamed Item",
                    copy: button.getAttribute("data-item-copy") || "",
                    image: button.getAttribute("data-item-image") || "",
                    price: parseMoney(button.getAttribute("data-item-price") || "0"),
                    qty: 1,
                    days: 1
                });

                showCartToast("Added to cart");
            });
        });
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function initializeCartPage() {
        var panel = document.querySelector("[data-cart-items-panel]");
        if (!panel) {
            return;
        }

        var emptyMessage = panel.querySelector("[data-cart-empty-message]");
        var totalNode = document.querySelector("[data-cart-total]");
        var breakdownNode = document.querySelector("[data-cart-breakdown]");
        var bookingCard = document.querySelector("[data-cart-booking]");
        var bookingNote = document.querySelector("[data-cart-booking-note]");
        var confirmButton = bookingCard ? bookingCard.querySelector(".cart-confirm-button") : null;
        var paymentSelect = bookingCard ? bookingCard.querySelector("[data-booking-field='paymentMethod']") : null;
        var mapFrame = bookingCard ? bookingCard.querySelector("[data-booking-map]") : null;
        var deliveryOnlyBlock = bookingCard ? bookingCard.querySelector("[data-delivery-only-block]") : null;
        var courierSelect = bookingCard ? bookingCard.querySelector("[data-booking-field='courier']") : null;
        var placeSelect = bookingCard ? bookingCard.querySelector("[data-booking-field='place']") : null;
        var receiveDateInput = bookingCard ? bookingCard.querySelector("[data-booking-field='receiveDate']") : null;
        var returnDateInput = bookingCard ? bookingCard.querySelector("[data-booking-field='returnDate']") : null;
        var receiveMethodInputs = bookingCard ? bookingCard.querySelectorAll("input[name='receivingMethod']") : [];
        var returnMethodInputs = bookingCard ? bookingCard.querySelectorAll("input[name='returningMethod']") : [];
        var uploadInputs = bookingCard ? bookingCard.querySelectorAll("input[type='file'][data-booking-field]") : [];
        var bookingState = loadJsonStorage(bookingStorageKey, {});

        function getMethodValue(inputList, fallbackValue) {
            var checked = Array.prototype.find.call(inputList, function (input) {
                return input.checked;
            });

            return checked ? checked.value : fallbackValue;
        }

        function restoreBookingDefaults() {
            var now = new Date();
            var today = now.toISOString().slice(0, 10);
            var tomorrowDate = new Date(now.getTime() + 24 * 60 * 60 * 1000);
            var tomorrow = tomorrowDate.toISOString().slice(0, 10);

            if (receiveDateInput) {
                receiveDateInput.min = today;
                receiveDateInput.value = bookingState.receiveDate || today;
            }

            if (returnDateInput) {
                returnDateInput.min = receiveDateInput ? receiveDateInput.value : today;
                returnDateInput.value = bookingState.returnDate || tomorrow;
            }

            if (placeSelect && bookingState.place) {
                placeSelect.value = bookingState.place;
            }

            if (courierSelect && bookingState.courier) {
                courierSelect.value = bookingState.courier;
            }

            if (bookingCard) {
                var receiveTime = bookingCard.querySelector("[data-booking-field='receiveTime']");
                var returnTime = bookingCard.querySelector("[data-booking-field='returnTime']");

                if (receiveTime && bookingState.receiveTime) {
                    receiveTime.value = bookingState.receiveTime;
                }

                if (returnTime && bookingState.returnTime) {
                    returnTime.value = bookingState.returnTime;
                }

                if (paymentSelect && bookingState.paymentMethod) {
                    paymentSelect.value = bookingState.paymentMethod;
                }
            }

            if (bookingState.receivingMethod) {
                Array.prototype.forEach.call(receiveMethodInputs, function (input) {
                    input.checked = input.value === bookingState.receivingMethod;
                });
            }

            if (bookingState.returningMethod) {
                Array.prototype.forEach.call(returnMethodInputs, function (input) {
                    input.checked = input.value === bookingState.returningMethod;
                });
            }
        }

        function updateMethodOptionStyles() {
            if (!bookingCard) {
                return;
            }

            bookingCard.querySelectorAll(".cart-method-option").forEach(function (option) {
                var radio = option.querySelector("input[type='radio']");
                option.classList.toggle("is-selected", Boolean(radio && radio.checked));
            });
        }

        function getBookingSnapshot() {
            if (!bookingCard) {
                return {};
            }

            var receiveTimeField = bookingCard.querySelector("[data-booking-field='receiveTime']");
            var returnTimeField = bookingCard.querySelector("[data-booking-field='returnTime']");

            return {
                receiveDate: receiveDateInput ? receiveDateInput.value : "",
                receiveTime: receiveTimeField ? receiveTimeField.value : "",
                place: placeSelect ? placeSelect.value : "",
                returnDate: returnDateInput ? returnDateInput.value : "",
                returnTime: returnTimeField ? returnTimeField.value : "",
                courier: courierSelect ? courierSelect.value : "",
                receivingMethod: getMethodValue(receiveMethodInputs, "pickup"),
                returningMethod: getMethodValue(returnMethodInputs, "meetup"),
                paymentMethod: paymentSelect ? paymentSelect.value : ""
            };
        }

        function saveBookingSnapshot() {
            bookingState = getBookingSnapshot();
            saveJsonStorage(bookingStorageKey, bookingState);
        }

        function updateMapPreview() {
            if (!mapFrame || !placeSelect) {
                return;
            }

            var place = placeSelect.value || "Cavite";
            mapFrame.src = "https://www.google.com/maps?q=" + encodeURIComponent(place) + "&output=embed";
        }

        function updateDeliveryFields() {
            var receivingMethod = getMethodValue(receiveMethodInputs, "pickup");
            var returningMethod = getMethodValue(returnMethodInputs, "meetup");
            var hasDelivery = receivingMethod === "delivery" || returningMethod === "delivery";

            if (deliveryOnlyBlock) {
                deliveryOnlyBlock.hidden = !hasDelivery;
            }

            if (courierSelect) {
                courierSelect.disabled = !hasDelivery;
            }

            updateMethodOptionStyles();
        }

        function renderCartItems() {
            var items = getCartItems();

            panel.querySelectorAll(".cart-item-card").forEach(function (node) {
                node.remove();
            });

            if (emptyMessage) {
                emptyMessage.hidden = items.length > 0;
            }

            if (!items.length) {
                if (totalNode) {
                    totalNode.textContent = formatMoney(0);
                }

                if (breakdownNode) {
                    breakdownNode.textContent = "Subtotal P 0.00 + Service fee P 0.00";
                }

                syncCartCountBadges(items);
                return;
            }

            items.forEach(function (item) {
                var lineTotal = item.price * item.qty * item.days;
                var card = document.createElement("article");
                card.className = "cart-item-card";
                card.setAttribute("data-cart-item-id", item.id);
                card.innerHTML = '' +
                    '<div class="cart-item-copy">' +
                        '<h2>' + escapeHtml(String(item.name).toUpperCase()) + '</h2>' +
                        '<p>' + escapeHtml(item.copy) + '</p>' +
                        '<label class="cart-mini-field">' +
                            '<span>Qty</span>' +
                            '<input type="number" min="1" max="20" value="' + item.qty + '" data-cart-edit="qty">' +
                        '</label>' +
                    '</div>' +
                    '<div class="cart-item-thumb">' +
                        '<img class="cart-item-thumb-image" src="' + escapeHtml(item.image) + '" alt="' + escapeHtml(item.name) + '">' +
                    '</div>' +
                    '<div class="cart-item-pricebox">' +
                        '<label class="cart-mini-field">' +
                            '<span>Days</span>' +
                            '<input type="number" min="1" max="14" value="' + item.days + '" data-cart-edit="days">' +
                        '</label>' +
                        '<p class="cart-item-price-label">Price:</p>' +
                        '<strong>' + formatMoney(lineTotal) + '</strong>' +
                    '</div>' +
                    '<button class="cart-remove-button" type="button" aria-label="Remove item" data-cart-remove>&#10005;</button>';

                panel.appendChild(card);
            });

            refreshTotals(items);
            syncCartCountBadges(items);
        }

        function refreshTotals(items) {
            var activeItems = Array.isArray(items) ? items : getCartItems();
            var subtotal = activeItems.reduce(function (sum, item) {
                return sum + (item.price * item.qty * item.days);
            }, 0);
            var booking = getBookingSnapshot();
            var deliveryCount = 0;

            if (booking.receivingMethod === "delivery") {
                deliveryCount += 1;
            }

            if (booking.returningMethod === "delivery") {
                deliveryCount += 1;
            }

            var courierFee = deliveryCount * 120;
            var serviceFee = subtotal > 0 ? 45 : 0;
            var total = subtotal + courierFee + serviceFee;

            if (totalNode) {
                totalNode.textContent = formatMoney(total);
            }

            if (breakdownNode) {
                breakdownNode.textContent = "Subtotal " + formatMoney(subtotal) + " + Service fee " + formatMoney(serviceFee) + " + Courier " + formatMoney(courierFee);
            }
        }

        function handleCartPanelInput(event) {
            var target = event.target;
            var field = target.getAttribute("data-cart-edit");
            if (!field) {
                return;
            }

            var card = target.closest("[data-cart-item-id]");
            if (!card) {
                return;
            }

            var itemId = card.getAttribute("data-cart-item-id");
            var nextValue = Number.parseInt(target.value, 10);
            if (!Number.isFinite(nextValue) || nextValue < 1) {
                nextValue = 1;
            }

            target.value = String(nextValue);

            var items = getCartItems().map(function (item) {
                if (item.id === itemId) {
                    item[field] = nextValue;
                }

                return item;
            });

            saveCartItems(items);
            renderCartItems();
        }

        panel.addEventListener("click", function (event) {
            var removeButton = event.target.closest("[data-cart-remove]");
            if (!removeButton) {
                return;
            }

            var card = removeButton.closest("[data-cart-item-id]");
            if (!card) {
                return;
            }

            var itemId = card.getAttribute("data-cart-item-id");
            var filteredItems = getCartItems().filter(function (item) {
                return item.id !== itemId;
            });

            saveCartItems(filteredItems);
            renderCartItems();
        });

        panel.addEventListener("input", handleCartPanelInput);
        panel.addEventListener("change", handleCartPanelInput);

        if (bookingCard) {
            bookingCard.querySelectorAll("[data-booking-field], input[name='receivingMethod'], input[name='returningMethod']").forEach(function (control) {
                control.addEventListener("change", function () {
                    if (receiveDateInput && returnDateInput && receiveDateInput.value) {
                        returnDateInput.min = receiveDateInput.value;
                        if (returnDateInput.value && returnDateInput.value < receiveDateInput.value) {
                            returnDateInput.value = receiveDateInput.value;
                        }
                    }

                    updateMapPreview();
                    updateDeliveryFields();
                    saveBookingSnapshot();
                    refreshTotals();
                });
            });
        }

        uploadInputs.forEach(function (input) {
            input.addEventListener("change", function () {
                var label = input.getAttribute("data-booking-field") === "validIdImage"
                    ? bookingCard.querySelector("[data-upload-label='validId']")
                    : bookingCard.querySelector("[data-upload-label='selfieId']");

                if (!label) {
                    return;
                }

                if (input.files && input.files.length) {
                    label.textContent = input.files[0].name;
                    return;
                }

                label.textContent = "No file selected";
            });
        });

        if (paymentSelect && bookingNote) {
            paymentSelect.addEventListener("change", function () {
                if (paymentSelect.value === "gcash" || paymentSelect.value === "bank-transfer") {
                    bookingNote.textContent = "Demo payment selected. No charges will be made in this frontend prototype.";
                    return;
                }

                bookingNote.textContent = "Demo flow only: no real booking or payment will be processed.";
            });
        }

        if (confirmButton && bookingNote) {
            confirmButton.addEventListener("click", function () {
                var items = getCartItems();
                if (!items.length) {
                    bookingNote.textContent = "Add at least one item before confirming your demo booking.";
                    return;
                }

                bookingNote.textContent = "Booking request staged in demo mode. We did not submit this to any backend payment or booking service.";
                showCartToast("Demo booking prepared");
            });
        }

        restoreBookingDefaults();
        updateMapPreview();
        updateDeliveryFields();
        saveBookingSnapshot();
        renderCartItems();
    }

    syncCartCountBadges();
    initializeAddToCartButtons();
    initializeCartPage();

    // Calendar Initialization
    var calendarCard = document.querySelector(".product-calendar-card");
    if (calendarCard) {
        var monthSelect = document.getElementById("calendar-month-select");
        var yearSelect = document.getElementById("calendar-year-select");
        var gridContainer = document.getElementById("calendar-grid-container");
        
        var availMonthStr = calendarCard.getAttribute("data-available-month");
        var availYearStr = calendarCard.getAttribute("data-available-year");
        var availDaysStr = calendarCard.getAttribute("data-available-days");
        
        var months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
        var availMonthIndex = months.indexOf(availMonthStr);
        var availDays = [];
        try {
            availDays = JSON.parse(availDaysStr);
        } catch (e) {}

        if (availMonthIndex !== -1 && monthSelect) {
            monthSelect.value = availMonthIndex;
        }
        if (availYearStr && yearSelect) {
            var options = Array.from(yearSelect.options);
            var opt = options.find(o => o.value === availYearStr);
            if (!opt) {
                var newOpt = document.createElement("option");
                newOpt.value = availYearStr;
                newOpt.text = availYearStr;
                yearSelect.appendChild(newOpt);
            }
            yearSelect.value = availYearStr;
        }

        var calendarDays = ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"];
        
        function updateDisables() {
            var today = new Date();
            var currentY = today.getFullYear();
            var currentM = today.getMonth();
            if (currentY < 2026) {
                currentY = 2026;
                currentM = 2; // March
            }

            // Hide and disable past years
            Array.from(yearSelect.options).forEach(function(opt) {
                var y = parseInt(opt.value, 10);
                if (y < currentY) {
                    opt.disabled = true;
                    opt.hidden = true;
                } else {
                    opt.disabled = false;
                    opt.hidden = false;
                }
            });
            
            // If selected year is disabled, push it to current year
            if (yearSelect.options[yearSelect.selectedIndex] && yearSelect.options[yearSelect.selectedIndex].disabled) {
                var firstAbleYear = Array.from(yearSelect.options).find(function(o) { return !o.disabled; });
                if (firstAbleYear) {
                    yearSelect.value = firstAbleYear.value;
                }
            }

            var selY = parseInt(yearSelect.value, 10);

            Array.from(monthSelect.options).forEach(function(opt) {
                var m = parseInt(opt.value, 10);
                if (selY < currentY) {
                    opt.disabled = true;
                    opt.hidden = true;
                } else if (selY === currentY) {
                    var isPastMonth = (m < currentM);
                    opt.disabled = isPastMonth;
                    opt.hidden = isPastMonth;
                } else {
                    opt.disabled = false;
                    opt.hidden = false;
                }
            });

            if (monthSelect.options[monthSelect.selectedIndex] && monthSelect.options[monthSelect.selectedIndex].disabled) {
                var firstAble = Array.from(monthSelect.options).find(function(o) { return !o.disabled; });
                if (firstAble) {
                    monthSelect.value = firstAble.value;
                }
            }
        }

        function renderCalendar() {
            var selectedMonth = parseInt(monthSelect.value, 10);
            var selectedYear = parseInt(yearSelect.value, 10);
            
            var isAvailMonthYear = (selectedMonth === availMonthIndex && selectedYear === parseInt(availYearStr, 10));

            var firstDay = new Date(selectedYear, selectedMonth, 1).getDay();
            var daysInMonth = new Date(selectedYear, selectedMonth + 1, 0).getDate();
            var daysInPrevMonth = new Date(selectedYear, selectedMonth, 0).getDate();
            
            var today = new Date();
            var currentY = today.getFullYear();
            if (currentY < 2026) { currentY = 2026; }
            // Let's use hardcoded 2026 / 2 for March or dynamic:
            // Since environment context is March 18, 2026, let's just use real JS Date
            // but fallback structurally:
            var actualTodayY = today.getFullYear();
            var actualTodayM = today.getMonth();
            var actualTodayD = today.getDate();
            // Environment override since we're simulating mock future/present
            if (actualTodayY < 2026) {
                actualTodayY = 2026;
                actualTodayM = 2;
                actualTodayD = 18;
            }

            var html = "";
            
            calendarDays.forEach(function(day) {
                html += '<span class="calendar-day-name">' + day + '</span>';
            });
            
            var date = 1;
            var nextDate = 1;
            var needsSixthRow = (firstDay + daysInMonth > 35);
            var totalRows = needsSixthRow ? 6 : 5;
            
            for (var row = 0; row < totalRows; row++) {
                for (var d = 0; d < 7; d++) {
                    if (row === 0 && d < firstDay) {
                        var prevDay = daysInPrevMonth - firstDay + d + 1;
                        html += '<span class="calendar-date is-muted">' + prevDay + '</span>';
                    } else if (date > daysInMonth) {
                        html += '<span class="calendar-date is-muted">' + nextDate + '</span>';
                        nextDate++;
                    } else {
                        var isPastDay = false;
                        if (selectedYear < actualTodayY) {
                            isPastDay = true;
                        } else if (selectedYear === actualTodayY) {
                            if (selectedMonth < actualTodayM) {
                                isPastDay = true;
                            } else if (selectedMonth === actualTodayM && date < actualTodayD) {
                                isPastDay = true;
                            }
                        }

                        var sDate = date.toString().padStart(2, '0');
                        var isAvailableDay = isAvailMonthYear && availDays.includes(sDate) && !isPastDay;
                        var classes = "calendar-date";
                        
                        if (isPastDay) classes += " is-muted";
                        else if (isAvailableDay) classes += " is-available";
                        
                        html += '<span class="' + classes + '">' + date + '</span>';
                        date++;
                    }
                }
            }
            gridContainer.innerHTML = html;
        }

        if (monthSelect && yearSelect && gridContainer) {
            monthSelect.addEventListener("change", renderCalendar);
            yearSelect.addEventListener("change", function() {
                updateDisables();
                renderCalendar();
            });
            updateDisables();
            renderCalendar();
        }
    }
});