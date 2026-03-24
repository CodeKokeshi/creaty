document.addEventListener("DOMContentLoaded", function () {
    var revealItems = document.querySelectorAll(".reveal");
    var toggleButtons = document.querySelectorAll(".toggle-visibility");
    var authSwitchLinks = document.querySelectorAll("[data-auth-switch]");
    var promoBanner = document.querySelector(".promo-banner");
    var promoSlides = promoBanner ? promoBanner.querySelectorAll(".promo-slide") : [];
    var promoPrev = promoBanner ? promoBanner.querySelector(".promo-arrow-left") : null;
    var promoNext = promoBanner ? promoBanner.querySelector(".promo-arrow-right") : null;
    var promoIndex = 0;
    var promoTimer = null;
    var promoDelay = 3000;
    var filterNav = document.querySelector(".section-nav-interactive");
    var filterToggles = filterNav ? filterNav.querySelectorAll(".filter-toggle") : [];
    var productCards = document.querySelectorAll('.product-grid .product-card:not([data-admin-add-card="true"])');
    var productEmpty = document.querySelector(".product-grid-empty");
    var adminRemoveButtons = document.querySelectorAll("[data-admin-remove-featured]");
    var adminEditButtons = document.querySelectorAll("[data-admin-edit-featured]");
    var adminEditBackdrop = document.querySelector("[data-admin-edit-backdrop]");
    var adminEditForm = document.querySelector("[data-admin-edit-form]");
    var adminEditClose = document.querySelector("[data-admin-edit-close]");
    var adminEditCancel = document.querySelector("[data-admin-edit-cancel]");
    var adminEditDuplicate = document.querySelector("[data-admin-edit-duplicate]");
    var adminEditBrowse = document.querySelector("[data-admin-edit-browse]");
    var adminEditRecrop = document.querySelector("[data-admin-edit-recrop]");
    var adminCropWorkspace = document.querySelector("[data-admin-crop-workspace]");
    var adminEditCropCancel = document.querySelector("[data-admin-edit-crop-cancel]");
    var adminEditCropSave = document.querySelector("[data-admin-edit-crop-save]");
    var adminEditFileInput = document.querySelector("[data-admin-edit-file]");
    var adminEditPreviewWrap = document.querySelector("[data-admin-edit-image-preview]");
    var adminEditPreviewImage = document.querySelector("[data-admin-edit-preview-img]");
    var adminEditBrand = document.querySelector("[data-admin-edit-brand]");
    var adminEditName = document.querySelector("[data-admin-edit-name]");
    var adminEditSpec1 = document.querySelector("[data-admin-edit-spec1]");
    var adminEditSpec2 = document.querySelector("[data-admin-edit-spec2]");
    var adminEditPrice = document.querySelector("[data-admin-edit-price]");
    var adminEditDiscount = document.querySelector("[data-admin-edit-discount]");
    var adminEditTagline = document.querySelector("[data-admin-edit-tagline]");
    var adminEditImagingSpecs = document.querySelector("[data-admin-edit-imaging-specs]");
    var adminEditVideoSpecs = document.querySelector("[data-admin-edit-video-specs]");
    var adminEditPhysicalSpecs = document.querySelector("[data-admin-edit-physical-specs]");
    var adminEditCaptureSlides = document.querySelector("[data-admin-edit-capture-slides]");
    var adminEditZoom = document.querySelector("[data-admin-edit-zoom]");
    var activeAdminEditCard = null;
    var adminCropState = {
        zoom: 1,
        offsetX: 0,
        offsetY: 0,
        isCropping: false,
        isDragging: false,
        dragPointerId: null,
        dragStartClientX: 0,
        dragStartClientY: 0,
        dragStartOffsetX: 0,
        dragStartOffsetY: 0,
        sourceImage: "",
        previewBeforeCrop: ""
    };
    var detailGalleries = document.querySelectorAll("[data-gallery]");
    var packageSlideshows = document.querySelectorAll("[data-package-slideshow]");
    var packageSlideshowControllers = [];
    var activeFilters = {
        brand: "all",
        month: "all",
        day: "all",
        year: "all"
    };
    var monthDefinitions = [
        { value: "january", label: "January" },
        { value: "february", label: "February" },
        { value: "march", label: "March" },
        { value: "april", label: "April" },
        { value: "may", label: "May" },
        { value: "june", label: "June" },
        { value: "july", label: "July" },
        { value: "august", label: "August" },
        { value: "september", label: "September" },
        { value: "october", label: "October" },
        { value: "november", label: "November" },
        { value: "december", label: "December" }
    ];
    var monthValueToIndex = monthDefinitions.reduce(function (result, month, index) {
        result[month.value] = index;
        return result;
    }, {});
    var dateFilterPanel = filterNav ? filterNav.querySelector("#date-filter-panel") : null;
    var dateTabButtons = dateFilterPanel ? dateFilterPanel.querySelectorAll("[data-date-tab]") : [];
    var dateViews = dateFilterPanel ? dateFilterPanel.querySelectorAll("[data-date-view]") : [];
    var monthOptionsWrap = dateFilterPanel ? dateFilterPanel.querySelector("[data-date-month-options]") : null;
    var yearOptionsWrap = dateFilterPanel ? dateFilterPanel.querySelector("[data-date-year-options]") : null;
    var calendarGrid = dateFilterPanel ? dateFilterPanel.querySelector("[data-date-calendar-grid]") : null;
    var calendarTitle = dateFilterPanel ? dateFilterPanel.querySelector("[data-calendar-title]") : null;
    var availableDateKeys = new Set();
    var availableYears = [];
    var now = new Date();
    var calendarViewMonthIndex = now.getMonth();
    var calendarViewYear = now.getFullYear();
    var brandValueToLabel = {
        canon: "Canon",
        fuji: "Fuji",
        nikon: "Nikon",
        sony: "Sony"
    };
    var adminProducts = (typeof window.__creatyAdminProducts === "object" && window.__creatyAdminProducts)
        ? window.__creatyAdminProducts
        : {};

    revealItems.forEach(function (item, index) {
        window.setTimeout(function () {
            item.classList.add("is-visible");
        }, 120 * (index + 1));
    });

    if (document.body.classList.contains("login-page") && authSwitchLinks.length) {
        var switchStorageKey = "creaty-auth-switch";

        try {
            if (window.sessionStorage.getItem(switchStorageKey) === "1") {
                document.body.classList.add("is-auth-switched");
                window.sessionStorage.removeItem(switchStorageKey);
                window.setTimeout(function () {
                    document.body.classList.remove("is-auth-switched");
                }, 600);
            }
        } catch (error) {
            // Ignore storage errors in private browsing contexts.
        }

        authSwitchLinks.forEach(function (link) {
            link.addEventListener("click", function (event) {
                if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                    return;
                }

                var targetHref = link.getAttribute("href");

                if (!targetHref) {
                    return;
                }

                event.preventDefault();

                try {
                    window.sessionStorage.setItem(switchStorageKey, "1");
                } catch (error) {
                    // Ignore storage errors in private browsing contexts.
                }

                document.body.classList.add("is-auth-switching");

                window.setTimeout(function () {
                    window.location.href = targetHref;
                }, 180);
            });
        });
    }

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

    adminRemoveButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            var card = button.closest(".product-card");

            if (!card) {
                return;
            }

            card.classList.add("is-admin-removing");

            window.setTimeout(function () {
                // Intentionally client-side only for now; no persistence.
                card.setAttribute("data-admin-removed", "true");
                card.classList.remove("is-admin-removing");
                card.classList.add("is-hidden");
                applyProductFilters();
            }, 180);
        });
    });

    productCards.forEach(function (card) {
        ensureCardBrandNameData(card);
    });

    function parseMoneyValue(value) {
        var normalized = String(value || "").replace(/[^0-9.]/g, "");
        var parsed = Number.parseFloat(normalized);

        if (!Number.isFinite(parsed)) {
            return 0;
        }

        return parsed;
    }

    function formatPeso(value) {
        return "\u20B1 " + Number(value).toFixed(2);
    }

    function normalizeBrandValue(value) {
        var normalized = String(value || "").toLowerCase().trim();

        if (Object.prototype.hasOwnProperty.call(brandValueToLabel, normalized)) {
            return normalized;
        }

        return "canon";
    }

    function getBrandLabel(value) {
        var normalized = normalizeBrandValue(value);
        return brandValueToLabel[normalized] || "Canon";
    }

    function splitProductDisplayName(brandValue, fullName) {
        var normalizedBrand = normalizeBrandValue(brandValue);
        var brandLabel = getBrandLabel(normalizedBrand);
        var cleanName = String(fullName || "").trim();

        if (!cleanName) {
            return "";
        }

        var escapedBrand = brandLabel.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
        var prefixRegex = new RegExp("^" + escapedBrand + "\\s+", "i");

        return cleanName.replace(prefixRegex, "").trim();
    }

    function composeProductDisplayName(brandValue, productName) {
        var brandLabel = getBrandLabel(brandValue);
        var cleanName = String(productName || "").trim();

        if (!cleanName) {
            return brandLabel;
        }

        return brandLabel + " " + cleanName;
    }

    function ensureCardBrandNameData(card) {
        if (!card) {
            return;
        }

        var currentBrand = normalizeBrandValue(card.getAttribute("data-brand"));
        var storedName = String(card.getAttribute("data-product-name") || "").trim();
        var titleLink = card.querySelector(".product-title-link");
        var fallbackTitle = titleLink ? titleLink.textContent.trim() : "";

        if (!storedName) {
            storedName = splitProductDisplayName(currentBrand, fallbackTitle);
        }

        card.setAttribute("data-brand", currentBrand);
        card.setAttribute("data-product-name", storedName);
    }

    function linesToTextarea(value) {
        if (!Array.isArray(value)) {
            return "";
        }

        return value
            .map(function (line) {
                return String(line || "").trim();
            })
            .filter(function (line) {
                return line !== "";
            })
            .join("\n");
    }

    function textareaToLines(value) {
        return String(value || "")
            .split(/\r\n|\r|\n/)
            .map(function (line) {
                return line.trim();
            })
            .filter(function (line) {
                return line !== "";
            });
    }

    function getActiveAdminProductData() {
        if (!activeAdminEditCard) {
            return null;
        }

        var productKey = activeAdminEditCard.getAttribute("data-product-key") || "";

        if (!productKey || !adminProducts || typeof adminProducts !== "object") {
            return null;
        }

        return adminProducts[productKey] || null;
    }

    function syncAdminPreviewTransform() {
        if (!adminEditPreviewWrap || !adminEditPreviewImage || !adminEditZoom) {
            return;
        }

        adminEditPreviewWrap.style.setProperty("--admin-crop-zoom", String(adminCropState.zoom));
        adminEditPreviewWrap.style.setProperty("--admin-crop-x", String(adminCropState.offsetX) + "px");
        adminEditPreviewWrap.style.setProperty("--admin-crop-y", String(adminCropState.offsetY) + "px");
        adminEditPreviewWrap.classList.toggle("is-crop-active", adminCropState.isCropping);
    }

    function setAdminCropWorkspaceVisible(isVisible) {
        adminCropState.isCropping = isVisible;

        if (adminCropWorkspace) {
            adminCropWorkspace.hidden = !isVisible;
        }

        syncAdminPreviewTransform();
    }

    function clampAdminCropOffsets(nextX, nextY) {
        if (!adminEditPreviewWrap || !adminEditPreviewImage) {
            return { x: nextX, y: nextY };
        }

        var rect = adminEditPreviewWrap.getBoundingClientRect();
        var zoom = Math.max(1, adminCropState.zoom);
        var maxShift = Math.max(0, ((rect.width * zoom) - rect.width) / 2);
        var clampedX = Math.min(maxShift, Math.max(-maxShift, nextX));
        var clampedY = Math.min(maxShift, Math.max(-maxShift, nextY));

        return {
            x: clampedX,
            y: clampedY
        };
    }

    function resetAdminCropState() {
        adminCropState.zoom = 1;
        adminCropState.offsetX = 0;
        adminCropState.offsetY = 0;
        adminCropState.isDragging = false;
        adminCropState.dragPointerId = null;

        if (adminEditZoom) {
            adminEditZoom.value = "1";
        }

        syncAdminPreviewTransform();
    }

    function buildAdminCropDataUrlFromPreview() {
        if (!adminEditPreviewImage || !adminEditPreviewImage.src || !adminEditPreviewImage.naturalWidth || !adminEditPreviewImage.naturalHeight) {
            return null;
        }

        var size = 600;
        var canvas = document.createElement("canvas");
        canvas.width = size;
        canvas.height = size;

        var ctx = canvas.getContext("2d");
        if (!ctx) {
            return null;
        }

        var zoomValue = Math.max(1, Number(adminCropState.zoom || 1));
        var scaleToCover = Math.max(size / adminEditPreviewImage.naturalWidth, size / adminEditPreviewImage.naturalHeight);
        var scale = scaleToCover * zoomValue;
        var drawWidth = adminEditPreviewImage.naturalWidth * scale;
        var drawHeight = adminEditPreviewImage.naturalHeight * scale;
        var drawX = ((size - drawWidth) / 2) + adminCropState.offsetX;
        var drawY = ((size - drawHeight) / 2) + adminCropState.offsetY;

        ctx.clearRect(0, 0, size, size);
        ctx.drawImage(adminEditPreviewImage, drawX, drawY, drawWidth, drawHeight);

        return canvas.toDataURL("image/png");
    }

    function clampDiscount(value) {
        var parsed = Number.parseInt(String(value || "0"), 10);

        if (!Number.isFinite(parsed)) {
            return 0;
        }

        return Math.min(95, Math.max(0, parsed));
    }

    function closeAdminEditModal() {
        if (!adminEditBackdrop) {
            return;
        }

        setAdminCropWorkspaceVisible(false);
        resetAdminCropState();

        adminEditBackdrop.hidden = true;
        document.body.classList.remove("admin-modal-open");
        activeAdminEditCard = null;
    }

    function openAdminEditModal(card) {
        if (!adminEditBackdrop || !adminEditForm || !card) {
            return;
        }

        activeAdminEditCard = card;
        ensureCardBrandNameData(card);

        var cardImage = card.querySelector(".product-visual-image");
        var titleLink = card.querySelector(".product-title-link");
        var currentBrandValue = normalizeBrandValue(card.getAttribute("data-brand"));
        var storedProductName = String(card.getAttribute("data-product-name") || "").trim();
        var copyParagraphs = card.querySelectorAll(".product-copy > p");
        var specOne = copyParagraphs[0] ? copyParagraphs[0].textContent.trim() : "";
        var specTwo = copyParagraphs[1] ? copyParagraphs[1].textContent.trim() : "";
        var displayedTitle = titleLink ? titleLink.textContent.trim() : "";
        var productNameValue = storedProductName || splitProductDisplayName(currentBrandValue, displayedTitle);
        var priceParagraph = copyParagraphs[2] || null;
        var priceOriginal = 0;
        var discountPercent = 0;

        if (priceParagraph) {
            var spans = priceParagraph.querySelectorAll("span");

            if (spans.length >= 2) {
                priceOriginal = parseMoneyValue(spans[0].textContent);
                var discountedPrice = parseMoneyValue(spans[1].textContent);

                if (priceOriginal > 0 && discountedPrice > 0 && discountedPrice < priceOriginal) {
                    discountPercent = Math.round((1 - (discountedPrice / priceOriginal)) * 100);
                }
            } else {
                priceOriginal = parseMoneyValue(priceParagraph.textContent);
            }
        }

        if (adminEditPreviewImage) {
            adminEditPreviewImage.src = cardImage ? cardImage.src : "";
        }

        if (adminEditBrand) {
            adminEditBrand.value = currentBrandValue;
        }

        if (adminEditName) {
            adminEditName.value = productNameValue;
        }

        if (adminEditSpec1) {
            adminEditSpec1.value = specOne;
        }

        if (adminEditSpec2) {
            adminEditSpec2.value = specTwo;
        }

        if (adminEditPrice) {
            adminEditPrice.value = priceOriginal > 0 ? String(priceOriginal.toFixed(2)) : "";
        }

        if (adminEditDiscount) {
            adminEditDiscount.value = String(discountPercent);
        }

        var activeProductData = getActiveAdminProductData() || {};
        var specsMap = activeProductData.specs && typeof activeProductData.specs === "object" ? activeProductData.specs : {};

        if (adminEditTagline) {
            adminEditTagline.value = String(activeProductData.tagline || "");
        }

        if (adminEditImagingSpecs) {
            adminEditImagingSpecs.value = linesToTextarea(specsMap["Imaging and Performance"] || []);
        }

        if (adminEditVideoSpecs) {
            adminEditVideoSpecs.value = linesToTextarea(specsMap.Video || []);
        }

        if (adminEditPhysicalSpecs) {
            adminEditPhysicalSpecs.value = linesToTextarea(specsMap["Physical Specifications"] || []);
        }

        if (adminEditCaptureSlides) {
            adminEditCaptureSlides.value = linesToTextarea(activeProductData.captureSlides || []);
        }

        if (adminEditFileInput) {
            adminEditFileInput.value = "";
        }

        adminCropState.previewBeforeCrop = adminEditPreviewImage ? adminEditPreviewImage.src : "";
        adminCropState.sourceImage = "";
        resetAdminCropState();
        setAdminCropWorkspaceVisible(false);

        adminEditBackdrop.hidden = false;
        document.body.classList.add("admin-modal-open");
    }

    if (adminEditZoom) {
        adminEditZoom.addEventListener("input", function () {
            adminCropState.zoom = Number.parseFloat(adminEditZoom.value) || 1;
            var clamped = clampAdminCropOffsets(adminCropState.offsetX, adminCropState.offsetY);
            adminCropState.offsetX = clamped.x;
            adminCropState.offsetY = clamped.y;
            syncAdminPreviewTransform();
        });
    }

    if (adminEditBrowse && adminEditFileInput) {
        adminEditBrowse.addEventListener("click", function () {
            adminEditFileInput.click();
        });
    }

    if (adminEditRecrop && adminEditPreviewImage) {
        adminEditRecrop.addEventListener("click", function () {
            if (!adminEditPreviewImage.src) {
                return;
            }

            adminCropState.previewBeforeCrop = adminEditPreviewImage.src;
            adminCropState.sourceImage = adminEditPreviewImage.src;
            resetAdminCropState();
            setAdminCropWorkspaceVisible(true);
            syncAdminPreviewTransform();
        });
    }

    if (adminEditFileInput && adminEditPreviewImage) {
        adminEditPreviewImage.addEventListener("dragstart", function (event) {
            event.preventDefault();
        });

        adminEditFileInput.addEventListener("change", function () {
            var file = adminEditFileInput.files && adminEditFileInput.files[0] ? adminEditFileInput.files[0] : null;

            if (!file) {
                return;
            }

            var reader = new FileReader();
            reader.onload = function (event) {
                adminCropState.previewBeforeCrop = adminEditPreviewImage.src || "";
                adminCropState.sourceImage = String(event.target && event.target.result ? event.target.result : "");
                adminEditPreviewImage.src = adminCropState.sourceImage;
                resetAdminCropState();
                setAdminCropWorkspaceVisible(true);
                syncAdminPreviewTransform();
            };
            reader.readAsDataURL(file);
        });
    }

    if (adminEditPreviewWrap) {
        adminEditPreviewWrap.addEventListener("wheel", function (event) {
            if (!adminCropState.isCropping || !adminEditZoom) {
                return;
            }

            event.preventDefault();

            var minZoom = Number.parseFloat(adminEditZoom.min || "1");
            var maxZoom = Number.parseFloat(adminEditZoom.max || "3");
            var stepZoom = Number.parseFloat(adminEditZoom.step || "0.01");
            var direction = event.deltaY < 0 ? 1 : -1;
            var nextZoom = adminCropState.zoom + (direction * (stepZoom * 5));

            nextZoom = Math.min(maxZoom, Math.max(minZoom, nextZoom));
            nextZoom = Math.round(nextZoom * 100) / 100;

            adminCropState.zoom = nextZoom;
            adminEditZoom.value = String(nextZoom);

            var clamped = clampAdminCropOffsets(adminCropState.offsetX, adminCropState.offsetY);
            adminCropState.offsetX = clamped.x;
            adminCropState.offsetY = clamped.y;
            syncAdminPreviewTransform();
        }, { passive: false });

        adminEditPreviewWrap.addEventListener("pointerdown", function (event) {
            if (!adminCropState.isCropping || event.button !== 0) {
                return;
            }

            event.preventDefault();

            adminCropState.isDragging = true;
            adminCropState.dragPointerId = event.pointerId;
            adminCropState.dragStartClientX = event.clientX;
            adminCropState.dragStartClientY = event.clientY;
            adminCropState.dragStartOffsetX = adminCropState.offsetX;
            adminCropState.dragStartOffsetY = adminCropState.offsetY;
            adminEditPreviewWrap.setPointerCapture(event.pointerId);
        });

        adminEditPreviewWrap.addEventListener("pointermove", function (event) {
            if (!adminCropState.isCropping || !adminCropState.isDragging || adminCropState.dragPointerId !== event.pointerId) {
                return;
            }

            var nextX = adminCropState.dragStartOffsetX + (event.clientX - adminCropState.dragStartClientX);
            var nextY = adminCropState.dragStartOffsetY + (event.clientY - adminCropState.dragStartClientY);
            var clamped = clampAdminCropOffsets(nextX, nextY);
            adminCropState.offsetX = clamped.x;
            adminCropState.offsetY = clamped.y;
            syncAdminPreviewTransform();
        });

        function stopAdminCropDrag(event) {
            if (!adminCropState.isDragging || adminCropState.dragPointerId !== event.pointerId) {
                return;
            }

            adminCropState.isDragging = false;
            adminCropState.dragPointerId = null;
            adminEditPreviewWrap.releasePointerCapture(event.pointerId);
        }

        adminEditPreviewWrap.addEventListener("pointerup", stopAdminCropDrag);
        adminEditPreviewWrap.addEventListener("pointercancel", stopAdminCropDrag);
    }

    if (adminEditCropCancel && adminEditPreviewImage) {
        adminEditCropCancel.addEventListener("click", function () {
            adminEditPreviewImage.src = adminCropState.previewBeforeCrop || adminEditPreviewImage.src;
            adminCropState.sourceImage = "";
            resetAdminCropState();
            setAdminCropWorkspaceVisible(false);
        });
    }

    if (adminEditCropSave && adminEditPreviewImage) {
        adminEditCropSave.addEventListener("click", function () {
            var croppedDataUrl = buildAdminCropDataUrlFromPreview();

            if (!croppedDataUrl) {
                return;
            }

            adminEditPreviewImage.src = croppedDataUrl;
            adminCropState.previewBeforeCrop = croppedDataUrl;
            adminCropState.sourceImage = "";
            resetAdminCropState();
            setAdminCropWorkspaceVisible(false);
        });
    }

    adminEditButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            var card = button.closest(".product-card");
            openAdminEditModal(card);
        });
    });

    if (adminEditClose) {
        adminEditClose.addEventListener("click", closeAdminEditModal);
    }

    if (adminEditCancel) {
        adminEditCancel.addEventListener("click", closeAdminEditModal);
    }

    if (adminEditBackdrop) {
        adminEditBackdrop.addEventListener("click", function (event) {
            if (event.target === adminEditBackdrop) {
                closeAdminEditModal();
            }
        });
    }

    if (adminEditDuplicate) {
        adminEditDuplicate.addEventListener("click", function () {
            if (!activeAdminEditCard || !adminEditBackdrop) {
                return;
            }

            var productKey = activeAdminEditCard.getAttribute("data-product-key");
            var endpoint = adminEditBackdrop.getAttribute("data-admin-duplicate-endpoint") || "";

            if (!productKey || !endpoint) {
                return;
            }

            adminEditDuplicate.disabled = true;
            adminEditDuplicate.textContent = "Duplicating...";

            fetch(endpoint, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    productKey: productKey
                })
            })
                .then(function (response) {
                    return response.json().then(function (payload) {
                        return {
                            ok: response.ok,
                            payload: payload
                        };
                    });
                })
                .then(function (result) {
                    if (!result.ok || !result.payload || !result.payload.ok) {
                        var message = result.payload && result.payload.message ? result.payload.message : "Unable to duplicate product.";
                        throw new Error(message);
                    }

                    window.location.reload();
                })
                .catch(function (error) {
                    window.alert(error.message || "Unable to duplicate product.");
                    adminEditDuplicate.disabled = false;
                    adminEditDuplicate.textContent = "Duplicate Product";
                });
        });
    }

    if (adminEditForm) {
        adminEditForm.addEventListener("submit", function (event) {
            event.preventDefault();

            if (!activeAdminEditCard || !adminEditBackdrop) {
                closeAdminEditModal();
                return;
            }

            var productKey = activeAdminEditCard.getAttribute("data-product-key") || "";
            var updateEndpoint = adminEditBackdrop.getAttribute("data-admin-update-endpoint") || "";
            var brandValue = normalizeBrandValue(adminEditBrand ? adminEditBrand.value : "canon");
            var nameValue = adminEditName ? adminEditName.value.trim() : "";
            var specOneValue = adminEditSpec1 ? adminEditSpec1.value.trim() : "";
            var specTwoValue = adminEditSpec2 ? adminEditSpec2.value.trim() : "";
            var priceValue = adminEditPrice ? Number.parseFloat(adminEditPrice.value) : 0;
            var discountValue = clampDiscount(adminEditDiscount ? adminEditDiscount.value : 0);
            var taglineValue = adminEditTagline ? adminEditTagline.value.trim() : "";
            var imagingSpecsValue = textareaToLines(adminEditImagingSpecs ? adminEditImagingSpecs.value : "");
            var videoSpecsValue = textareaToLines(adminEditVideoSpecs ? adminEditVideoSpecs.value : "");
            var physicalSpecsValue = textareaToLines(adminEditPhysicalSpecs ? adminEditPhysicalSpecs.value : "");
            var captureSlidesValue = textareaToLines(adminEditCaptureSlides ? adminEditCaptureSlides.value : "");

            if (!productKey || !updateEndpoint || !nameValue || !specOneValue || !specTwoValue || !Number.isFinite(priceValue) || priceValue < 0) {
                return;
            }

            var finalPreviewSrc = adminEditPreviewImage ? adminEditPreviewImage.src : "";

            if (adminCropState.isCropping) {
                var autoCroppedDataUrl = buildAdminCropDataUrlFromPreview();

                if (autoCroppedDataUrl && adminEditPreviewImage) {
                    finalPreviewSrc = autoCroppedDataUrl;
                    adminEditPreviewImage.src = autoCroppedDataUrl;
                    adminCropState.previewBeforeCrop = autoCroppedDataUrl;
                }

                adminCropState.sourceImage = "";
                resetAdminCropState();
                setAdminCropWorkspaceVisible(false);
            }

            var submitButton = adminEditForm.querySelector('button[type="submit"]');
            var previousSubmitText = submitButton ? submitButton.textContent : "";

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = "Saving...";
            }

            fetch(updateEndpoint, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    productKey: productKey,
                    brand: brandValue,
                    name: nameValue,
                    spec1: specOneValue,
                    spec2: specTwoValue,
                    price: priceValue,
                    discountPercent: discountValue,
                    tagline: taglineValue,
                    imagingSpecs: imagingSpecsValue,
                    videoSpecs: videoSpecsValue,
                    physicalSpecs: physicalSpecsValue,
                    captureSlides: captureSlidesValue,
                    imageDataUrl: finalPreviewSrc
                })
            })
                .then(function (response) {
                    return response.json().then(function (payload) {
                        return {
                            ok: response.ok,
                            payload: payload
                        };
                    });
                })
                .then(function (result) {
                    if (!result.ok || !result.payload || !result.payload.ok) {
                        var message = result.payload && result.payload.message ? result.payload.message : "Unable to save product changes.";
                        throw new Error(message);
                    }

                    window.location.reload();
                })
                .catch(function (error) {
                    window.alert(error.message || "Unable to save product changes.");

                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.textContent = previousSubmitText || "Save Changes";
                    }
                });
        });
    }

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

        var effectiveToday = getEffectiveToday();
        var visibleCount = 0;

        productCards.forEach(function (card) {
            if (card.getAttribute("data-admin-removed") === "true") {
                card.classList.add("is-hidden");
                return;
            }

            var cardBrand = normalizeFilterValue(card.getAttribute("data-brand"));
            var brandMatches = activeFilters.brand === "all" || activeFilters.brand === cardBrand;
            var hasDateFilter = activeFilters.month !== "all" || activeFilters.day !== "all" || activeFilters.year !== "all";
            var matches = brandMatches;

            if (matches && hasDateFilter) {
                var selectedYear = activeFilters.year === "all" ? calendarViewYear : Number.parseInt(activeFilters.year, 10);
                var selectedMonthIndex = activeFilters.month === "all" ? calendarViewMonthIndex : monthValueToIndex[activeFilters.month];
                var selectedDay = activeFilters.day === "all" ? null : Number.parseInt(activeFilters.day, 10);

                if (!Number.isFinite(selectedYear)) {
                    matches = false;
                } else if (selectedDay !== null && Number.isFinite(selectedYear) && Number.isFinite(selectedMonthIndex)) {
                    var targetDate = new Date(selectedYear, selectedMonthIndex, selectedDay);
                    matches = targetDate >= effectiveToday;
                } else if (activeFilters.month !== "all" && Number.isFinite(selectedYear) && Number.isFinite(selectedMonthIndex)) {
                    matches = selectedYear > effectiveToday.getFullYear() || (selectedYear === effectiveToday.getFullYear() && selectedMonthIndex >= effectiveToday.getMonth());
                } else if (activeFilters.year !== "all" && Number.isFinite(selectedYear)) {
                    matches = selectedYear >= effectiveToday.getFullYear();
                }
            }

            card.classList.toggle("is-hidden", !matches);

            if (matches) {
                visibleCount += 1;
            }
        });

        if (productEmpty) {
            productEmpty.hidden = visibleCount !== 0;
        }
    }

    function padTwo(value) {
        return String(value).padStart(2, "0");
    }

    function getStartOfDay(dateValue) {
        var date = new Date(dateValue.getFullYear(), dateValue.getMonth(), dateValue.getDate());
        date.setHours(0, 0, 0, 0);
        return date;
    }

    function getEffectiveToday() {
        return getStartOfDay(new Date());
    }

    function dateKeyFromDate(dateValue) {
        return String(dateValue.getFullYear()) + "-" + padTwo(dateValue.getMonth() + 1) + "-" + padTwo(dateValue.getDate());
    }

    function extractProductKeyFromHref(hrefValue) {
        if (!hrefValue) {
            return "";
        }

        var productMatch = hrefValue.match(/[?&]product=([^&#]+)/i);

        if (!productMatch || !productMatch[1]) {
            return "";
        }

        try {
            return decodeURIComponent(productMatch[1]).toLowerCase();
        } catch (error) {
            return String(productMatch[1]).toLowerCase();
        }
    }

    function getCardProductKey(card) {
        if (!card) {
            return "";
        }

        var existingKey = normalizeFilterValue(card.getAttribute("data-product-key"));

        if (existingKey && existingKey !== "all") {
            return existingKey;
        }

        var productLink = card.querySelector(".product-visual-link") || card.querySelector(".product-title-link");
        var parsedKey = extractProductKeyFromHref(productLink ? productLink.getAttribute("href") : "");

        if (parsedKey) {
            card.setAttribute("data-product-key", parsedKey);
        }

        return parsedKey;
    }

    function collectAvailableYearsFromCards() {
        var years = new Set();
        var baseYear = getEffectiveToday().getFullYear();

        years.add(baseYear);
        years.add(baseYear + 1);
        years.add(baseYear + 2);

        return Array.from(years).sort(function (left, right) {
            return left - right;
        });
    }

    function syncCalendarViewFromFilters() {
        var effectiveToday = getEffectiveToday();
        var yearPool = availableYears.length ? availableYears : collectAvailableYearsFromCards();
        var minYear = yearPool[0];
        var maxYear = yearPool[yearPool.length - 1];

        if (activeFilters.year !== "all") {
            var parsedYear = Number.parseInt(activeFilters.year, 10);

            if (Number.isFinite(parsedYear)) {
                calendarViewYear = parsedYear;
            }
        } else if (!Number.isFinite(calendarViewYear)) {
            calendarViewYear = effectiveToday.getFullYear();
        }

        calendarViewYear = Math.min(Math.max(calendarViewYear, minYear), maxYear);

        if (activeFilters.month !== "all" && monthValueToIndex[activeFilters.month] !== undefined) {
            calendarViewMonthIndex = monthValueToIndex[activeFilters.month];
        } else if (!Number.isFinite(calendarViewMonthIndex) || calendarViewMonthIndex < 0 || calendarViewMonthIndex > 11) {
            calendarViewMonthIndex = calendarViewYear === effectiveToday.getFullYear() ? effectiveToday.getMonth() : 0;
        }
    }

    function normalizeFilterValue(value) {
        return String(value || "all").toLowerCase();
    }

    function getMonthLabel(monthValue) {
        var normalized = normalizeFilterValue(monthValue);
        var month = monthDefinitions.find(function (item) {
            return item.value === normalized;
        });

        return month ? month.label : "Month";
    }

    function syncFilterButtons(group) {
        if (!filterNav) {
            return;
        }

        var selected = activeFilters[group];

        filterNav.querySelectorAll('.filter-option[data-filter-group="' + group + '"]').forEach(function (option) {
            var optionValue = normalizeFilterValue(option.getAttribute("data-filter-value"));
            option.classList.toggle("is-selected", optionValue === selected);
        });
    }

    function setActiveFilter(group, value, options) {
        var config = options || {};
        var normalizedValue = normalizeFilterValue(value);

        activeFilters[group] = normalizedValue;
        syncFilterButtons(group);

        if (!config.skipApply) {
            applyProductFilters();
        }
    }

    function buildDateAvailability() {
        var effectiveToday = getEffectiveToday();
        var daysInVisibleMonth = new Date(calendarViewYear, calendarViewMonthIndex + 1, 0).getDate();
        availableDateKeys.clear();

        for (var day = 1; day <= daysInVisibleMonth; day += 1) {
            var candidateDate = new Date(calendarViewYear, calendarViewMonthIndex, day);

            if (candidateDate >= effectiveToday) {
                availableDateKeys.add(dateKeyFromDate(candidateDate));
            }
        }

        availableYears = collectAvailableYearsFromCards();

        if (!Number.isFinite(calendarViewYear)) {
            calendarViewYear = effectiveToday.getFullYear();
        }

        calendarViewYear = Math.min(Math.max(calendarViewYear, availableYears[0]), availableYears[availableYears.length - 1]);

        if (!Number.isFinite(calendarViewMonthIndex) || calendarViewMonthIndex < 0 || calendarViewMonthIndex > 11) {
            calendarViewMonthIndex = calendarViewYear === effectiveToday.getFullYear() ? effectiveToday.getMonth() : 0;
        }
    }

    function setActiveDateView(nextView) {
        if (!dateTabButtons.length || !dateViews.length) {
            return;
        }

        dateTabButtons.forEach(function (button) {
            var isSelected = button.getAttribute("data-date-tab") === nextView;
            button.classList.toggle("is-active", isSelected);
            button.setAttribute("aria-selected", isSelected ? "true" : "false");
        });

        dateViews.forEach(function (view) {
            view.classList.toggle("is-active", view.getAttribute("data-date-view") === nextView);
        });

        if (nextView === "day") {
            renderCalendarView();
        }
    }

    function updateDateTabLabels() {
        if (!dateTabButtons.length) {
            return;
        }

        dateTabButtons.forEach(function (button) {
            var tab = button.getAttribute("data-date-tab");
            button.textContent = tab ? tab.charAt(0).toUpperCase() + tab.slice(1) : "Date";
        });
    }

    function createDateOptionButton(group, value, label) {
        var button = document.createElement("button");
        var normalizedValue = normalizeFilterValue(value);

        button.type = "button";
        button.className = "filter-option";
        button.setAttribute("data-filter-group", group);
        button.setAttribute("data-filter-value", normalizedValue);
        button.textContent = label;
        button.classList.toggle("is-selected", activeFilters[group] === normalizedValue);

        return button;
    }

    function renderMonthOptions() {
        if (!monthOptionsWrap) {
            return;
        }

        var effectiveToday = getEffectiveToday();
        var selectedYear = activeFilters.year === "all" ? effectiveToday.getFullYear() : Number.parseInt(activeFilters.year, 10);

        monthOptionsWrap.innerHTML = "";
        monthOptionsWrap.appendChild(createDateOptionButton("month", "all", "All Months"));

        monthDefinitions.forEach(function (month, monthIndex) {
            var option = createDateOptionButton("month", month.value, month.label);
            var isPast = selectedYear < effectiveToday.getFullYear() || (selectedYear === effectiveToday.getFullYear() && monthIndex < effectiveToday.getMonth());

            if (isPast) {
                option.classList.add("is-past");
                option.disabled = true;
                option.setAttribute("aria-disabled", "true");
            }

            monthOptionsWrap.appendChild(option);
        });
    }

    function renderYearOptions() {
        if (!yearOptionsWrap) {
            return;
        }

        var effectiveToday = getEffectiveToday();

        yearOptionsWrap.innerHTML = "";
        yearOptionsWrap.appendChild(createDateOptionButton("year", "all", "All Years"));

        availableYears.forEach(function (yearValue) {
            var option = createDateOptionButton("year", String(yearValue), String(yearValue));

            if (yearValue < effectiveToday.getFullYear()) {
                option.classList.add("is-past");
                option.disabled = true;
                option.setAttribute("aria-disabled", "true");
            }

            yearOptionsWrap.appendChild(option);
        });
    }

    function renderCalendarView() {
        if (!calendarGrid) {
            return;
        }

        if (activeFilters.month !== "all" && monthValueToIndex[activeFilters.month] !== undefined) {
            calendarViewMonthIndex = monthValueToIndex[activeFilters.month];
        }

        if (activeFilters.year !== "all") {
            var parsedYear = Number.parseInt(activeFilters.year, 10);

            if (Number.isFinite(parsedYear)) {
                calendarViewYear = parsedYear;
            }
        }

        var monthLabel = monthDefinitions[calendarViewMonthIndex].label;
        var firstWeekday = new Date(calendarViewYear, calendarViewMonthIndex, 1).getDay();
        var totalDays = new Date(calendarViewYear, calendarViewMonthIndex + 1, 0).getDate();
        var effectiveToday = getEffectiveToday();

        calendarGrid.innerHTML = "";

        if (calendarTitle) {
            calendarTitle.textContent = monthLabel + " " + calendarViewYear;
        }

        for (var padIndex = 0; padIndex < firstWeekday; padIndex += 1) {
            var padCell = document.createElement("span");
            padCell.className = "date-calendar-cell is-empty";
            padCell.setAttribute("aria-hidden", "true");
            calendarGrid.appendChild(padCell);
        }

        for (var day = 1; day <= totalDays; day += 1) {
            var dayValue = padTwo(day);
            var monthValue = padTwo(calendarViewMonthIndex + 1);
            var dateKey = String(calendarViewYear) + "-" + monthValue + "-" + dayValue;
            var dayDate = new Date(calendarViewYear, calendarViewMonthIndex, day);
            var isPastDay = dayDate < effectiveToday;
            var isAvailable = availableDateKeys.has(dateKey);
            var dayButton = document.createElement("button");

            dayButton.type = "button";
            dayButton.className = "date-calendar-cell date-calendar-day";
            dayButton.textContent = String(day);
            dayButton.setAttribute("data-calendar-day", dayValue);
            dayButton.setAttribute("aria-label", monthLabel + " " + day + ", " + calendarViewYear);

            if (isPastDay) {
                dayButton.classList.add("is-past");
                dayButton.disabled = true;
            } else if (isAvailable) {
                dayButton.classList.add("is-available");
            } else {
                dayButton.classList.add("is-unavailable");
                dayButton.disabled = true;
            }

            if (
                activeFilters.day === dayValue &&
                activeFilters.month === monthDefinitions[calendarViewMonthIndex].value &&
                activeFilters.year === String(calendarViewYear)
            ) {
                dayButton.classList.add("is-selected");
            }

            calendarGrid.appendChild(dayButton);
        }

        syncFilterButtons("day");
    }

    function refreshDatePickerUI() {
        syncCalendarViewFromFilters();
        buildDateAvailability();
        renderMonthOptions();
        renderYearOptions();
        updateDateTabLabels();
        renderCalendarView();
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
        refreshDatePickerUI();

        filterToggles.forEach(function (toggle) {
            toggle.addEventListener("click", function () {
                var panelId = toggle.getAttribute("aria-controls");
                var expanded = toggle.getAttribute("aria-expanded") === "true";

                closeFilterPanels(expanded ? null : panelId);
            });
        });

        filterNav.addEventListener("click", function (event) {
            var target = event.target;
            var dateTabButton = target.closest("[data-date-tab]");

            if (dateTabButton && dateFilterPanel && dateFilterPanel.contains(dateTabButton)) {
                setActiveDateView(dateTabButton.getAttribute("data-date-tab"));
                return;
            }

            var dayButton = target.closest("[data-calendar-day]");

            if (dayButton && dateFilterPanel && dateFilterPanel.contains(dayButton)) {
                var selectedDay = dayButton.getAttribute("data-calendar-day");
                var selectedMonth = monthDefinitions[calendarViewMonthIndex].value;

                setActiveFilter("month", selectedMonth, { skipApply: true });
                setActiveFilter("year", String(calendarViewYear), { skipApply: true });
                setActiveFilter("day", selectedDay);
                refreshDatePickerUI();
                return;
            }

            var option = target.closest(".filter-option");

            if (!option || !filterNav.contains(option)) {
                return;
            }

            var group = option.getAttribute("data-filter-group");
            var value = option.getAttribute("data-filter-value");

            if (!group || !value) {
                return;
            }

            setActiveFilter(group, value);
            refreshDatePickerUI();
        });

        document.addEventListener("click", function (event) {
            var clickPath = typeof event.composedPath === "function" ? event.composedPath() : [];
            var clickedInsideFilterNav = clickPath.includes(filterNav) || filterNav.contains(event.target);

            if (!clickedInsideFilterNav) {
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
        var calendarDays = ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"];
        var calendarProductKey = (calendarCard.getAttribute("data-product-key") || "").toLowerCase();

        if (!calendarProductKey) {
            calendarProductKey = extractProductKeyFromHref(window.location.search || "");
        }

        function setStartOfDay(dateValue) {
            var date = new Date(dateValue.getFullYear(), dateValue.getMonth(), dateValue.getDate());
            date.setHours(0, 0, 0, 0);
            return date;
        }

        function getCalendarToday() {
            return setStartOfDay(new Date());
        }

        function rebuildYearOptions() {
            if (!yearSelect) {
                return;
            }

            yearSelect.innerHTML = "";

            var startYear = getCalendarToday().getFullYear();
            var endYear = startYear + 2;

            for (var yearValue = startYear; yearValue <= endYear; yearValue += 1) {
                var option = document.createElement("option");
                option.value = String(yearValue);
                option.textContent = String(yearValue);
                yearSelect.appendChild(option);
            }
        }

        function updateDisables() {
            var today = getCalendarToday();
            var currentY = today.getFullYear();
            var currentM = today.getMonth();

            Array.from(yearSelect.options).forEach(function (opt) {
                var y = Number.parseInt(opt.value, 10);
                opt.disabled = y < currentY;
            });

            if (yearSelect.options[yearSelect.selectedIndex] && yearSelect.options[yearSelect.selectedIndex].disabled) {
                var firstAbleYear = Array.from(yearSelect.options).find(function (o) {
                    return !o.disabled;
                });

                if (firstAbleYear) {
                    yearSelect.value = firstAbleYear.value;
                }
            }

            var selectedYear = Number.parseInt(yearSelect.value, 10);

            Array.from(monthSelect.options).forEach(function (opt) {
                var monthIndex = Number.parseInt(opt.value, 10);
                var isPastMonth = selectedYear < currentY || (selectedYear === currentY && monthIndex < currentM);
                opt.disabled = isPastMonth;
            });

            if (monthSelect.options[monthSelect.selectedIndex] && monthSelect.options[monthSelect.selectedIndex].disabled) {
                var firstAvailableMonth = Array.from(monthSelect.options).find(function (o) {
                    return !o.disabled;
                });

                if (firstAvailableMonth) {
                    monthSelect.value = firstAvailableMonth.value;
                }
            }
        }

        function renderCalendar() {
            var selectedMonth = parseInt(monthSelect.value, 10);
            var selectedYear = parseInt(yearSelect.value, 10);

            var firstDay = new Date(selectedYear, selectedMonth, 1).getDay();
            var daysInMonth = new Date(selectedYear, selectedMonth + 1, 0).getDate();
            var daysInPrevMonth = new Date(selectedYear, selectedMonth, 0).getDate();
            var effectiveToday = getCalendarToday();

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
                        var currentDate = new Date(selectedYear, selectedMonth, date);
                        var isPastDay = currentDate < effectiveToday;
                        var isAvailableDay = !isPastDay;
                        var classes = "calendar-date";

                        if (isPastDay) {
                            classes += " is-past";
                        } else if (isAvailableDay) {
                            classes += " is-available";
                        }
                        
                        html += '<span class="' + classes + '">' + date + '</span>';
                        date++;
                    }
                }
            }
            gridContainer.innerHTML = html;
        }

        if (monthSelect && yearSelect && gridContainer) {
            rebuildYearOptions();

            var startupToday = getCalendarToday();
            yearSelect.value = String(startupToday.getFullYear());
            monthSelect.value = String(startupToday.getMonth());

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