document.addEventListener("DOMContentLoaded", function () {
    var revealItems = document.querySelectorAll(".reveal");
    var toggleButtons = document.querySelectorAll(".toggle-visibility");
    var authSwitchLinks = document.querySelectorAll("[data-auth-switch]");
    var messageUsOpenButtons = document.querySelectorAll("[data-message-us-open]");
    var customerMessageModal = document.querySelector("[data-message-modal]");
    var customerMessageModalCloseButtons = customerMessageModal ? customerMessageModal.querySelectorAll("[data-message-modal-close]") : [];
    var customerMessageForm = customerMessageModal ? customerMessageModal.querySelector("[data-message-form]") : null;
    var customerMessageSubmitButton = customerMessageModal ? customerMessageModal.querySelector("[data-message-submit]") : null;
    var customerMessageFeedback = customerMessageModal ? customerMessageModal.querySelector("[data-message-feedback]") : null;
    var customerMessageAttachmentInput = customerMessageModal ? customerMessageModal.querySelector("[data-message-attachments]") : null;
    var adminNotificationItems = document.querySelectorAll("[data-admin-notification-item]");
    var adminNotificationModal = document.querySelector("[data-admin-notification-modal]");
    var adminNotificationModalCloseButtons = adminNotificationModal ? adminNotificationModal.querySelectorAll("[data-admin-notification-close]") : [];
    var adminNotificationModalTitle = adminNotificationModal ? adminNotificationModal.querySelector("[data-admin-notification-modal-title]") : null;
    var adminNotificationModalType = adminNotificationModal ? adminNotificationModal.querySelector("[data-admin-notification-modal-type]") : null;
    var adminNotificationModalSender = adminNotificationModal ? adminNotificationModal.querySelector("[data-admin-notification-modal-sender]") : null;
    var adminNotificationModalEmail = adminNotificationModal ? adminNotificationModal.querySelector("[data-admin-notification-modal-email]") : null;
    var adminNotificationModalTime = adminNotificationModal ? adminNotificationModal.querySelector("[data-admin-notification-modal-time]") : null;
    var adminNotificationModalSummary = adminNotificationModal ? adminNotificationModal.querySelector("[data-admin-notification-modal-summary]") : null;
    var adminNotificationModalMessage = adminNotificationModal ? adminNotificationModal.querySelector("[data-admin-notification-modal-message]") : null;
    var adminNotificationModalAttachments = adminNotificationModal ? adminNotificationModal.querySelector("[data-admin-notification-modal-attachments]") : null;
    var adminNotificationModalEmpty = adminNotificationModal ? adminNotificationModal.querySelector("[data-admin-notification-modal-empty]") : null;
    var adminNotificationCountBadges = document.querySelectorAll("[data-admin-notification-count], .topbar-notification-count");
    var adminNotificationMarkReadEndpoint = typeof window.__creatyAdminNotificationMarkReadEndpoint === "string"
        ? String(window.__creatyAdminNotificationMarkReadEndpoint || "")
        : "";
    var promoBanner = document.querySelector(".promo-banner");
    var promoCarousel = promoBanner ? promoBanner.querySelector(".promo-carousel") : null;
    var promoPrev = promoBanner ? promoBanner.querySelector(".promo-arrow-left") : null;
    var promoNext = promoBanner ? promoBanner.querySelector(".promo-arrow-right") : null;
    var adminPromoBanner = document.querySelector("[data-admin-promo-banner]");
    var adminPromoRemove = document.querySelector("[data-admin-promo-remove]");
    var adminPromoArchiveEndpoint = adminPromoBanner ? (adminPromoBanner.getAttribute("data-admin-promo-archive-endpoint") || "") : "";
    var adminPromoRestoreEndpoint = adminPromoBanner ? (adminPromoBanner.getAttribute("data-admin-promo-restore-endpoint") || "") : "";
    var adminPromoUpdateEndpoint = adminPromoBanner ? (adminPromoBanner.getAttribute("data-admin-promo-update-endpoint") || "") : "";
    var adminPromoImageBase = adminPromoBanner ? (adminPromoBanner.getAttribute("data-admin-promo-image-base") || "assets/promo_images/") : "assets/promo_images/";
    var adminPromoEditBackdrop = document.querySelector("[data-admin-promo-edit-backdrop]");
    var adminPromoForm = document.querySelector("[data-admin-promo-form]");
    var adminPromoClose = document.querySelector("[data-admin-promo-close]");
    var adminPromoCancel = document.querySelector("[data-admin-promo-cancel]");
    var adminPromoBrowse = document.querySelector("[data-admin-promo-browse]");
    var adminPromoRecrop = document.querySelector("[data-admin-promo-recrop]");
    var adminPromoImageActions = document.querySelector("[data-admin-promo-image-actions]");
    var adminPromoMainActions = document.querySelector("[data-admin-promo-main-actions]");
    var adminPromoCropWorkspace = document.querySelector("[data-admin-promo-crop-workspace]");
    var adminPromoCropCancel = document.querySelector("[data-admin-promo-crop-cancel]");
    var adminPromoCropSave = document.querySelector("[data-admin-promo-crop-save]");
    var adminPromoFileInput = document.querySelector("[data-admin-promo-file]");
    var adminPromoPreviewWrap = document.querySelector("[data-admin-promo-preview-wrap]");
    var adminPromoPreviewImage = document.querySelector("[data-admin-promo-preview-img]");
    var adminPromoZoom = document.querySelector("[data-admin-promo-zoom]");
    var adminPromoSlotNote = document.querySelector("[data-admin-promo-slot-note]");
    var promoIndex = 0;
    var promoTimer = null;
    var promoDelay = 3000;
    var filterNav = document.querySelector(".section-nav-interactive");
    var filterToggles = filterNav ? filterNav.querySelectorAll(".filter-toggle") : [];
    var adminNavBars = document.querySelectorAll("[data-admin-nav]");
    var adminUsersCreateBackdrop = document.querySelector("[data-admin-users-create-backdrop]");
    var adminUsersOpenModalButtons = document.querySelectorAll("[data-admin-users-open-modal]");
    var adminUsersCloseModalButtons = document.querySelectorAll("[data-admin-users-close-modal]");
    var shouldOpenAdminUsersCreateModal = document.body.getAttribute("data-admin-open-create-user-modal") === "true";
    var adminEquipmentArchiveBackdrop = document.querySelector("[data-admin-equipment-archive-backdrop]");
    var adminEquipmentArchiveOpenButtons = document.querySelectorAll("[data-admin-equipment-archive-open]");
    var adminEquipmentArchiveCloseButtons = document.querySelectorAll("[data-admin-equipment-archive-close]");
    var adminEquipmentStatusBackdrop = document.querySelector("[data-admin-equipment-status-backdrop]");
    var adminEquipmentStatusOpenButtons = document.querySelectorAll("[data-admin-equipment-status-open]");
    var adminEquipmentStatusCloseButtons = document.querySelectorAll("[data-admin-equipment-status-close]");
    var adminEquipmentStatusDeleteButtons = document.querySelectorAll("[data-admin-equipment-status-delete]");
    var adminEquipmentAddButtons = document.querySelectorAll("[data-admin-equipment-add]");
    var adminEquipmentRemoveButtons = document.querySelectorAll("[data-admin-equipment-remove]");
    var adminBookingRows = document.querySelectorAll("[data-admin-booking-row]");
    var adminBookingDetailBackdrop = document.querySelector("[data-admin-booking-detail-backdrop]");
    var adminBookingDetailCloseButtons = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelectorAll("[data-admin-booking-detail-close]") : [];
    var adminBookingDetailName = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-name]") : null;
    var adminBookingDetailEmail = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-email]") : null;
    var adminBookingDetailOrderNumber = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-order-number]") : null;
    var adminBookingDetailTimestamp = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-timestamp]") : null;
    var adminBookingDetailStatus = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-status]") : null;
    var adminBookingDetailPlace = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-place]") : null;
    var adminBookingDetailReceive = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-receive]") : null;
    var adminBookingDetailReturn = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-return]") : null;
    var adminBookingDetailReceivingMethod = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-receiving-method]") : null;
    var adminBookingDetailReturningMethod = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-returning-method]") : null;
    var adminBookingDetailCourier = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-courier]") : null;
    var adminBookingDetailPaymentMethod = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-payment-method]") : null;
    var adminBookingDetailItems = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-items]") : null;
    var adminBookingStatusOrderIdInput = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-status-order-id]") : null;
    var adminBookingsSource = Array.isArray(window.__creatyAdminBookings) ? window.__creatyAdminBookings : [];
    var shouldOpenAdminEquipmentArchiveModal = document.body.getAttribute("data-admin-open-equipment-archive-modal") === "true";
    var shouldOpenAdminEquipmentStatusModal = document.body.getAttribute("data-admin-open-equipment-status-modal") === "true";
    var adminActionModalBackdrop = document.querySelector("[data-admin-action-modal-backdrop]");
    var adminActionModalTitle = document.querySelector("[data-admin-action-modal-title]");
    var adminActionModalMessage = document.querySelector("[data-admin-action-modal-message]");
    var adminActionModalQuantityWrap = document.querySelector("[data-admin-action-modal-quantity-wrap]");
    var adminActionModalQuantityInput = document.querySelector("[data-admin-action-modal-quantity-input]");
    var adminActionModalConfirm = document.querySelector("[data-admin-action-modal-confirm]");
    var adminActionModalCancelButtons = document.querySelectorAll("[data-admin-action-modal-cancel], [data-admin-action-modal-close]");
    var productCards = document.querySelectorAll('.product-grid .product-card:not([data-admin-add-card="true"])');
    var adminAddCard = document.querySelector('[data-admin-add-card="true"]');
    var productEmpty = document.querySelector(".product-grid-empty");
    var adminRemoveButtons = document.querySelectorAll("[data-admin-remove-featured]");
    var adminEditButtons = document.querySelectorAll("[data-admin-edit-featured]");
    var adminEditBackdrop = document.querySelector("[data-admin-edit-backdrop]");
    var adminRestoreEndpoint = adminEditBackdrop ? (adminEditBackdrop.getAttribute("data-admin-restore-endpoint") || "") : "";
    var adminUndoToast = document.querySelector("[data-admin-undo-toast]");
    var adminUndoMessage = document.querySelector("[data-admin-undo-message]");
    var adminUndoAction = document.querySelector("[data-admin-undo-action]");
    var adminEditForm = document.querySelector("[data-admin-edit-form]");
    var adminEditClose = document.querySelector("[data-admin-edit-close]");
    var adminEditCancel = document.querySelector("[data-admin-edit-cancel]");
    var adminEditDuplicate = document.querySelector("[data-admin-edit-duplicate]");
    var adminEditBrowse = document.querySelector("[data-admin-edit-browse]");
    var adminEditRecrop = document.querySelector("[data-admin-edit-recrop]");
    var adminEditImageActions = document.querySelector("[data-admin-edit-image-actions]");
    var adminEditMainActions = document.querySelector("[data-admin-edit-main-actions]");
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
    var adminEventEditButtons = document.querySelectorAll("[data-admin-event-edit]");
    var adminEventEditBackdrop = document.querySelector("[data-admin-event-edit-backdrop]");
    var adminEventEditForm = document.querySelector("[data-admin-event-edit-form]");
    var adminEventEditClose = document.querySelector("[data-admin-event-edit-close]");
    var adminEventEditCancel = document.querySelector("[data-admin-event-edit-cancel]");
    var adminEventEditKey = document.querySelector("[data-admin-event-edit-key]");
    var adminEventEditName = document.querySelector("[data-admin-event-edit-name]");
    var adminEventEditPrice = document.querySelector("[data-admin-event-edit-price]");
    var adminEventEditDiscount = document.querySelector("[data-admin-event-edit-discount]");
    var adminEventRemoveButtons = document.querySelectorAll("[data-admin-remove-event-package]");
    var adminEventSetThumbButtonInEdit = document.querySelector("[data-admin-event-set-thumbnails-edit]");
    var adminEventThumbsBackdrop = document.querySelector("[data-admin-event-thumbs-backdrop]");
    var adminEventThumbsForm = document.querySelector("[data-admin-event-thumbs-form]");
    var adminEventThumbsClose = document.querySelector("[data-admin-event-thumbs-close]");
    var adminEventThumbsCancel = document.querySelector("[data-admin-event-thumbs-cancel]");
    var adminEventThumbsKey = document.querySelector("[data-admin-event-thumbs-key]");
    var adminEventThumbsInput = document.querySelector("[data-admin-event-thumbs-input]");
    var adminEventThumbItems = document.querySelectorAll("[data-admin-event-thumb-item]");
    var adminEventThumbsPackageTitle = document.querySelector("[data-admin-event-thumbs-package-title]");
    var adminEventThumbsFolderEmpty = document.querySelector("[data-admin-event-thumbs-folder-empty]");
    var adminEventArchiveEndpoint = adminEventEditBackdrop ? (adminEventEditBackdrop.getAttribute("data-admin-event-archive-endpoint") || "") : "";
    var adminEventRestoreEndpoint = adminEventEditBackdrop ? (adminEventEditBackdrop.getAttribute("data-admin-event-restore-endpoint") || "") : "";
    var adminHowGrid = document.querySelector("[data-admin-how-grid]");
    var adminHowEditBackdrop = document.querySelector("[data-admin-how-edit-backdrop]");
    var adminHowForm = document.querySelector("[data-admin-how-form]");
    var adminHowClose = document.querySelector("[data-admin-how-close]");
    var adminHowCancel = document.querySelector("[data-admin-how-cancel]");
    var adminHowBrowse = document.querySelector("[data-admin-how-browse]");
    var adminHowRecrop = document.querySelector("[data-admin-how-recrop]");
    var adminHowImageActions = document.querySelector("[data-admin-how-image-actions]");
    var adminHowMainActions = document.querySelector("[data-admin-how-main-actions]");
    var adminHowCropWorkspace = document.querySelector("[data-admin-how-crop-workspace]");
    var adminHowCropCancel = document.querySelector("[data-admin-how-crop-cancel]");
    var adminHowCropSave = document.querySelector("[data-admin-how-crop-save]");
    var adminHowFileInput = document.querySelector("[data-admin-how-file]");
    var adminHowPreviewWrap = document.querySelector("[data-admin-how-preview-wrap]");
    var adminHowPreviewImage = document.querySelector("[data-admin-how-preview-img]");
    var adminHowZoom = document.querySelector("[data-admin-how-zoom]");
    var adminHowSlotNote = document.querySelector("[data-admin-how-slot-note]");
    var adminHowRestoreEndpoint = adminHowGrid ? (adminHowGrid.getAttribute("data-admin-how-restore-endpoint") || "") : "";
    var adminHowImageBase = adminHowGrid ? (adminHowGrid.getAttribute("data-admin-how-image-base") || "assets/how_it_works/") : "assets/how_it_works/";
    var activeAdminEditCard = null;
    var activeAdminEventPackageCard = null;
    var activeAdminEventThumbsCard = null;
    var activeAdminEventThumbFolder = "";
    var adminEventThumbSelection = [];
    var activeAdminHowSlot = "";
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
    var adminCoverAspect = {
        width: 5,
        height: 4
    };
    var adminHowCropState = {
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
    var adminHowAspect = {
        width: 3,
        height: 2
    };
    var activeAdminPromoSlot = "";
    var adminPromoCropState = {
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
    var adminPromoAspect = {
        width: 3,
        height: 1
    };
    var adminUndoState = {
        timerId: null,
        pending: null
    };
    var adminActionModalState = {
        onConfirm: null,
        onClose: null,
        quantityRequired: false
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
    var manageBrandsOptionValue = "__manage_brands__";
    var brandValueToLabel = {};

    function formatBrandLabelFromValue(value) {
        var normalized = String(value || "")
            .replace(/[_-]+/g, " ")
            .replace(/\s+/g, " ")
            .trim();

        if (!normalized) {
            return "";
        }

        return normalized.replace(/\b\w/g, function (character) {
            return character.toUpperCase();
        });
    }

    function registerBrandValueLabel(value, label) {
        var normalizedValue = String(value || "").toLowerCase().trim();
        if (!normalizedValue || normalizedValue === "all" || normalizedValue === manageBrandsOptionValue) {
            return;
        }

        var normalizedLabel = String(label || "").replace(/\s+/g, " ").trim();

        if (!normalizedLabel || normalizedLabel === normalizedLabel.toUpperCase()) {
            normalizedLabel = formatBrandLabelFromValue(normalizedValue);
        }

        if (normalizedLabel && !Object.prototype.hasOwnProperty.call(brandValueToLabel, normalizedValue)) {
            brandValueToLabel[normalizedValue] = normalizedLabel;
        }
    }

    if (adminEditBrand && adminEditBrand.options) {
        Array.prototype.forEach.call(adminEditBrand.options, function (option) {
            if (!option) {
                return;
            }

            registerBrandValueLabel(option.value, option.textContent);
        });
    }

    Array.prototype.forEach.call(document.querySelectorAll('[data-filter-group="brand"][data-filter-value]'), function (button) {
        if (!button) {
            return;
        }

        registerBrandValueLabel(button.getAttribute("data-filter-value"), button.textContent);
    });

    if (!Object.keys(brandValueToLabel).length) {
        registerBrandValueLabel("canon", "Canon");
        registerBrandValueLabel("fuji", "Fuji");
        registerBrandValueLabel("nikon", "Nikon");
        registerBrandValueLabel("sony", "Sony");
    }

    var defaultBrandValue = Object.keys(brandValueToLabel)[0] || "canon";
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

    function initializeCustomerMessageModal() {
        if (!messageUsOpenButtons.length || !customerMessageModal) {
            return;
        }

        var isSubmittingMessage = false;

        function setMessageFeedback(message, type) {
            if (!customerMessageFeedback) {
                return;
            }

            var normalizedMessage = String(message || "").trim();
            var normalizedType = type === "success" ? "is-success" : "is-error";

            customerMessageFeedback.hidden = normalizedMessage === "";
            customerMessageFeedback.textContent = normalizedMessage;
            customerMessageFeedback.classList.remove("is-success", "is-error");

            if (normalizedMessage !== "") {
                customerMessageFeedback.classList.add(normalizedType);
            }
        }

        function closeCustomerMessageModal() {
            customerMessageModal.hidden = true;
            document.body.classList.remove("admin-modal-open");

            if (!isSubmittingMessage) {
                setMessageFeedback("", "error");
            }
        }

        function openCustomerMessageModal() {
            customerMessageModal.hidden = false;
            document.body.classList.add("admin-modal-open");
            setMessageFeedback("", "error");

            var firstInput = customerMessageModal.querySelector("#customer-message-subject");
            if (firstInput) {
                window.setTimeout(function () {
                    firstInput.focus();
                }, 60);
            }
        }

        messageUsOpenButtons.forEach(function (button) {
            button.addEventListener("click", function (event) {
                event.preventDefault();
                openCustomerMessageModal();
            });
        });

        customerMessageModalCloseButtons.forEach(function (button) {
            button.addEventListener("click", function () {
                if (isSubmittingMessage) {
                    return;
                }

                closeCustomerMessageModal();
            });
        });

        document.addEventListener("keydown", function (event) {
            if (event.key !== "Escape" || customerMessageModal.hidden || isSubmittingMessage) {
                return;
            }

            closeCustomerMessageModal();
        });

        if (customerMessageAttachmentInput) {
            customerMessageAttachmentInput.addEventListener("change", function () {
                var fileCount = customerMessageAttachmentInput.files ? customerMessageAttachmentInput.files.length : 0;

                if (fileCount <= 5) {
                    return;
                }

                customerMessageAttachmentInput.value = "";
                setMessageFeedback("You can upload up to 5 images only.", "error");
            });
        }

        if (!customerMessageForm) {
            return;
        }

        customerMessageForm.addEventListener("submit", function (event) {
            event.preventDefault();

            if (isSubmittingMessage) {
                return;
            }

            var endpoint = customerMessageForm.getAttribute("action") || "";
            if (!endpoint) {
                setMessageFeedback("Message endpoint is unavailable.", "error");
                return;
            }

            var subjectInput = customerMessageForm.querySelector("#customer-message-subject");
            var messageInput = customerMessageForm.querySelector("#customer-message-body");
            var subjectValue = subjectInput ? String(subjectInput.value || "").trim() : "";
            var messageValue = messageInput ? String(messageInput.value || "").trim() : "";
            var attachmentCount = customerMessageAttachmentInput && customerMessageAttachmentInput.files
                ? customerMessageAttachmentInput.files.length
                : 0;

            if (!subjectValue || !messageValue) {
                setMessageFeedback("Subject and message are required.", "error");
                return;
            }

            if (attachmentCount > 5) {
                setMessageFeedback("You can upload up to 5 images only.", "error");
                return;
            }

            setMessageFeedback("", "error");
            isSubmittingMessage = true;

            var previousButtonLabel = customerMessageSubmitButton ? customerMessageSubmitButton.textContent : "Send Message";

            if (customerMessageSubmitButton) {
                customerMessageSubmitButton.disabled = true;
                customerMessageSubmitButton.textContent = "Sending...";
            }

            var payload = new FormData(customerMessageForm);

            fetch(endpoint, {
                method: "POST",
                body: payload,
                credentials: "same-origin"
            })
                .then(function (response) {
                    return response.json().catch(function () {
                        return {
                            ok: false,
                            message: "Unexpected server response."
                        };
                    }).then(function (body) {
                        return {
                            ok: response.ok,
                            body: body
                        };
                    });
                })
                .then(function (result) {
                    var responseMessage = result.body && result.body.message
                        ? String(result.body.message)
                        : "Unable to send your message right now.";

                    if (!result.ok || !result.body || !result.body.ok) {
                        throw new Error(responseMessage);
                    }

                    customerMessageForm.reset();
                    setMessageFeedback(responseMessage, "success");

                    window.setTimeout(function () {
                        isSubmittingMessage = false;

                        if (customerMessageSubmitButton) {
                            customerMessageSubmitButton.disabled = false;
                            customerMessageSubmitButton.textContent = previousButtonLabel;
                        }

                        closeCustomerMessageModal();
                    }, 700);
                })
                .catch(function (error) {
                    setMessageFeedback(error.message || "Unable to send your message right now.", "error");
                })
                .finally(function () {
                    if (isSubmittingMessage) {
                        isSubmittingMessage = false;

                        if (customerMessageSubmitButton) {
                            customerMessageSubmitButton.disabled = false;
                            customerMessageSubmitButton.textContent = previousButtonLabel;
                        }
                    }
                });
        });
    }

    function initializeAdminNotificationsPage() {
        if (!document.body.classList.contains("admin-notifications-page") || !adminNotificationModal || !adminNotificationItems.length) {
            return;
        }

        function getAdminNotificationBadgeCount() {
            var firstBadge = adminNotificationCountBadges.length ? adminNotificationCountBadges[0] : null;
            var parsedCount = firstBadge ? Number.parseInt(String(firstBadge.textContent || "0"), 10) : 0;

            if (!Number.isFinite(parsedCount) || parsedCount < 0) {
                return 0;
            }

            return parsedCount;
        }

        function setAdminNotificationBadgeCount(nextCount) {
            var normalizedCount = Number.parseInt(String(nextCount), 10);

            if (!Number.isFinite(normalizedCount) || normalizedCount < 0) {
                normalizedCount = 0;
            }

            adminNotificationCountBadges.forEach(function (badge) {
                badge.textContent = String(normalizedCount);
            });
        }

        function parseAdminNotificationAttachments(rawValue) {
            var parsed = [];

            try {
                var decoded = JSON.parse(String(rawValue || "[]"));

                if (Array.isArray(decoded)) {
                    parsed = decoded;
                }
            } catch (error) {
                parsed = [];
            }

            return parsed
                .map(function (entry) {
                    return String(entry || "").trim();
                })
                .filter(function (entry) {
                    return entry !== "";
                });
        }

        function addAdminNotificationCarriageReturns(textValue, maxLineLength) {
            var text = String(textValue || "");
            var limit = Number.parseInt(String(maxLineLength || 92), 10);

            if (!Number.isFinite(limit) || limit < 20) {
                limit = 92;
            }

            return text
                .replace(/\r\n/g, "\n")
                .replace(/\r/g, "\n")
                .split("\n")
                .map(function (line) {
                    if (line.length <= limit) {
                        return line;
                    }

                    var remaining = line;
                    var chunks = [];

                    while (remaining.length > limit) {
                        var segment = remaining.slice(0, limit);
                        var breakIndex = segment.lastIndexOf(" ");

                        if (breakIndex > Math.floor(limit * 0.5)) {
                            chunks.push(remaining.slice(0, breakIndex));
                            remaining = remaining.slice(breakIndex + 1);
                        } else {
                            chunks.push(segment);
                            remaining = remaining.slice(limit);
                        }
                    }

                    if (remaining !== "") {
                        chunks.push(remaining);
                    }

                    return chunks.join("\r\n");
                })
                .join("\r\n");
        }

        function setAdminNotificationReadState(item, isRead) {
            if (!item) {
                return;
            }

            var shouldRead = Boolean(isRead);

            item.setAttribute("data-notification-is-read", shouldRead ? "1" : "0");
            item.classList.toggle("is-read", shouldRead);
            item.classList.toggle("is-unread", !shouldRead);

            var unreadDot = item.querySelector(".admin-notification-unread-dot");
            if (unreadDot) {
                unreadDot.hidden = shouldRead;
            }
        }

        function clearAdminNotificationModalAttachments() {
            if (!adminNotificationModalAttachments) {
                return;
            }

            adminNotificationModalAttachments.innerHTML = "";
            adminNotificationModalAttachments.hidden = true;
        }

        function openAdminNotificationModal(item) {
            if (!item || !adminNotificationModal) {
                return;
            }

            var typeValue = String(item.getAttribute("data-notification-type") || "notification").trim();
            var titleValue = String(item.getAttribute("data-notification-title") || "Notification").trim();
            var summaryValue = String(item.getAttribute("data-notification-summary") || "").trim();
            var senderValue = String(item.getAttribute("data-notification-sender") || "System").trim();
            var emailValue = String(item.getAttribute("data-notification-email") || "").trim();
            var messageValue = String(item.getAttribute("data-notification-message") || "").trim();
            var timeValue = String(item.getAttribute("data-notification-created-at") || "Unknown time").trim();
            var attachments = parseAdminNotificationAttachments(item.getAttribute("data-notification-attachments") || "[]");
            var wrappedSummaryValue = addAdminNotificationCarriageReturns(summaryValue, 92);
            var wrappedMessageValue = addAdminNotificationCarriageReturns(messageValue, 92);

            if (adminNotificationModalTitle) {
                adminNotificationModalTitle.textContent = titleValue || "Notification";
            }

            if (adminNotificationModalType) {
                adminNotificationModalType.textContent = (typeValue || "notification").toUpperCase();
            }

            if (adminNotificationModalSender) {
                adminNotificationModalSender.textContent = senderValue || "System";
            }

            if (adminNotificationModalEmail) {
                adminNotificationModalEmail.textContent = emailValue;
                adminNotificationModalEmail.hidden = emailValue === "";
            }

            if (adminNotificationModalTime) {
                adminNotificationModalTime.textContent = timeValue || "Unknown time";
            }

            if (adminNotificationModalSummary) {
                if (typeValue !== "message" && summaryValue !== "") {
                    adminNotificationModalSummary.textContent = wrappedSummaryValue;
                    adminNotificationModalSummary.hidden = false;
                } else {
                    adminNotificationModalSummary.hidden = true;
                }
            }

            if (adminNotificationModalMessage) {
                if (messageValue !== "") {
                    adminNotificationModalMessage.textContent = wrappedMessageValue;
                    adminNotificationModalMessage.hidden = false;
                } else {
                    adminNotificationModalMessage.hidden = true;
                }
            }

            clearAdminNotificationModalAttachments();

            if (adminNotificationModalAttachments && attachments.length) {
                attachments.forEach(function (attachmentPath) {
                    var anchor = document.createElement("a");
                    anchor.className = "admin-notification-modal-attachment";
                    anchor.href = attachmentPath;
                    anchor.target = "_blank";
                    anchor.rel = "noopener noreferrer";

                    var image = document.createElement("img");
                    image.src = attachmentPath;
                    image.alt = "Notification attachment";

                    anchor.appendChild(image);
                    adminNotificationModalAttachments.appendChild(anchor);
                });

                adminNotificationModalAttachments.hidden = false;
            }

            if (adminNotificationModalEmpty) {
                var hasDetails = summaryValue !== "" || messageValue !== "" || attachments.length > 0;
                adminNotificationModalEmpty.hidden = hasDetails;
            }

            adminNotificationModal.hidden = false;
            document.body.classList.add("admin-modal-open");

            var isRead = String(item.getAttribute("data-notification-is-read") || "0") === "1";

            if (!isRead) {
                var notificationId = String(item.getAttribute("data-notification-id") || "").trim();

                if (!notificationId) {
                    return;
                }

                if (!adminNotificationMarkReadEndpoint) {
                    setAdminNotificationReadState(item, true);
                    setAdminNotificationBadgeCount(Math.max(0, getAdminNotificationBadgeCount() - 1));
                    return;
                }

                fetch(adminNotificationMarkReadEndpoint, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        notificationId: notificationId
                    })
                })
                    .then(function (response) {
                        return response.json().catch(function () {
                            return {
                                ok: false
                            };
                        }).then(function (payload) {
                            return {
                                ok: response.ok,
                                payload: payload
                            };
                        });
                    })
                    .then(function (result) {
                        if (!result.ok || !result.payload || !result.payload.ok) {
                            return;
                        }

                        setAdminNotificationReadState(item, true);

                        if (typeof result.payload.unreadCount !== "undefined") {
                            setAdminNotificationBadgeCount(result.payload.unreadCount);
                        } else {
                            setAdminNotificationBadgeCount(Math.max(0, getAdminNotificationBadgeCount() - 1));
                        }
                    })
                    .catch(function () {
                        // Keep UI usable even if mark-read network call fails.
                    });
            }
        }

        function closeAdminNotificationModal() {
            if (!adminNotificationModal) {
                return;
            }

            adminNotificationModal.hidden = true;
            document.body.classList.remove("admin-modal-open");
        }

        adminNotificationItems.forEach(function (item) {
            item.addEventListener("click", function () {
                openAdminNotificationModal(item);
            });
        });

        adminNotificationModalCloseButtons.forEach(function (button) {
            button.addEventListener("click", function () {
                closeAdminNotificationModal();
            });
        });

        document.addEventListener("keydown", function (event) {
            if (event.key !== "Escape" || adminNotificationModal.hidden) {
                return;
            }

            closeAdminNotificationModal();
        });
    }

    function hideAdminUndoToast() {
        if (!adminUndoToast) {
            return;
        }

        adminUndoToast.classList.remove("is-visible");
        adminUndoToast.hidden = true;

        if (adminUndoAction) {
            adminUndoAction.disabled = false;
            adminUndoAction.textContent = "Undo";
        }

        if (adminUndoState.timerId) {
            window.clearTimeout(adminUndoState.timerId);
            adminUndoState.timerId = null;
        }

        adminUndoState.pending = null;
    }

    function formatArchiveDateLabel(isoValue) {
        var date = new Date(String(isoValue || ""));

        if (Number.isNaN(date.getTime())) {
            return String(isoValue || "");
        }

        return date.toLocaleString();
    }

    function showAdminUndoToast(message, pendingState) {
        if (!adminUndoToast || !adminUndoMessage || !adminUndoAction || !pendingState) {
            return;
        }

        if (adminUndoState.timerId) {
            window.clearTimeout(adminUndoState.timerId);
            adminUndoState.timerId = null;
        }

        adminUndoState.pending = pendingState;
        adminUndoMessage.textContent = message || "Archived";

        adminUndoToast.hidden = false;
        window.requestAnimationFrame(function () {
            adminUndoToast.classList.add("is-visible");
        });

        adminUndoState.timerId = window.setTimeout(function () {
            hideAdminUndoToast();
        }, 8000);
    }

    if (adminUndoAction) {
        adminUndoAction.addEventListener("click", function () {
            var pending = adminUndoState.pending;
            var endpoint = "";
            var fallbackError = "Unable to restore item.";

            if (!pending) {
                hideAdminUndoToast();
                return;
            }

            if (pending.type === "how") {
                endpoint = adminHowRestoreEndpoint;
                fallbackError = "Unable to restore How It Works image.";
            } else if (pending.type === "promo") {
                endpoint = adminPromoRestoreEndpoint;
                fallbackError = "Unable to restore promo banner.";
            } else if (pending.type === "event-package") {
                endpoint = adminEventRestoreEndpoint;
                fallbackError = "Unable to restore event package.";
            } else {
                endpoint = adminRestoreEndpoint;
                fallbackError = "Unable to restore product.";
            }

            if (!endpoint) {
                hideAdminUndoToast();
                return;
            }

            adminUndoAction.disabled = true;
            adminUndoAction.textContent = "Restoring...";

            fetch(endpoint, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(
                    pending.type === "event-package"
                        ? { packageKey: pending.packageKey || pending.archiveKey || "" }
                        : { archiveKey: pending.archiveKey }
                )
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
                        var message = result.payload && result.payload.message ? result.payload.message : fallbackError;
                        throw new Error(message);
                    }

                    if (pending.type === "how" || pending.type === "promo") {
                        if (pending.onRestore && typeof pending.onRestore === "function") {
                            pending.onRestore(result.payload);
                        }
                    } else if (pending.type === "event-package" && pending.card) {
                        pending.card.hidden = false;
                        pending.card.classList.remove("is-hidden");
                        pending.card.classList.remove("is-admin-removing");
                    } else if (pending.card) {
                        pending.card.removeAttribute("data-admin-removed");
                        pending.card.classList.remove("is-hidden");
                        pending.card.classList.remove("is-admin-removing");

                        if (result.payload.restoredKey) {
                            pending.card.setAttribute("data-product-key", result.payload.restoredKey);
                        }
                    }

                    if (pending.button) {
                        pending.button.disabled = false;
                    }

                    if (pending.type === "product") {
                        applyProductFilters();
                    }

                    hideAdminUndoToast();
                })
                .catch(function (error) {
                    window.alert(error.message || fallbackError);
                    adminUndoAction.disabled = false;
                    adminUndoAction.textContent = "Undo";
                });
        });
    }

    function archiveFeaturedProduct(card, button, archiveEndpoint) {
        if (!card || !button || !archiveEndpoint) {
            return;
        }

        var productKey = card.getAttribute("data-product-key") || "";
        if (!productKey) {
            return;
        }

        card.classList.add("is-admin-removing");
        button.disabled = true;

        fetch(archiveEndpoint, {
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
                    var message = result.payload && result.payload.message ? result.payload.message : "Unable to archive product.";
                    throw new Error(message);
                }

                var titleLink = card.querySelector(".product-title-link");
                var productName = titleLink ? titleLink.textContent.trim() : "Product";
                var archivedAt = result.payload.archivedEntry && result.payload.archivedEntry.archivedAt
                    ? String(result.payload.archivedEntry.archivedAt)
                    : "";
                var archiveKey = result.payload.archivedEntry && result.payload.archivedEntry.archiveKey
                    ? String(result.payload.archivedEntry.archiveKey)
                    : "";

                window.setTimeout(function () {
                    card.setAttribute("data-admin-removed", "true");
                    card.classList.remove("is-admin-removing");
                    card.classList.add("is-hidden");
                    applyProductFilters();

                    if (archiveKey) {
                        showAdminUndoToast(productName + " archived" + (archivedAt ? " (" + formatArchiveDateLabel(archivedAt) + ")" : ""), {
                            type: "product",
                            archiveKey: archiveKey,
                            card: card,
                            button: button
                        });
                    }
                }, 160);
            })
            .catch(function (error) {
                openAdminActionModal({
                    title: "Archive Failed",
                    message: error.message || "Unable to archive product.",
                    confirmLabel: "OK"
                });
                card.classList.remove("is-admin-removing");
                button.disabled = false;
            });
    }

    adminRemoveButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            var card = button.closest(".product-card");
            var archiveEndpoint = adminEditBackdrop ? (adminEditBackdrop.getAttribute("data-admin-archive-endpoint") || "") : "";

            if (!card || !archiveEndpoint) {
                return;
            }

            var titleLink = card.querySelector(".product-title-link");
            var productName = titleLink ? titleLink.textContent.trim() : "This featured product";

            openAdminActionModal({
                title: "Archive Featured Product",
                message: productName + " will be archived together with all of its equipment inventory units.",
                confirmLabel: "Archive Product",
                onConfirm: function () {
                    archiveFeaturedProduct(card, button, archiveEndpoint);
                }
            });
        });
    });

    function archiveEventPackage(card, button) {
        if (!card || !button || !adminEventArchiveEndpoint) {
            return;
        }

        var packageKey = String(card.getAttribute("data-admin-event-package-key") || "").trim();
        if (!packageKey) {
            return;
        }

        card.classList.add("is-admin-removing");
        button.disabled = true;

        fetch(adminEventArchiveEndpoint, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                packageKey: packageKey
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
                    var message = result.payload && result.payload.message ? result.payload.message : "Unable to archive event package.";
                    throw new Error(message);
                }

                var packageTitle = String(card.getAttribute("data-admin-event-package-title") || "Event package").trim();
                var archivedAt = result.payload.archivedEntry && result.payload.archivedEntry.archivedAt
                    ? String(result.payload.archivedEntry.archivedAt)
                    : "";

                window.setTimeout(function () {
                    card.classList.remove("is-admin-removing");
                    card.classList.add("is-hidden");
                    card.hidden = true;

                    showAdminUndoToast(packageTitle + " archived" + (archivedAt ? " (" + formatArchiveDateLabel(archivedAt) + ")" : ""), {
                        type: "event-package",
                        packageKey: packageKey,
                        archiveKey: packageKey,
                        card: card,
                        button: button
                    });
                }, 160);
            })
            .catch(function (error) {
                openAdminActionModal({
                    title: "Archive Failed",
                    message: error.message || "Unable to archive event package.",
                    confirmLabel: "OK"
                });
                card.classList.remove("is-admin-removing");
                button.disabled = false;
            });
    }

    adminEventRemoveButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            var card = button.closest("[data-admin-event-package]");

            if (!card || !adminEventArchiveEndpoint) {
                return;
            }

            archiveEventPackage(card, button);
        });
    });

    if (adminAddCard && adminEditBackdrop) {
        adminAddCard.addEventListener("click", function () {
            var createEndpoint = adminEditBackdrop.getAttribute("data-admin-create-endpoint") || "";
            var productBaseUrl = adminEditBackdrop.getAttribute("data-admin-product-base-url") || "";

            if (!createEndpoint || !productBaseUrl) {
                return;
            }

            if (adminAddCard.classList.contains("is-admin-creating")) {
                return;
            }

            adminAddCard.classList.add("is-admin-creating");

            fetch(createEndpoint, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({})
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
                    if (!result.ok || !result.payload || !result.payload.ok || !result.payload.newKey) {
                        var message = result.payload && result.payload.message ? result.payload.message : "Unable to create product.";
                        throw new Error(message);
                    }

                    window.location.href = productBaseUrl + encodeURIComponent(String(result.payload.newKey));
                })
                .catch(function (error) {
                    adminAddCard.classList.remove("is-admin-creating");
                    window.alert(error.message || "Unable to create product.");
                });
        });
    }

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

        if (!normalized || normalized === manageBrandsOptionValue) {
            return defaultBrandValue;
        }

        if (Object.prototype.hasOwnProperty.call(brandValueToLabel, normalized)) {
            return normalized;
        }

        return normalized;
    }

    function getBrandLabel(value) {
        var normalized = normalizeBrandValue(value);

        if (Object.prototype.hasOwnProperty.call(brandValueToLabel, normalized)) {
            return brandValueToLabel[normalized];
        }

        var fallback = formatBrandLabelFromValue(normalized);
        if (fallback) {
            return fallback;
        }

        return brandValueToLabel[defaultBrandValue] || "Brand";
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

        var bounds = getCoverRenderBounds(adminEditPreviewWrap, adminEditPreviewImage, adminCropState.zoom);
        var coverScale = adminEditPreviewImage.naturalWidth > 0
            ? (bounds.renderedWidth / adminEditPreviewImage.naturalWidth)
            : 1;

        adminEditPreviewWrap.style.setProperty("--admin-crop-zoom", String(adminCropState.zoom));
        adminEditPreviewWrap.style.setProperty("--admin-crop-x", String(adminCropState.offsetX) + "px");
        adminEditPreviewWrap.style.setProperty("--admin-crop-y", String(adminCropState.offsetY) + "px");
        adminEditPreviewWrap.style.setProperty("--admin-crop-scale", String(coverScale));
        adminEditPreviewWrap.classList.toggle("is-crop-active", adminCropState.isCropping);
    }

    function setAdminCropWorkspaceVisible(isVisible) {
        adminCropState.isCropping = isVisible;

        if (adminCropWorkspace) {
            adminCropWorkspace.hidden = !isVisible;
        }

        if (adminEditImageActions) {
            adminEditImageActions.hidden = isVisible;

            Array.prototype.forEach.call(adminEditImageActions.querySelectorAll("button"), function (button) {
                button.disabled = isVisible;
            });
        }

        if (adminEditMainActions) {
            adminEditMainActions.hidden = isVisible;

            Array.prototype.forEach.call(adminEditMainActions.querySelectorAll("button"), function (button) {
                button.disabled = isVisible;
            });
        }

        syncAdminPreviewTransform();
    }

    function getCoverRenderBounds(previewWrap, previewImage, zoomValue) {
        if (!previewWrap || !previewImage || !previewImage.naturalWidth || !previewImage.naturalHeight) {
            return {
                rectWidth: 0,
                rectHeight: 0,
                renderedWidth: 0,
                renderedHeight: 0,
                maxShiftX: 0,
                maxShiftY: 0
            };
        }

        var rect = previewWrap.getBoundingClientRect();
        var zoom = Math.max(1, Number(zoomValue || 1));
        var baseScale = Math.max(rect.width / previewImage.naturalWidth, rect.height / previewImage.naturalHeight);
        var renderedWidth = previewImage.naturalWidth * baseScale * zoom;
        var renderedHeight = previewImage.naturalHeight * baseScale * zoom;

        return {
            rectWidth: rect.width,
            rectHeight: rect.height,
            renderedWidth: renderedWidth,
            renderedHeight: renderedHeight,
            maxShiftX: Math.max(0, (renderedWidth - rect.width) / 2),
            maxShiftY: Math.max(0, (renderedHeight - rect.height) / 2)
        };
    }

    function clampAdminCropOffsets(nextX, nextY) {
        if (!adminEditPreviewWrap || !adminEditPreviewImage) {
            return { x: nextX, y: nextY };
        }

        var bounds = getCoverRenderBounds(adminEditPreviewWrap, adminEditPreviewImage, adminCropState.zoom);
        var clampedX = Math.min(bounds.maxShiftX, Math.max(-bounds.maxShiftX, nextX));
        var clampedY = Math.min(bounds.maxShiftY, Math.max(-bounds.maxShiftY, nextY));

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

        var outputWidth = 900;
        var outputHeight = Math.round(outputWidth * (adminCoverAspect.height / adminCoverAspect.width));
        var canvas = document.createElement("canvas");
        canvas.width = outputWidth;
        canvas.height = outputHeight;

        var ctx = canvas.getContext("2d");
        if (!ctx) {
            return null;
        }

        var zoomValue = Math.max(1, Number(adminCropState.zoom || 1));
        var bounds = getCoverRenderBounds(adminEditPreviewWrap, adminEditPreviewImage, zoomValue);
        var offsetScaleX = bounds.rectWidth > 0 ? (outputWidth / bounds.rectWidth) : 1;
        var offsetScaleY = bounds.rectHeight > 0 ? (outputHeight / bounds.rectHeight) : 1;
        var scaleToCover = Math.max(outputWidth / adminEditPreviewImage.naturalWidth, outputHeight / adminEditPreviewImage.naturalHeight);
        var scale = scaleToCover * zoomValue;
        var drawWidth = adminEditPreviewImage.naturalWidth * scale;
        var drawHeight = adminEditPreviewImage.naturalHeight * scale;
        var drawX = ((outputWidth - drawWidth) / 2) + (adminCropState.offsetX * offsetScaleX);
        var drawY = ((outputHeight - drawHeight) / 2) + (adminCropState.offsetY * offsetScaleY);

        ctx.clearRect(0, 0, outputWidth, outputHeight);
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

    function closeAdminEventEditModal() {
        if (!adminEventEditBackdrop) {
            return;
        }

        adminEventEditBackdrop.hidden = true;
        activeAdminEventPackageCard = null;
        syncAdminModalBodyLock();
    }

    function openAdminEventEditModal(card) {
        if (!adminEventEditBackdrop || !adminEventEditForm || !card) {
            return;
        }

        activeAdminEventPackageCard = card;

        var packageKey = String(card.getAttribute("data-admin-event-package-key") || "").trim();
        var packageTitle = String(card.getAttribute("data-admin-event-package-title") || "").trim();
        var packagePrice = Number.parseFloat(String(card.getAttribute("data-admin-event-package-price") || "0"));
        var packageDiscount = clampDiscount(card.getAttribute("data-admin-event-package-discount") || "0");

        if (adminEventEditKey) {
            adminEventEditKey.value = packageKey;
        }

        if (adminEventEditName) {
            adminEventEditName.value = packageTitle;
        }

        if (adminEventEditPrice) {
            adminEventEditPrice.value = Number.isFinite(packagePrice) ? packagePrice.toFixed(2) : "0.00";
        }

        if (adminEventEditDiscount) {
            adminEventEditDiscount.value = String(packageDiscount);
        }

        adminEventEditBackdrop.hidden = false;
        syncAdminModalBodyLock();
    }

    adminEventEditButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            var card = button.closest("[data-admin-event-package]");
            openAdminEventEditModal(card);
        });
    });

    if (adminEventEditCancel) {
        adminEventEditCancel.addEventListener("click", closeAdminEventEditModal);
    }

    if (adminEventEditClose) {
        adminEventEditClose.addEventListener("click", closeAdminEventEditModal);
    }

    if (adminEventEditBackdrop) {
        adminEventEditBackdrop.addEventListener("click", function (event) {
            if (event.target === adminEventEditBackdrop) {
                closeAdminEventEditModal();
            }
        });
    }

    function parseAdminEventThumbSelection(rawValue) {
        if (typeof rawValue !== "string" || rawValue.trim() === "") {
            return [];
        }

        try {
            var parsed = JSON.parse(rawValue);

            if (!Array.isArray(parsed)) {
                return [];
            }

            var unique = [];
            var seen = Object.create(null);

            parsed.forEach(function (entry) {
                var path = String(entry || "").trim();

                if (!path || seen[path]) {
                    return;
                }

                seen[path] = true;
                unique.push(path);
            });

            return unique;
        } catch (error) {
            return [];
        }
    }

    function getAdminEventImageFolder(imagePath) {
        var normalizedPath = String(imagePath || "").replace(/\\/g, "/").replace(/^\/+/, "");
        var prefix = "assets/event_packages/";

        if (normalizedPath.indexOf(prefix) !== 0) {
            return "";
        }

        var remainder = normalizedPath.slice(prefix.length);
        var slashIndex = remainder.indexOf("/");

        return (slashIndex >= 0 ? remainder.slice(0, slashIndex) : remainder).trim();
    }

    function syncAdminEventThumbSelectionUI() {
        if (!adminEventThumbItems.length) {
            if (adminEventThumbsInput) {
                adminEventThumbsInput.value = JSON.stringify(adminEventThumbSelection);
            }

            return;
        }

        var visibleCount = 0;

        adminEventThumbItems.forEach(function (item) {
            var path = String(item.getAttribute("data-image-path") || "");
            var itemFolder = String(item.getAttribute("data-image-folder") || "").trim();
            var isVisible = activeAdminEventThumbFolder === "" || itemFolder === activeAdminEventThumbFolder;
            var orderBadge = item.querySelector("[data-admin-event-thumb-order]");
            var selectedIndex = adminEventThumbSelection.indexOf(path);
            var isSelected = selectedIndex >= 0;

            item.hidden = !isVisible;

            if (!isVisible) {
                item.classList.remove("is-selected");
                if (orderBadge) {
                    orderBadge.hidden = true;
                    orderBadge.textContent = "";
                }

                return;
            }

            visibleCount += 1;

            item.classList.toggle("is-selected", isSelected);

            if (orderBadge) {
                if (isSelected) {
                    orderBadge.hidden = false;
                    orderBadge.textContent = String(selectedIndex + 1);
                } else {
                    orderBadge.hidden = true;
                    orderBadge.textContent = "";
                }
            }
        });

        if (adminEventThumbsFolderEmpty) {
            adminEventThumbsFolderEmpty.hidden = visibleCount > 0;
        }

        if (adminEventThumbsInput) {
            adminEventThumbsInput.value = JSON.stringify(adminEventThumbSelection);
        }
    }

    function closeAdminEventThumbsModal() {
        if (!adminEventThumbsBackdrop) {
            return;
        }

        adminEventThumbsBackdrop.hidden = true;
        activeAdminEventThumbsCard = null;
        activeAdminEventThumbFolder = "";
        adminEventThumbSelection = [];
        syncAdminModalBodyLock();
    }

    function openAdminEventThumbsModal(card, folderOverride) {
        if (!adminEventThumbsBackdrop || !card) {
            return;
        }

        activeAdminEventThumbsCard = card;

        var packageKey = String(card.getAttribute("data-admin-event-package-key") || "").trim();
        var packageTitle = String(card.getAttribute("data-admin-event-package-title") || "").trim();
        var packageFolder = String(folderOverride || card.getAttribute("data-admin-event-package-folder") || "").trim();
        var selectedRaw = String(card.getAttribute("data-admin-event-selected-thumbnails") || "[]");

        activeAdminEventThumbFolder = packageFolder;
        adminEventThumbSelection = parseAdminEventThumbSelection(selectedRaw).filter(function (path) {
            return getAdminEventImageFolder(path) === activeAdminEventThumbFolder;
        });

        if (adminEventThumbsKey) {
            adminEventThumbsKey.value = packageKey;
        }

        if (adminEventThumbsPackageTitle) {
            adminEventThumbsPackageTitle.textContent = packageTitle || "this package";
        }

        syncAdminEventThumbSelectionUI();

        adminEventThumbsBackdrop.hidden = false;
        syncAdminModalBodyLock();
    }

    if (adminEventSetThumbButtonInEdit) {
        adminEventSetThumbButtonInEdit.addEventListener("click", function () {
            if (!activeAdminEventPackageCard) {
                return;
            }

            openAdminEventThumbsModal(activeAdminEventPackageCard);
        });
    }

    adminEventThumbItems.forEach(function (item) {
        item.addEventListener("click", function () {
            var path = String(item.getAttribute("data-image-path") || "").trim();

            if (!path) {
                return;
            }

            var selectedIndex = adminEventThumbSelection.indexOf(path);

            if (selectedIndex >= 0) {
                adminEventThumbSelection.splice(selectedIndex, 1);
            } else {
                adminEventThumbSelection.push(path);
            }

            syncAdminEventThumbSelectionUI();
        });
    });

    if (adminEventThumbsClose) {
        adminEventThumbsClose.addEventListener("click", closeAdminEventThumbsModal);
    }

    if (adminEventThumbsCancel) {
        adminEventThumbsCancel.addEventListener("click", closeAdminEventThumbsModal);
    }

    if (adminEventThumbsBackdrop) {
        adminEventThumbsBackdrop.addEventListener("click", function (event) {
            if (event.target === adminEventThumbsBackdrop) {
                closeAdminEventThumbsModal();
            }
        });
    }

    if (adminEventThumbsForm) {
        adminEventThumbsForm.addEventListener("submit", function () {
            syncAdminEventThumbSelectionUI();
        });
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

            if (adminEditBrand.value !== currentBrandValue) {
                adminEditBrand.value = defaultBrandValue;
                currentBrandValue = normalizeBrandValue(adminEditBrand.value);
            }

            adminEditBrand.setAttribute("data-admin-brand-last-value", currentBrandValue);
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

    if (adminEditBrand) {
        adminEditBrand.addEventListener("focus", function () {
            var currentValue = String(adminEditBrand.value || "").toLowerCase().trim();

            if (currentValue && currentValue !== manageBrandsOptionValue) {
                adminEditBrand.setAttribute("data-admin-brand-last-value", currentValue);
            }
        });

        adminEditBrand.addEventListener("change", function () {
            var selectedValue = String(adminEditBrand.value || "").toLowerCase().trim();

            if (selectedValue && selectedValue !== manageBrandsOptionValue) {
                adminEditBrand.setAttribute("data-admin-brand-last-value", selectedValue);
                return;
            }

            if (selectedValue !== manageBrandsOptionValue) {
                return;
            }

            var targetUrl = String(adminEditBrand.getAttribute("data-admin-manage-brands-url") || "").trim();
            var fallbackValue = String(adminEditBrand.getAttribute("data-admin-brand-last-value") || defaultBrandValue).toLowerCase().trim();

            if (fallbackValue && fallbackValue !== manageBrandsOptionValue) {
                adminEditBrand.value = fallbackValue;
            }

            if (targetUrl) {
                window.location.href = targetUrl;
            }
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

            if (adminCropState.isCropping) {
                return;
            }

            if (!activeAdminEditCard || !adminEditBackdrop) {
                closeAdminEditModal();
                return;
            }

            var productKey = activeAdminEditCard.getAttribute("data-product-key") || "";
            var updateEndpoint = adminEditBackdrop.getAttribute("data-admin-update-endpoint") || "";
            var selectedBrandValue = adminEditBrand ? String(adminEditBrand.value || "").toLowerCase().trim() : "";

            if (selectedBrandValue === manageBrandsOptionValue) {
                var manageBrandsUrl = adminEditBrand
                    ? String(adminEditBrand.getAttribute("data-admin-manage-brands-url") || "").trim()
                    : "";

                if (manageBrandsUrl) {
                    window.location.href = manageBrandsUrl;
                }

                return;
            }

            var brandValue = normalizeBrandValue(selectedBrandValue || defaultBrandValue);
            var nameValue = adminEditName ? adminEditName.value.trim() : "";
            var specOneValue = adminEditSpec1 ? adminEditSpec1.value.trim() : "";
            var specTwoValue = adminEditSpec2 ? adminEditSpec2.value.trim() : "";
            var priceValue = adminEditPrice ? Number.parseFloat(adminEditPrice.value) : 0;
            var discountValue = clampDiscount(adminEditDiscount ? adminEditDiscount.value : 0);
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

            var payload = {
                productKey: productKey,
                brand: brandValue,
                name: nameValue,
                spec1: specOneValue,
                spec2: specTwoValue,
                price: priceValue,
                discountPercent: discountValue,
                imageDataUrl: finalPreviewSrc
            };

            fetch(updateEndpoint, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(payload)
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

    function setAdminHowPreviewSource(source) {
        if (!adminHowPreviewImage) {
            return;
        }

        var nextSource = String(source || "").trim();

        if (!nextSource) {
            adminHowPreviewImage.hidden = true;
            adminHowPreviewImage.removeAttribute("src");
            return;
        }

        adminHowPreviewImage.hidden = false;
        adminHowPreviewImage.src = nextSource;
    }

    function syncAdminHowPreviewTransform() {
        if (!adminHowPreviewWrap || !adminHowPreviewImage || !adminHowZoom) {
            return;
        }

        var bounds = getCoverRenderBounds(adminHowPreviewWrap, adminHowPreviewImage, adminHowCropState.zoom);
        var coverScale = adminHowPreviewImage.naturalWidth > 0
            ? (bounds.renderedWidth / adminHowPreviewImage.naturalWidth)
            : 1;

        adminHowPreviewWrap.style.setProperty("--admin-crop-zoom", String(adminHowCropState.zoom));
        adminHowPreviewWrap.style.setProperty("--admin-crop-x", String(adminHowCropState.offsetX) + "px");
        adminHowPreviewWrap.style.setProperty("--admin-crop-y", String(adminHowCropState.offsetY) + "px");
        adminHowPreviewWrap.style.setProperty("--admin-crop-scale", String(coverScale));
        adminHowPreviewWrap.classList.toggle("is-crop-active", adminHowCropState.isCropping);
    }

    function setAdminHowCropWorkspaceVisible(isVisible) {
        adminHowCropState.isCropping = isVisible;

        if (adminHowCropWorkspace) {
            adminHowCropWorkspace.hidden = !isVisible;
        }

        if (adminHowImageActions) {
            adminHowImageActions.hidden = isVisible;

            Array.prototype.forEach.call(adminHowImageActions.querySelectorAll("button"), function (button) {
                button.disabled = isVisible;
            });
        }

        if (adminHowMainActions) {
            adminHowMainActions.hidden = isVisible;

            Array.prototype.forEach.call(adminHowMainActions.querySelectorAll("button"), function (button) {
                button.disabled = isVisible;
            });
        }

        syncAdminHowPreviewTransform();
    }

    function clampAdminHowCropOffsets(nextX, nextY) {
        if (!adminHowPreviewWrap || !adminHowPreviewImage) {
            return { x: nextX, y: nextY };
        }

        var bounds = getCoverRenderBounds(adminHowPreviewWrap, adminHowPreviewImage, adminHowCropState.zoom);
        var clampedX = Math.min(bounds.maxShiftX, Math.max(-bounds.maxShiftX, nextX));
        var clampedY = Math.min(bounds.maxShiftY, Math.max(-bounds.maxShiftY, nextY));

        return {
            x: clampedX,
            y: clampedY
        };
    }

    function resetAdminHowCropState() {
        adminHowCropState.zoom = 1;
        adminHowCropState.offsetX = 0;
        adminHowCropState.offsetY = 0;
        adminHowCropState.isDragging = false;
        adminHowCropState.dragPointerId = null;

        if (adminHowZoom) {
            adminHowZoom.value = "1";
        }

        syncAdminHowPreviewTransform();
    }

    function buildAdminHowCropDataUrlFromPreview() {
        if (!adminHowPreviewImage || adminHowPreviewImage.hidden || !adminHowPreviewImage.src || !adminHowPreviewImage.naturalWidth || !adminHowPreviewImage.naturalHeight) {
            return null;
        }

        var outputWidth = 900;
        var outputHeight = Math.round(outputWidth * (adminHowAspect.height / adminHowAspect.width));
        var canvas = document.createElement("canvas");
        canvas.width = outputWidth;
        canvas.height = outputHeight;

        var ctx = canvas.getContext("2d");
        if (!ctx) {
            return null;
        }

        var zoomValue = Math.max(1, Number(adminHowCropState.zoom || 1));
        var bounds = getCoverRenderBounds(adminHowPreviewWrap, adminHowPreviewImage, zoomValue);
        var offsetScaleX = bounds.rectWidth > 0 ? (outputWidth / bounds.rectWidth) : 1;
        var offsetScaleY = bounds.rectHeight > 0 ? (outputHeight / bounds.rectHeight) : 1;
        var scaleToCover = Math.max(outputWidth / adminHowPreviewImage.naturalWidth, outputHeight / adminHowPreviewImage.naturalHeight);
        var scale = scaleToCover * zoomValue;
        var drawWidth = adminHowPreviewImage.naturalWidth * scale;
        var drawHeight = adminHowPreviewImage.naturalHeight * scale;
        var drawX = ((outputWidth - drawWidth) / 2) + (adminHowCropState.offsetX * offsetScaleX);
        var drawY = ((outputHeight - drawHeight) / 2) + (adminHowCropState.offsetY * offsetScaleY);

        ctx.clearRect(0, 0, outputWidth, outputHeight);
        ctx.drawImage(adminHowPreviewImage, drawX, drawY, drawWidth, drawHeight);

        return canvas.toDataURL("image/png");
    }

    function updateAdminHowRecropState() {
        if (!adminHowRecrop || !adminHowPreviewImage) {
            return;
        }

        adminHowRecrop.disabled = adminHowPreviewImage.hidden || !adminHowPreviewImage.src;
    }

    function closeAdminHowEditModal() {
        if (!adminHowEditBackdrop) {
            return;
        }

        setAdminHowCropWorkspaceVisible(false);
        resetAdminHowCropState();
        adminHowCropState.previewBeforeCrop = "";
        adminHowCropState.sourceImage = "";

        if (adminHowFileInput) {
            adminHowFileInput.value = "";
        }

        setAdminHowPreviewSource("");
        updateAdminHowRecropState();
        activeAdminHowSlot = "";
        adminHowEditBackdrop.hidden = true;
        document.body.classList.remove("admin-modal-open");
    }

    function openAdminHowEditModal(slot, currentSource) {
        if (!adminHowEditBackdrop || !adminHowForm) {
            return;
        }

        activeAdminHowSlot = String(slot || "").trim();
        if (!activeAdminHowSlot) {
            return;
        }

        if (adminHowSlotNote) {
            adminHowSlotNote.textContent = "Slot " + activeAdminHowSlot + " image (3:2)";
        }

        setAdminHowPreviewSource(currentSource || "");
        updateAdminHowRecropState();

        if (adminHowFileInput) {
            adminHowFileInput.value = "";
        }

        adminHowCropState.previewBeforeCrop = adminHowPreviewImage && !adminHowPreviewImage.hidden ? adminHowPreviewImage.src : "";
        adminHowCropState.sourceImage = "";
        resetAdminHowCropState();
        setAdminHowCropWorkspaceVisible(false);

        adminHowEditBackdrop.hidden = false;
        document.body.classList.add("admin-modal-open");
    }

    function renderAdminHowCardAsEmpty(card, slotValue) {
        if (!card) {
            return;
        }

        var slot = String(slotValue || card.getAttribute("data-admin-how-slot") || "").trim();
        card.classList.add("step-card-admin-add");
        card.classList.remove("is-admin-busy");
        card.setAttribute("data-admin-how-has-image", "false");
        card.setAttribute("data-admin-how-image-src", "");
        card.innerHTML = '' +
            '<button class="step-card-admin-add-trigger" type="button" data-admin-how-edit aria-label="Add how it works image slot ' + slot + '">' +
                '<span class="step-card-admin-add-plus">+</span>' +
                '<span>Add Photo</span>' +
            '</button>';
    }

    function renderAdminHowCardAsImage(card, slotValue, imageSource) {
        if (!card) {
            return;
        }

        var slot = String(slotValue || card.getAttribute("data-admin-how-slot") || "").trim();
        var cacheBustedSource = String(imageSource || "");

        card.classList.remove("step-card-admin-add");
        card.classList.remove("is-admin-busy");
        card.setAttribute("data-admin-how-has-image", "true");
        card.setAttribute("data-admin-how-image-src", cacheBustedSource);
        card.innerHTML = '' +
            '<button class="step-card-admin-remove" type="button" data-admin-how-remove aria-label="Delete how it works image slot ' + slot + '">&times;</button>' +
            '<button class="step-card-admin-image-button" type="button" data-admin-how-edit aria-label="Edit how it works image slot ' + slot + '">' +
                '<img class="step-image" src="' + cacheBustedSource + '" alt="How it works step ' + slot + '">' +
            '</button>';
    }

    if (adminHowGrid) {
        var adminHowUpdateEndpoint = adminHowGrid.getAttribute("data-admin-how-update-endpoint") || "";
        var adminHowDeleteEndpoint = adminHowGrid.getAttribute("data-admin-how-delete-endpoint") || "";

        adminHowGrid.addEventListener("click", function (event) {
            var removeButton = event.target.closest("[data-admin-how-remove]");
            if (removeButton) {
                var removeCard = removeButton.closest("[data-admin-how-slot]");
                var removeSlot = removeCard ? (removeCard.getAttribute("data-admin-how-slot") || "") : "";

                if (!removeCard || !removeSlot || !adminHowDeleteEndpoint) {
                    return;
                }

                removeCard.classList.add("is-admin-busy");
                removeButton.disabled = true;

                fetch(adminHowDeleteEndpoint, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        slot: Number.parseInt(removeSlot, 10)
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
                            var message = result.payload && result.payload.message ? result.payload.message : "Unable to archive image.";
                            throw new Error(message);
                        }

                        var archivedEntry = result.payload.archivedEntry && typeof result.payload.archivedEntry === "object"
                            ? result.payload.archivedEntry
                            : null;
                        var archivedAt = archivedEntry && archivedEntry.archivedAt ? String(archivedEntry.archivedAt) : "";
                        var archiveKey = archivedEntry && archivedEntry.archiveKey ? String(archivedEntry.archiveKey) : "";

                        renderAdminHowCardAsEmpty(removeCard, removeSlot);

                        if (archiveKey) {
                            showAdminUndoToast(
                                "How It Works slot " + removeSlot + " archived" + (archivedAt ? " (" + formatArchiveDateLabel(archivedAt) + ")" : ""),
                                {
                                    type: "how",
                                    archiveKey: archiveKey,
                                    onRestore: function (payload) {
                                        var restoredSlot = payload && payload.slot ? String(payload.slot) : removeSlot;
                                        var restoredSource = adminHowImageBase + restoredSlot + ".png?t=" + String(Date.now());
                                        renderAdminHowCardAsImage(removeCard, restoredSlot, restoredSource);
                                    }
                                }
                            );
                        }
                    })
                    .catch(function (error) {
                        removeCard.classList.remove("is-admin-busy");
                        removeButton.disabled = false;
                        window.alert(error.message || "Unable to archive image.");
                    });

                return;
            }

            var editButton = event.target.closest("[data-admin-how-edit]");
            if (!editButton) {
                return;
            }

            var editCard = editButton.closest("[data-admin-how-slot]");
            var editSlot = editCard ? (editCard.getAttribute("data-admin-how-slot") || "") : "";
            var currentSource = editCard ? (editCard.getAttribute("data-admin-how-image-src") || "") : "";

            openAdminHowEditModal(editSlot, currentSource);
        });

        if (adminHowZoom) {
            adminHowZoom.addEventListener("input", function () {
                adminHowCropState.zoom = Number.parseFloat(adminHowZoom.value) || 1;
                var clamped = clampAdminHowCropOffsets(adminHowCropState.offsetX, adminHowCropState.offsetY);
                adminHowCropState.offsetX = clamped.x;
                adminHowCropState.offsetY = clamped.y;
                syncAdminHowPreviewTransform();
            });
        }

        if (adminHowBrowse && adminHowFileInput) {
            adminHowBrowse.addEventListener("click", function () {
                adminHowFileInput.click();
            });
        }

        if (adminHowRecrop && adminHowPreviewImage) {
            adminHowRecrop.addEventListener("click", function () {
                if (adminHowPreviewImage.hidden || !adminHowPreviewImage.src) {
                    return;
                }

                adminHowCropState.previewBeforeCrop = adminHowPreviewImage.src;
                adminHowCropState.sourceImage = adminHowPreviewImage.src;
                resetAdminHowCropState();
                setAdminHowCropWorkspaceVisible(true);
                syncAdminHowPreviewTransform();
            });
        }

        if (adminHowFileInput && adminHowPreviewImage) {
            adminHowPreviewImage.addEventListener("dragstart", function (event) {
                event.preventDefault();
            });

            adminHowFileInput.addEventListener("change", function () {
                var file = adminHowFileInput.files && adminHowFileInput.files[0] ? adminHowFileInput.files[0] : null;

                if (!file) {
                    return;
                }

                var reader = new FileReader();
                reader.onload = function (loadEvent) {
                    adminHowCropState.previewBeforeCrop = adminHowPreviewImage.hidden ? "" : (adminHowPreviewImage.src || "");
                    adminHowCropState.sourceImage = String(loadEvent.target && loadEvent.target.result ? loadEvent.target.result : "");
                    setAdminHowPreviewSource(adminHowCropState.sourceImage);
                    updateAdminHowRecropState();
                    resetAdminHowCropState();
                    setAdminHowCropWorkspaceVisible(true);
                    syncAdminHowPreviewTransform();
                };
                reader.readAsDataURL(file);
            });
        }

        if (adminHowPreviewWrap) {
            adminHowPreviewWrap.addEventListener("wheel", function (event) {
                if (!adminHowCropState.isCropping || !adminHowZoom) {
                    return;
                }

                event.preventDefault();

                var minZoom = Number.parseFloat(adminHowZoom.min || "1");
                var maxZoom = Number.parseFloat(adminHowZoom.max || "3");
                var stepZoom = Number.parseFloat(adminHowZoom.step || "0.01");
                var direction = event.deltaY < 0 ? 1 : -1;
                var nextZoom = adminHowCropState.zoom + (direction * (stepZoom * 5));

                nextZoom = Math.min(maxZoom, Math.max(minZoom, nextZoom));
                nextZoom = Math.round(nextZoom * 100) / 100;

                adminHowCropState.zoom = nextZoom;
                adminHowZoom.value = String(nextZoom);

                var clamped = clampAdminHowCropOffsets(adminHowCropState.offsetX, adminHowCropState.offsetY);
                adminHowCropState.offsetX = clamped.x;
                adminHowCropState.offsetY = clamped.y;
                syncAdminHowPreviewTransform();
            }, { passive: false });

            adminHowPreviewWrap.addEventListener("pointerdown", function (event) {
                if (!adminHowCropState.isCropping || event.button !== 0) {
                    return;
                }

                event.preventDefault();

                adminHowCropState.isDragging = true;
                adminHowCropState.dragPointerId = event.pointerId;
                adminHowCropState.dragStartClientX = event.clientX;
                adminHowCropState.dragStartClientY = event.clientY;
                adminHowCropState.dragStartOffsetX = adminHowCropState.offsetX;
                adminHowCropState.dragStartOffsetY = adminHowCropState.offsetY;
                adminHowPreviewWrap.setPointerCapture(event.pointerId);
            });

            adminHowPreviewWrap.addEventListener("pointermove", function (event) {
                if (!adminHowCropState.isCropping || !adminHowCropState.isDragging || adminHowCropState.dragPointerId !== event.pointerId) {
                    return;
                }

                var nextX = adminHowCropState.dragStartOffsetX + (event.clientX - adminHowCropState.dragStartClientX);
                var nextY = adminHowCropState.dragStartOffsetY + (event.clientY - adminHowCropState.dragStartClientY);
                var clamped = clampAdminHowCropOffsets(nextX, nextY);
                adminHowCropState.offsetX = clamped.x;
                adminHowCropState.offsetY = clamped.y;
                syncAdminHowPreviewTransform();
            });

            function stopAdminHowCropDrag(event) {
                if (!adminHowCropState.isDragging || adminHowCropState.dragPointerId !== event.pointerId) {
                    return;
                }

                adminHowCropState.isDragging = false;
                adminHowCropState.dragPointerId = null;
                adminHowPreviewWrap.releasePointerCapture(event.pointerId);
            }

            adminHowPreviewWrap.addEventListener("pointerup", stopAdminHowCropDrag);
            adminHowPreviewWrap.addEventListener("pointercancel", stopAdminHowCropDrag);
        }

        if (adminHowCropCancel && adminHowPreviewImage) {
            adminHowCropCancel.addEventListener("click", function () {
                setAdminHowPreviewSource(adminHowCropState.previewBeforeCrop || "");
                updateAdminHowRecropState();
                adminHowCropState.sourceImage = "";
                resetAdminHowCropState();
                setAdminHowCropWorkspaceVisible(false);
            });
        }

        if (adminHowCropSave && adminHowPreviewImage) {
            adminHowCropSave.addEventListener("click", function () {
                var croppedDataUrl = buildAdminHowCropDataUrlFromPreview();

                if (!croppedDataUrl) {
                    return;
                }

                setAdminHowPreviewSource(croppedDataUrl);
                adminHowCropState.previewBeforeCrop = croppedDataUrl;
                adminHowCropState.sourceImage = "";
                updateAdminHowRecropState();
                resetAdminHowCropState();
                setAdminHowCropWorkspaceVisible(false);
            });
        }

        if (adminHowClose) {
            adminHowClose.addEventListener("click", closeAdminHowEditModal);
        }

        if (adminHowCancel) {
            adminHowCancel.addEventListener("click", closeAdminHowEditModal);
        }

        if (adminHowEditBackdrop) {
            adminHowEditBackdrop.addEventListener("click", function (event) {
                if (event.target === adminHowEditBackdrop) {
                    closeAdminHowEditModal();
                }
            });
        }

        if (adminHowForm) {
            adminHowForm.addEventListener("submit", function (event) {
                event.preventDefault();

                if (!activeAdminHowSlot || !adminHowUpdateEndpoint) {
                    return;
                }

                var finalPreviewSrc = adminHowPreviewImage && !adminHowPreviewImage.hidden ? adminHowPreviewImage.src : "";

                if (adminHowCropState.isCropping) {
                    var autoCroppedDataUrl = buildAdminHowCropDataUrlFromPreview();

                    if (autoCroppedDataUrl) {
                        finalPreviewSrc = autoCroppedDataUrl;
                        setAdminHowPreviewSource(autoCroppedDataUrl);
                        adminHowCropState.previewBeforeCrop = autoCroppedDataUrl;
                    }

                    adminHowCropState.sourceImage = "";
                    resetAdminHowCropState();
                    setAdminHowCropWorkspaceVisible(false);
                }

                if (!finalPreviewSrc) {
                    window.alert("Please add an image first.");
                    return;
                }

                var submitButton = adminHowForm.querySelector('button[type="submit"]');
                var previousSubmitTitle = submitButton ? submitButton.getAttribute("title") : "";

                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.setAttribute("title", "Saving...");
                }

                fetch(adminHowUpdateEndpoint, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        slot: Number.parseInt(activeAdminHowSlot, 10),
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
                            var message = result.payload && result.payload.message ? result.payload.message : "Unable to save image.";
                            throw new Error(message);
                        }

                        window.location.reload();
                    })
                    .catch(function (error) {
                        if (submitButton) {
                            submitButton.disabled = false;
                            submitButton.setAttribute("title", previousSubmitTitle || "Save Changes");
                        }

                        window.alert(error.message || "Unable to save image.");
                    });
            });
        }

        updateAdminHowRecropState();
    }

    function getPromoSlides() {
        return promoBanner ? promoBanner.querySelectorAll(".promo-slide") : [];
    }

    function getPromoImageSlides() {
        return promoBanner ? promoBanner.querySelectorAll(".promo-slide[data-promo-slot]") : [];
    }

    function getPromoAddSlide() {
        return promoBanner ? promoBanner.querySelector("[data-admin-promo-add-slide]") : null;
    }

    function getActivePromoSlide() {
        if (!promoBanner) {
            return null;
        }

        var activeSlide = promoBanner.querySelector(".promo-slide.is-active");
        if (activeSlide) {
            return activeSlide;
        }

        var promoSlides = getPromoSlides();

        return promoSlides.length ? promoSlides[0] : null;
    }

    function syncAdminPromoRemoveState() {
        if (!adminPromoRemove) {
            return;
        }

        var activeSlide = getActivePromoSlide();
        var hasImageSlides = getPromoImageSlides().length > 0;
        var activeHasSlot = !!(activeSlide && String(activeSlide.getAttribute("data-promo-slot") || "").trim());

        adminPromoRemove.disabled = !hasImageSlides || !activeHasSlot;
    }

    function showPromoSlide(nextIndex) {
        var promoSlides = getPromoSlides();

        if (!promoSlides.length) {
            promoIndex = 0;
            syncAdminPromoRemoveState();
            return;
        }

        promoIndex = (nextIndex + promoSlides.length) % promoSlides.length;

        promoSlides.forEach(function (slide, slideIndex) {
            var isActive = slideIndex === promoIndex;

            slide.classList.toggle("is-active", isActive);
            slide.setAttribute("aria-hidden", isActive ? "false" : "true");
        });

        syncAdminPromoRemoveState();
    }

    function startPromoTimer() {
        var promoSlides = getPromoSlides();

        if (promoSlides.length < 2) {
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

    function getNextAvailablePromoSlot() {
        var slots = [];

        Array.prototype.forEach.call(getPromoImageSlides(), function (slide) {
            var value = Number.parseInt(slide.getAttribute("data-promo-slot") || "0", 10);

            if (Number.isFinite(value) && value >= 1) {
                slots.push(value);
            }
        });

        if (!slots.length) {
            return 1;
        }

        return Math.max.apply(null, slots) + 1;
    }

    function updatePromoAddSlideSlot() {
        var addSlide = getPromoAddSlide();

        if (!addSlide) {
            return;
        }

        var nextSlot = getNextAvailablePromoSlot();

        addSlide.setAttribute("data-admin-promo-slot", String(nextSlot));
    }

    function updatePromoControls() {
        var promoSlides = getPromoSlides();
        var imageSlides = getPromoImageSlides();
        var hasSlides = promoSlides.length > 0;
        var isSingleSlide = promoSlides.length <= 1;

        if (promoPrev) {
            promoPrev.hidden = isSingleSlide;
        }

        if (promoNext) {
            promoNext.hidden = isSingleSlide;
        }

        if (!promoCarousel) {
            syncAdminPromoRemoveState();
            return;
        }

        var emptyNode = promoCarousel.querySelector("[data-promo-empty]");

        if (!hasSlides) {
            stopPromoTimer();

            if (!adminPromoBanner && !emptyNode) {
                emptyNode = document.createElement("div");
                emptyNode.className = "promo-placeholder promo-placeholder-empty";
                emptyNode.setAttribute("data-promo-empty", "true");
                emptyNode.innerHTML = "<span>No promo banners available.</span>";
                promoCarousel.appendChild(emptyNode);
            }

            syncAdminPromoRemoveState();
            return;
        }

        if (emptyNode) {
            emptyNode.remove();
        }

        if (adminPromoBanner && !imageSlides.length) {
            var addSlide = getPromoAddSlide();
            var slidesArray = Array.prototype.slice.call(getPromoSlides());
            var addSlideIndex = addSlide ? slidesArray.indexOf(addSlide) : -1;

            if (addSlideIndex >= 0) {
                showPromoSlide(addSlideIndex);
            }
        } else {
            syncAdminPromoRemoveState();
        }
    }

    function buildPromoSlotFilename(slot) {
        var slotNumber = Number.parseInt(slot, 10);

        if (!Number.isFinite(slotNumber) || slotNumber < 1) {
            slotNumber = 1;
        }

        return String(slotNumber).padStart(4, "0") + ".png";
    }

    function buildPromoSlideClass(slot) {
        var slotNumber = Number.parseInt(slot, 10);

        if (slotNumber === 1) {
            return "promo-slide-one";
        }

        if (slotNumber === 2) {
            return "promo-slide-two";
        }

        return "promo-slide-three";
    }

    function insertPromoSlideSorted(slideElement) {
        if (!promoCarousel || !slideElement) {
            return;
        }

        var slotValue = Number.parseInt(slideElement.getAttribute("data-promo-slot") || "0", 10);
        var promoSlides = Array.prototype.slice.call(promoCarousel.querySelectorAll(".promo-slide"));
        var inserted = false;

        var addSlide = getPromoAddSlide();
        if (addSlide && addSlide !== slideElement) {
            promoCarousel.insertBefore(slideElement, addSlide);
            inserted = true;
        }

        promoSlides.forEach(function (existingSlide) {
            if (inserted) {
                return;
            }

            var existingSlot = Number.parseInt(existingSlide.getAttribute("data-promo-slot") || "0", 10);

            if (Number.isFinite(existingSlot) && existingSlot > slotValue) {
                promoCarousel.insertBefore(slideElement, existingSlide);
                inserted = true;
            }
        });

        if (!inserted) {
            promoCarousel.appendChild(slideElement);
        }
    }

    function setAdminPromoPreviewSource(source) {
        if (!adminPromoPreviewImage) {
            return;
        }

        var nextSource = String(source || "").trim();

        if (!nextSource) {
            adminPromoPreviewImage.hidden = true;
            adminPromoPreviewImage.removeAttribute("src");
            return;
        }

        adminPromoPreviewImage.hidden = false;
        adminPromoPreviewImage.src = nextSource;
    }

    function syncAdminPromoPreviewTransform() {
        if (!adminPromoPreviewWrap || !adminPromoPreviewImage || !adminPromoZoom) {
            return;
        }

        var bounds = getCoverRenderBounds(adminPromoPreviewWrap, adminPromoPreviewImage, adminPromoCropState.zoom);
        var coverScale = adminPromoPreviewImage.naturalWidth > 0
            ? (bounds.renderedWidth / adminPromoPreviewImage.naturalWidth)
            : 1;

        adminPromoPreviewWrap.style.setProperty("--admin-crop-zoom", String(adminPromoCropState.zoom));
        adminPromoPreviewWrap.style.setProperty("--admin-crop-x", String(adminPromoCropState.offsetX) + "px");
        adminPromoPreviewWrap.style.setProperty("--admin-crop-y", String(adminPromoCropState.offsetY) + "px");
        adminPromoPreviewWrap.style.setProperty("--admin-crop-scale", String(coverScale));
        adminPromoPreviewWrap.classList.toggle("is-crop-active", adminPromoCropState.isCropping);
    }

    function setAdminPromoCropWorkspaceVisible(isVisible) {
        adminPromoCropState.isCropping = isVisible;

        if (adminPromoCropWorkspace) {
            adminPromoCropWorkspace.hidden = !isVisible;
        }

        if (adminPromoImageActions) {
            adminPromoImageActions.hidden = isVisible;

            Array.prototype.forEach.call(adminPromoImageActions.querySelectorAll("button"), function (button) {
                button.disabled = isVisible;
            });
        }

        if (adminPromoMainActions) {
            adminPromoMainActions.hidden = isVisible;

            Array.prototype.forEach.call(adminPromoMainActions.querySelectorAll("button"), function (button) {
                button.disabled = isVisible;
            });
        }

        syncAdminPromoPreviewTransform();
    }

    function clampAdminPromoCropOffsets(nextX, nextY) {
        if (!adminPromoPreviewWrap || !adminPromoPreviewImage) {
            return { x: nextX, y: nextY };
        }

        var bounds = getCoverRenderBounds(adminPromoPreviewWrap, adminPromoPreviewImage, adminPromoCropState.zoom);
        var clampedX = Math.min(bounds.maxShiftX, Math.max(-bounds.maxShiftX, nextX));
        var clampedY = Math.min(bounds.maxShiftY, Math.max(-bounds.maxShiftY, nextY));

        return {
            x: clampedX,
            y: clampedY
        };
    }

    function resetAdminPromoCropState() {
        adminPromoCropState.zoom = 1;
        adminPromoCropState.offsetX = 0;
        adminPromoCropState.offsetY = 0;
        adminPromoCropState.isDragging = false;
        adminPromoCropState.dragPointerId = null;

        if (adminPromoZoom) {
            adminPromoZoom.value = "1";
        }

        syncAdminPromoPreviewTransform();
    }

    function updateAdminPromoRecropState() {
        if (!adminPromoRecrop || !adminPromoPreviewImage) {
            return;
        }

        adminPromoRecrop.disabled = adminPromoPreviewImage.hidden || !adminPromoPreviewImage.src;
    }

    function buildAdminPromoCropDataUrlFromPreview() {
        if (!adminPromoPreviewImage || adminPromoPreviewImage.hidden || !adminPromoPreviewImage.src || !adminPromoPreviewImage.naturalWidth || !adminPromoPreviewImage.naturalHeight) {
            return null;
        }

        var outputWidth = 1500;
        var outputHeight = Math.round(outputWidth * (adminPromoAspect.height / adminPromoAspect.width));
        var canvas = document.createElement("canvas");
        canvas.width = outputWidth;
        canvas.height = outputHeight;

        var ctx = canvas.getContext("2d");
        if (!ctx) {
            return null;
        }

        var zoomValue = Math.max(1, Number(adminPromoCropState.zoom || 1));
        var bounds = getCoverRenderBounds(adminPromoPreviewWrap, adminPromoPreviewImage, zoomValue);
        var offsetScaleX = bounds.rectWidth > 0 ? (outputWidth / bounds.rectWidth) : 1;
        var offsetScaleY = bounds.rectHeight > 0 ? (outputHeight / bounds.rectHeight) : 1;
        var scaleToCover = Math.max(outputWidth / adminPromoPreviewImage.naturalWidth, outputHeight / adminPromoPreviewImage.naturalHeight);
        var scale = scaleToCover * zoomValue;
        var drawWidth = adminPromoPreviewImage.naturalWidth * scale;
        var drawHeight = adminPromoPreviewImage.naturalHeight * scale;
        var drawX = ((outputWidth - drawWidth) / 2) + (adminPromoCropState.offsetX * offsetScaleX);
        var drawY = ((outputHeight - drawHeight) / 2) + (adminPromoCropState.offsetY * offsetScaleY);

        ctx.clearRect(0, 0, outputWidth, outputHeight);
        ctx.drawImage(adminPromoPreviewImage, drawX, drawY, drawWidth, drawHeight);

        return canvas.toDataURL("image/png");
    }

    function closeAdminPromoEditModal() {
        if (!adminPromoEditBackdrop) {
            return;
        }

        setAdminPromoCropWorkspaceVisible(false);
        resetAdminPromoCropState();
        adminPromoCropState.previewBeforeCrop = "";
        adminPromoCropState.sourceImage = "";

        if (adminPromoFileInput) {
            adminPromoFileInput.value = "";
        }

        setAdminPromoPreviewSource("");
        updateAdminPromoRecropState();
        activeAdminPromoSlot = "";
        adminPromoEditBackdrop.hidden = true;
        document.body.classList.remove("admin-modal-open");
    }

    function openAdminPromoEditModal(slot) {
        if (!adminPromoEditBackdrop || !adminPromoForm) {
            return;
        }

        activeAdminPromoSlot = String(slot || "").trim();
        if (!activeAdminPromoSlot) {
            return;
        }

        if (adminPromoSlotNote) {
            adminPromoSlotNote.textContent = "Promo slot " + activeAdminPromoSlot + " image (3:1)";
        }

        setAdminPromoPreviewSource("");
        updateAdminPromoRecropState();

        if (adminPromoFileInput) {
            adminPromoFileInput.value = "";
        }

        adminPromoCropState.previewBeforeCrop = "";
        adminPromoCropState.sourceImage = "";
        resetAdminPromoCropState();
        setAdminPromoCropWorkspaceVisible(false);

        adminPromoEditBackdrop.hidden = false;
        document.body.classList.add("admin-modal-open");
    }

    if (adminPromoBanner && adminPromoRemove) {
        adminPromoRemove.addEventListener("click", function () {
            if (!adminPromoArchiveEndpoint) {
                return;
            }

            var activeSlide = getActivePromoSlide();
            if (!activeSlide) {
                updatePromoControls();
                return;
            }

            var slotValue = (activeSlide.getAttribute("data-promo-slot") || "").trim();
            var slotNumber = Number.parseInt(slotValue, 10);

            if (!slotValue || !Number.isFinite(slotNumber) || slotNumber < 1) {
                return;
            }

            adminPromoBanner.classList.add("is-admin-busy");
            adminPromoRemove.disabled = true;

            fetch(adminPromoArchiveEndpoint, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    slot: slotNumber
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
                        var message = result.payload && result.payload.message ? result.payload.message : "Unable to archive promo banner.";
                        throw new Error(message);
                    }

                    var archivedEntry = result.payload.archivedEntry && typeof result.payload.archivedEntry === "object"
                        ? result.payload.archivedEntry
                        : null;
                    var archivedAt = archivedEntry && archivedEntry.archivedAt ? String(archivedEntry.archivedAt) : "";
                    var archiveKey = archivedEntry && archivedEntry.archiveKey ? String(archivedEntry.archiveKey) : "";

                    activeSlide.remove();

                    updatePromoAddSlideSlot();

                    var promoSlides = getPromoSlides();
                    if (promoSlides.length) {
                        showPromoSlide(Math.min(promoIndex, promoSlides.length - 1));
                        stopPromoTimer();
                        startPromoTimer();
                    }

                    updatePromoControls();

                    if (archiveKey) {
                        showAdminUndoToast(
                            "Promo banner slot " + slotValue + " archived" + (archivedAt ? " (" + formatArchiveDateLabel(archivedAt) + ")" : ""),
                            {
                                type: "promo",
                                archiveKey: archiveKey,
                                button: adminPromoRemove,
                                onRestore: function (payload) {
                                    if (!promoCarousel) {
                                        return;
                                    }

                                    var restoredSlot = payload && payload.slot ? String(payload.slot) : slotValue;
                                    var restoredFilename = buildPromoSlotFilename(restoredSlot);
                                    var restoredSource = adminPromoImageBase + restoredFilename + "?t=" + String(Date.now());
                                    var slide = document.createElement("div");
                                    var slotClass = buildPromoSlideClass(restoredSlot);

                                    slide.className = "promo-slide " + slotClass;
                                    slide.setAttribute("data-promo-slot", restoredSlot);
                                    slide.innerHTML = '<img class="promo-image" src="' + restoredSource + '" alt="Promo banner slot ' + restoredSlot + '">';

                                    insertPromoSlideSorted(slide);
                                    updatePromoAddSlideSlot();

                                    var slidesAfterRestore = Array.prototype.slice.call(getPromoSlides());
                                    var restoredIndex = slidesAfterRestore.indexOf(slide);

                                    updatePromoControls();
                                    showPromoSlide(restoredIndex >= 0 ? restoredIndex : 0);
                                    stopPromoTimer();
                                    startPromoTimer();
                                }
                            }
                        );
                    }

                    adminPromoBanner.classList.remove("is-admin-busy");
                })
                .catch(function (error) {
                    adminPromoBanner.classList.remove("is-admin-busy");
                    updatePromoControls();
                    window.alert(error.message || "Unable to archive promo banner.");
                });
        });
    }

    if (promoCarousel) {
        promoCarousel.addEventListener("click", function (event) {
            var addButton = event.target.closest("[data-admin-promo-add-trigger]");

            if (!addButton) {
                return;
            }

            var addSlide = addButton.closest("[data-admin-promo-add-slide]");
            var slot = addSlide ? (addSlide.getAttribute("data-admin-promo-slot") || "") : "";

            openAdminPromoEditModal(slot);
        });
    }

    if (adminPromoZoom) {
        adminPromoZoom.addEventListener("input", function () {
            adminPromoCropState.zoom = Number.parseFloat(adminPromoZoom.value) || 1;
            var clamped = clampAdminPromoCropOffsets(adminPromoCropState.offsetX, adminPromoCropState.offsetY);
            adminPromoCropState.offsetX = clamped.x;
            adminPromoCropState.offsetY = clamped.y;
            syncAdminPromoPreviewTransform();
        });
    }

    if (adminPromoBrowse && adminPromoFileInput) {
        adminPromoBrowse.addEventListener("click", function () {
            adminPromoFileInput.click();
        });
    }

    if (adminPromoRecrop && adminPromoPreviewImage) {
        adminPromoRecrop.addEventListener("click", function () {
            if (adminPromoPreviewImage.hidden || !adminPromoPreviewImage.src) {
                return;
            }

            adminPromoCropState.previewBeforeCrop = adminPromoPreviewImage.src;
            adminPromoCropState.sourceImage = adminPromoPreviewImage.src;
            resetAdminPromoCropState();
            setAdminPromoCropWorkspaceVisible(true);
            syncAdminPromoPreviewTransform();
        });
    }

    if (adminPromoFileInput && adminPromoPreviewImage) {
        adminPromoPreviewImage.addEventListener("dragstart", function (event) {
            event.preventDefault();
        });

        adminPromoFileInput.addEventListener("change", function () {
            var file = adminPromoFileInput.files && adminPromoFileInput.files[0] ? adminPromoFileInput.files[0] : null;

            if (!file) {
                return;
            }

            var reader = new FileReader();
            reader.onload = function (loadEvent) {
                adminPromoCropState.previewBeforeCrop = adminPromoPreviewImage.hidden ? "" : (adminPromoPreviewImage.src || "");
                adminPromoCropState.sourceImage = String(loadEvent.target && loadEvent.target.result ? loadEvent.target.result : "");
                setAdminPromoPreviewSource(adminPromoCropState.sourceImage);
                updateAdminPromoRecropState();
                resetAdminPromoCropState();
                setAdminPromoCropWorkspaceVisible(true);
                syncAdminPromoPreviewTransform();
            };
            reader.readAsDataURL(file);
        });
    }

    if (adminPromoPreviewWrap) {
        adminPromoPreviewWrap.addEventListener("wheel", function (event) {
            if (!adminPromoCropState.isCropping || !adminPromoZoom) {
                return;
            }

            event.preventDefault();

            var minZoom = Number.parseFloat(adminPromoZoom.min || "1");
            var maxZoom = Number.parseFloat(adminPromoZoom.max || "3");
            var stepZoom = Number.parseFloat(adminPromoZoom.step || "0.01");
            var direction = event.deltaY < 0 ? 1 : -1;
            var nextZoom = adminPromoCropState.zoom + (direction * (stepZoom * 5));

            nextZoom = Math.min(maxZoom, Math.max(minZoom, nextZoom));
            nextZoom = Math.round(nextZoom * 100) / 100;

            adminPromoCropState.zoom = nextZoom;
            adminPromoZoom.value = String(nextZoom);

            var clamped = clampAdminPromoCropOffsets(adminPromoCropState.offsetX, adminPromoCropState.offsetY);
            adminPromoCropState.offsetX = clamped.x;
            adminPromoCropState.offsetY = clamped.y;
            syncAdminPromoPreviewTransform();
        }, { passive: false });

        adminPromoPreviewWrap.addEventListener("pointerdown", function (event) {
            if (!adminPromoCropState.isCropping || event.button !== 0) {
                return;
            }

            event.preventDefault();

            adminPromoCropState.isDragging = true;
            adminPromoCropState.dragPointerId = event.pointerId;
            adminPromoCropState.dragStartClientX = event.clientX;
            adminPromoCropState.dragStartClientY = event.clientY;
            adminPromoCropState.dragStartOffsetX = adminPromoCropState.offsetX;
            adminPromoCropState.dragStartOffsetY = adminPromoCropState.offsetY;
            adminPromoPreviewWrap.setPointerCapture(event.pointerId);
        });

        adminPromoPreviewWrap.addEventListener("pointermove", function (event) {
            if (!adminPromoCropState.isCropping || !adminPromoCropState.isDragging || adminPromoCropState.dragPointerId !== event.pointerId) {
                return;
            }

            var nextX = adminPromoCropState.dragStartOffsetX + (event.clientX - adminPromoCropState.dragStartClientX);
            var nextY = adminPromoCropState.dragStartOffsetY + (event.clientY - adminPromoCropState.dragStartClientY);
            var clamped = clampAdminPromoCropOffsets(nextX, nextY);
            adminPromoCropState.offsetX = clamped.x;
            adminPromoCropState.offsetY = clamped.y;
            syncAdminPromoPreviewTransform();
        });

        function stopAdminPromoCropDrag(event) {
            if (!adminPromoCropState.isDragging || adminPromoCropState.dragPointerId !== event.pointerId) {
                return;
            }

            adminPromoCropState.isDragging = false;
            adminPromoCropState.dragPointerId = null;
            adminPromoPreviewWrap.releasePointerCapture(event.pointerId);
        }

        adminPromoPreviewWrap.addEventListener("pointerup", stopAdminPromoCropDrag);
        adminPromoPreviewWrap.addEventListener("pointercancel", stopAdminPromoCropDrag);
    }

    if (adminPromoCropCancel && adminPromoPreviewImage) {
        adminPromoCropCancel.addEventListener("click", function () {
            setAdminPromoPreviewSource(adminPromoCropState.previewBeforeCrop || "");
            updateAdminPromoRecropState();
            adminPromoCropState.sourceImage = "";
            resetAdminPromoCropState();
            setAdminPromoCropWorkspaceVisible(false);
        });
    }

    if (adminPromoCropSave && adminPromoPreviewImage) {
        adminPromoCropSave.addEventListener("click", function () {
            var croppedDataUrl = buildAdminPromoCropDataUrlFromPreview();

            if (!croppedDataUrl) {
                return;
            }

            setAdminPromoPreviewSource(croppedDataUrl);
            adminPromoCropState.previewBeforeCrop = croppedDataUrl;
            adminPromoCropState.sourceImage = "";
            updateAdminPromoRecropState();
            resetAdminPromoCropState();
            setAdminPromoCropWorkspaceVisible(false);
        });
    }

    if (adminPromoClose) {
        adminPromoClose.addEventListener("click", closeAdminPromoEditModal);
    }

    if (adminPromoCancel) {
        adminPromoCancel.addEventListener("click", closeAdminPromoEditModal);
    }

    if (adminPromoEditBackdrop) {
        adminPromoEditBackdrop.addEventListener("click", function (event) {
            if (event.target === adminPromoEditBackdrop) {
                closeAdminPromoEditModal();
            }
        });
    }

    if (adminPromoForm) {
        adminPromoForm.addEventListener("submit", function (event) {
            event.preventDefault();

            if (!activeAdminPromoSlot || !adminPromoUpdateEndpoint) {
                return;
            }

            var finalPreviewSrc = adminPromoPreviewImage && !adminPromoPreviewImage.hidden ? adminPromoPreviewImage.src : "";

            if (adminPromoCropState.isCropping) {
                var autoCroppedDataUrl = buildAdminPromoCropDataUrlFromPreview();

                if (autoCroppedDataUrl) {
                    finalPreviewSrc = autoCroppedDataUrl;
                    setAdminPromoPreviewSource(autoCroppedDataUrl);
                    adminPromoCropState.previewBeforeCrop = autoCroppedDataUrl;
                }

                adminPromoCropState.sourceImage = "";
                resetAdminPromoCropState();
                setAdminPromoCropWorkspaceVisible(false);
            }

            if (!finalPreviewSrc) {
                window.alert("Please add an image first.");
                return;
            }

            var submitButton = adminPromoForm.querySelector('button[type="submit"]');
            var previousSubmitTitle = submitButton ? submitButton.getAttribute("title") : "";

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.setAttribute("title", "Saving...");
            }

            fetch(adminPromoUpdateEndpoint, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    slot: Number.parseInt(activeAdminPromoSlot, 10),
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
                        var message = result.payload && result.payload.message ? result.payload.message : "Unable to save promo banner.";
                        throw new Error(message);
                    }

                    var savedSlot = result.payload && result.payload.slot ? String(result.payload.slot) : activeAdminPromoSlot;
                    var savedFilename = buildPromoSlotFilename(savedSlot);
                    var savedSource = adminPromoImageBase + savedFilename + "?t=" + String(Date.now());
                    var existingSlide = promoBanner ? promoBanner.querySelector('.promo-slide[data-promo-slot="' + savedSlot + '"]') : null;
                    var savedSlide = existingSlide;

                    if (!savedSlide) {
                        savedSlide = document.createElement("div");
                        savedSlide.className = "promo-slide " + buildPromoSlideClass(savedSlot);
                        savedSlide.setAttribute("data-promo-slot", savedSlot);
                        savedSlide.innerHTML = '<img class="promo-image" src="' + savedSource + '" alt="Promo banner slot ' + savedSlot + '">';
                        insertPromoSlideSorted(savedSlide);
                    } else {
                        var image = savedSlide.querySelector(".promo-image");

                        if (!image) {
                            savedSlide.innerHTML = '<img class="promo-image" src="' + savedSource + '" alt="Promo banner slot ' + savedSlot + '">';
                        } else {
                            image.src = savedSource;
                            image.alt = "Promo banner slot " + savedSlot;
                        }
                    }

                    updatePromoAddSlideSlot();

                    var slidesAfterSave = Array.prototype.slice.call(getPromoSlides());
                    var savedIndex = slidesAfterSave.indexOf(savedSlide);

                    updatePromoControls();
                    showPromoSlide(savedIndex >= 0 ? savedIndex : 0);
                    stopPromoTimer();
                    startPromoTimer();

                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.setAttribute("title", previousSubmitTitle || "Save Changes");
                    }

                    closeAdminPromoEditModal();
                })
                .catch(function (error) {
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.setAttribute("title", previousSubmitTitle || "Save Changes");
                    }

                    window.alert(error.message || "Unable to save promo banner.");
                });
        });
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

    function setAdminNavSwapState(nav, isSwapped) {
        var primaryItems = nav.querySelectorAll('[data-admin-nav-item="primary"]');
        var swappedItems = nav.querySelectorAll('[data-admin-nav-item="swapped"]');
        var swapButton = nav.querySelector("[data-admin-nav-swap]");

        nav.classList.toggle("is-swapped", isSwapped);

        primaryItems.forEach(function (item) {
            item.hidden = isSwapped;
        });

        swappedItems.forEach(function (item) {
            item.hidden = !isSwapped;
        });

        if (swapButton) {
            swapButton.setAttribute("aria-pressed", isSwapped ? "true" : "false");
            swapButton.setAttribute("title", isSwapped ? "Show filters bar" : "Show management bar");
        }
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

        if (!slides.length) {
            gallery.classList.add("is-no-arrows");

            if (prevButton) {
                prevButton.hidden = true;
            }

            if (nextButton) {
                nextButton.hidden = true;
            }

            return;
        }

        if (slides.length === 1) {
            gallery.classList.add("is-no-arrows");

            if (prevButton) {
                prevButton.hidden = true;
            }

            if (nextButton) {
                nextButton.hidden = true;
            }
        } else {
            gallery.classList.remove("is-no-arrows");
        }

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

    updatePromoAddSlideSlot();
    updatePromoControls();

    if (promoBanner) {
        var promoSlides = getPromoSlides();

        if (promoSlides.length) {
            showPromoSlide(0);
            startPromoTimer();
        }

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

    adminNavBars.forEach(function (nav) {
        var swapButton = nav.querySelector("[data-admin-nav-swap]");
        var adminNavPills = nav.querySelectorAll("[data-admin-nav-pill]");
        var adminDashboardBaseUrl = String(nav.getAttribute("data-admin-dashboard-base-url") || "");
        var adminUsersFilter = nav.hasAttribute("data-admin-dashboard-nav") ? document.querySelector("[data-admin-users-filter]") : null;
        var adminUserRows = nav.hasAttribute("data-admin-dashboard-nav") ? document.querySelectorAll("[data-admin-user-row]") : [];
        var initialPanelTarget = nav.hasAttribute("data-admin-dashboard-nav")
            ? String(document.body.getAttribute("data-admin-initial-panel") || "").toLowerCase()
            : "";
        var dashboardDefaultSections = nav.hasAttribute("data-admin-dashboard-nav")
            ? document.querySelectorAll("[data-admin-dashboard-default]")
            : [];
        var dashboardPanels = nav.hasAttribute("data-admin-dashboard-nav")
            ? document.querySelectorAll("[data-admin-dashboard-panel]")
            : [];

        function setActiveAdminPill(nextPill) {
            adminNavPills.forEach(function (pill) {
                var isActive = pill === nextPill;

                pill.classList.toggle("is-active", isActive);

                if (isActive) {
                    pill.setAttribute("aria-current", "page");
                } else {
                    pill.removeAttribute("aria-current");
                }
            });

            if (nav.hasAttribute("data-admin-dashboard-nav")) {
                updateDashboardPanels();
                
                var panelTarget = nextPill ? nextPill.getAttribute("data-admin-panel-target") : null;
                if (panelTarget) {
                    var url = new URL(window.location.href);
                    url.searchParams.set("admin_view", panelTarget);
                    window.history.replaceState({ path: url.href }, "", url.href);
                }
            } else {
                var nextPanelTarget = nextPill ? String(nextPill.getAttribute("data-admin-panel-target") || "").toLowerCase() : "";

                if (nextPanelTarget && adminDashboardBaseUrl) {
                    var normalizedBase = adminDashboardBaseUrl;
                    var hasQuery = normalizedBase.indexOf("?") >= 0;
                    var hasHash = normalizedBase.indexOf("#") >= 0;

                    if (hasHash) {
                        normalizedBase = normalizedBase.split("#")[0];
                        hasQuery = normalizedBase.indexOf("?") >= 0;
                    }

                    var delimiter = hasQuery ? "&" : "?";
                    window.location.href = normalizedBase + delimiter + "admin_view=" + encodeURIComponent(nextPanelTarget);
                }
            }
        }

        function updateDashboardPanels() {
            if (!dashboardPanels.length && !dashboardDefaultSections.length) {
                return;
            }

            if (!nav.classList.contains("is-swapped")) {
                dashboardDefaultSections.forEach(function (section) {
                    section.hidden = false;
                });

                dashboardPanels.forEach(function (panel) {
                    panel.hidden = true;
                });

                return;
            }

            var activePill = nav.querySelector("[data-admin-nav-pill].is-active");
            var activeTarget = activePill ? (activePill.getAttribute("data-admin-panel-target") || "") : "";

            dashboardDefaultSections.forEach(function (section) {
                section.hidden = true;
            });

            dashboardPanels.forEach(function (panel) {
                var panelName = panel.getAttribute("data-admin-dashboard-panel") || "";
                panel.hidden = panelName !== activeTarget;
            });
        }

        function applyAdminUsersFilter() {
            if (!adminUsersFilter || !adminUserRows.length) {
                return;
            }

            var selectedRole = String(adminUsersFilter.value || "all").toLowerCase();

            adminUserRows.forEach(function (row) {
                var rowRole = String(row.getAttribute("data-role") || "").toLowerCase();
                var shouldShow = selectedRole === "all" || rowRole === selectedRole;
                row.hidden = !shouldShow;
            });
        }

        if (!swapButton) {
            return;
        }

        if (initialPanelTarget) {
            var initialPill = nav.querySelector('[data-admin-nav-pill][data-admin-panel-target="' + initialPanelTarget + '"]');
            setAdminNavSwapState(nav, true);
            setActiveAdminPill(initialPill || adminNavPills[0] || null);
        } else {
            setAdminNavSwapState(nav, false);
            updateDashboardPanels();
        }

        applyAdminUsersFilter();

        swapButton.addEventListener("click", function () {
            var shouldSwap = !nav.classList.contains("is-swapped");

            if (shouldSwap && nav.classList.contains("section-nav-interactive")) {
                closeFilterPanels(null);
            }

            setAdminNavSwapState(nav, shouldSwap);

            if (shouldSwap) {
                if (nav.hasAttribute("data-admin-dashboard-nav")) {
                    setActiveAdminPill(adminNavPills[0] || null);
                }
            } else {
                updateDashboardPanels();
                if (nav.hasAttribute("data-admin-dashboard-nav")) {
                    var url = new URL(window.location.href);
                    url.searchParams.delete("admin_view");
                    window.history.replaceState({ path: url.href }, "", url.href);
                }
            }
        });

        adminNavPills.forEach(function (pill) {
            pill.addEventListener("click", function () {
                setActiveAdminPill(pill);
            });
        });

        if (adminUsersFilter) {
            adminUsersFilter.addEventListener("change", function () {
                applyAdminUsersFilter();
            });
        }
    });

    function syncAdminModalBodyLock() {
        var hasVisibleUsersModal = Boolean(adminUsersCreateBackdrop && !adminUsersCreateBackdrop.hidden);
        var hasVisibleEquipmentArchiveModal = Boolean(adminEquipmentArchiveBackdrop && !adminEquipmentArchiveBackdrop.hidden);
        var hasVisibleEquipmentStatusModal = Boolean(adminEquipmentStatusBackdrop && !adminEquipmentStatusBackdrop.hidden);
        var hasVisibleActionModal = Boolean(adminActionModalBackdrop && !adminActionModalBackdrop.hidden);
        var hasVisibleEventEditModal = Boolean(adminEventEditBackdrop && !adminEventEditBackdrop.hidden);
        var hasVisibleEventThumbsModal = Boolean(adminEventThumbsBackdrop && !adminEventThumbsBackdrop.hidden);
        var hasVisibleBookingDetailModal = Boolean(adminBookingDetailBackdrop && !adminBookingDetailBackdrop.hidden);
        document.body.classList.toggle("admin-modal-open", hasVisibleUsersModal || hasVisibleEquipmentArchiveModal || hasVisibleEquipmentStatusModal || hasVisibleActionModal || hasVisibleEventEditModal || hasVisibleEventThumbsModal || hasVisibleBookingDetailModal);
    }

    function closeAdminActionModal(invokeOnClose) {
        if (!adminActionModalBackdrop) {
            return;
        }

        adminActionModalBackdrop.hidden = true;
        syncAdminModalBodyLock();

        var onCloseCallback = adminActionModalState.onClose;
        adminActionModalState.onConfirm = null;
        adminActionModalState.onClose = null;
        adminActionModalState.quantityRequired = false;

        if (adminActionModalConfirm) {
            adminActionModalConfirm.disabled = false;
            adminActionModalConfirm.textContent = "Confirm";
        }

        if (invokeOnClose && typeof onCloseCallback === "function") {
            onCloseCallback();
        }
    }

    function openAdminActionModal(options) {
        if (!adminActionModalBackdrop || !adminActionModalTitle || !adminActionModalMessage || !adminActionModalConfirm) {
            return;
        }

        var config = options || {};
        var requiresQuantity = Boolean(config.quantityRequired);
        var confirmLabel = String(config.confirmLabel || "Confirm");

        adminActionModalState.onConfirm = typeof config.onConfirm === "function" ? config.onConfirm : null;
        adminActionModalState.onClose = typeof config.onClose === "function" ? config.onClose : null;
        adminActionModalState.quantityRequired = requiresQuantity;

        adminActionModalTitle.textContent = String(config.title || "Confirm Action");
        adminActionModalMessage.textContent = String(config.message || "Please confirm this action.");
        adminActionModalConfirm.textContent = confirmLabel;
        adminActionModalConfirm.disabled = false;

        if (adminActionModalQuantityWrap && adminActionModalQuantityInput) {
            adminActionModalQuantityWrap.hidden = !requiresQuantity;

            if (requiresQuantity) {
                var minValue = Number.parseInt(String(config.quantityMin || "1"), 10);
                var maxValue = Number.parseInt(String(config.quantityMax || "200"), 10);
                var initialValue = Number.parseInt(String(config.quantityInitial || "1"), 10);

                if (!Number.isFinite(minValue) || minValue < 1) {
                    minValue = 1;
                }

                if (!Number.isFinite(maxValue) || maxValue < minValue) {
                    maxValue = 200;
                }

                if (!Number.isFinite(initialValue)) {
                    initialValue = minValue;
                }

                initialValue = Math.max(minValue, Math.min(maxValue, initialValue));

                adminActionModalQuantityInput.min = String(minValue);
                adminActionModalQuantityInput.max = String(maxValue);
                adminActionModalQuantityInput.value = String(initialValue);
            }
        }

        adminActionModalBackdrop.hidden = false;
        syncAdminModalBodyLock();

        window.requestAnimationFrame(function () {
            if (requiresQuantity && adminActionModalQuantityInput) {
                adminActionModalQuantityInput.focus();
                adminActionModalQuantityInput.select();
                return;
            }

            adminActionModalConfirm.focus();
        });
    }

    function openAdminUsersCreateModal() {
        if (!adminUsersCreateBackdrop) {
            return;
        }

        adminUsersCreateBackdrop.hidden = false;
        syncAdminModalBodyLock();
    }

    function closeAdminUsersCreateModal() {
        if (!adminUsersCreateBackdrop) {
            return;
        }

        adminUsersCreateBackdrop.hidden = true;
        syncAdminModalBodyLock();
    }

    function openAdminEquipmentArchiveModal() {
        if (!adminEquipmentArchiveBackdrop) {
            return;
        }

        adminEquipmentArchiveBackdrop.hidden = false;
        syncAdminModalBodyLock();
    }

    function closeAdminEquipmentArchiveModal() {
        if (!adminEquipmentArchiveBackdrop) {
            return;
        }

        adminEquipmentArchiveBackdrop.hidden = true;
        syncAdminModalBodyLock();
    }

    function openAdminEquipmentStatusModal() {
        if (!adminEquipmentStatusBackdrop) {
            return;
        }

        adminEquipmentStatusBackdrop.hidden = false;
        syncAdminModalBodyLock();
    }

    function closeAdminEquipmentStatusModal() {
        if (!adminEquipmentStatusBackdrop) {
            return;
        }

        adminEquipmentStatusBackdrop.hidden = true;
        syncAdminModalBodyLock();
    }

    function normalizeAdminBookingStatusClass(value) {
        var token = String(value || "status-pending")
            .toLowerCase()
            .replace(/[^a-z0-9-]+/g, "");

        if (token.indexOf("status-") !== 0) {
            return "status-pending";
        }

        return token;
    }

    function formatAdminBookingDate(value) {
        var normalized = String(value || "").trim();
        if (!/^\d{4}-\d{2}-\d{2}$/.test(normalized)) {
            return normalized;
        }

        var parts = normalized.split("-");
        var year = Number.parseInt(parts[0], 10);
        var month = Number.parseInt(parts[1], 10);
        var day = Number.parseInt(parts[2], 10);

        if (!Number.isFinite(year) || !Number.isFinite(month) || !Number.isFinite(day)) {
            return normalized;
        }

        var parsed = new Date(year, month - 1, day);
        if (Number.isNaN(parsed.getTime())) {
            return normalized;
        }

        return parsed.toLocaleDateString("en-US", {
            month: "short",
            day: "numeric",
            year: "numeric"
        });
    }

    function formatAdminBookingTime(value) {
        var normalized = String(value || "").trim();
        var match = normalized.match(/^(\d{2}):(\d{2})$/);

        if (!match) {
            return normalized;
        }

        var hour = Number.parseInt(match[1], 10);
        var minute = Number.parseInt(match[2], 10);

        if (!Number.isFinite(hour) || !Number.isFinite(minute)) {
            return normalized;
        }

        var suffix = hour >= 12 ? "PM" : "AM";
        var hour12 = hour % 12;
        if (hour12 === 0) {
            hour12 = 12;
        }

        return String(hour12).padStart(2, "0") + ":" + String(minute).padStart(2, "0") + " " + suffix;
    }

    function formatAdminBookingSchedule(dateValue, timeValue) {
        var dateLabel = formatAdminBookingDate(dateValue);
        var timeLabel = formatAdminBookingTime(timeValue);

        if (dateLabel && timeLabel) {
            return dateLabel + " " + timeLabel;
        }

        return dateLabel || timeLabel || "-";
    }

    function formatAdminBookingMethod(value) {
        var token = String(value || "").toLowerCase().trim();

        if (token === "pickup") {
            return "Pick-up";
        }

        if (token === "meetup") {
            return "Meet-up";
        }

        if (token === "delivery") {
            return "Delivery";
        }

        return token ? token : "-";
    }

    function formatAdminBookingCourier(value) {
        var token = String(value || "").toLowerCase().trim();

        if (token === "grab-express") {
            return "GrabExpress";
        }

        if (token === "j-and-t") {
            return "J&T Express";
        }

        if (token === "self-booked") {
            return "Self-booked";
        }

        if (token === "lbc") {
            return "LBC";
        }

        if (token === "lalamove") {
            return "Lalamove";
        }

        return token ? token : "-";
    }

    function formatAdminBookingPayment(value) {
        var token = String(value || "").toLowerCase().trim();

        if (token === "gcash") {
            return "GCash";
        }

        if (token === "cash-pickup") {
            return "Cash on Pickup";
        }

        if (token === "cash-meetup") {
            return "Cash on Meetup";
        }

        return token ? token : "-";
    }

    function findAdminBookingById(bookingId) {
        var targetId = String(bookingId || "").trim();

        if (!targetId) {
            return null;
        }

        var found = null;

        adminBookingsSource.some(function (entry) {
            if (!entry || typeof entry !== "object") {
                return false;
            }

            if (String(entry.id || "") !== targetId) {
                return false;
            }

            found = entry;
            return true;
        });

        return found;
    }

    function populateAdminBookingDetails(booking) {
        if (!booking) {
            return;
        }

        if (adminBookingDetailName) {
            adminBookingDetailName.textContent = String(booking.name || "-");
        }

        if (adminBookingDetailEmail) {
            var emailText = String(booking.email || "").trim();
            adminBookingDetailEmail.textContent = emailText || "-";
        }

        if (adminBookingDetailOrderNumber) {
            adminBookingDetailOrderNumber.textContent = String(booking.orderNumber || booking.id || "-");
        }

        if (adminBookingDetailTimestamp) {
            adminBookingDetailTimestamp.textContent = String(booking.timestamp || "-");
        }

        if (adminBookingDetailStatus) {
            var statusClass = normalizeAdminBookingStatusClass(booking.statusClass || "status-pending");
            adminBookingDetailStatus.className = "admin-bookings-status " + statusClass;
            adminBookingDetailStatus.textContent = String(booking.status || "PENDING");
        }

        if (adminBookingDetailPlace) {
            var placeText = String(booking.place || "").trim();
            adminBookingDetailPlace.textContent = placeText || "-";
        }

        if (adminBookingDetailReceive) {
            adminBookingDetailReceive.textContent = formatAdminBookingSchedule(booking.receiveDate, booking.receiveTime);
        }

        if (adminBookingDetailReturn) {
            adminBookingDetailReturn.textContent = formatAdminBookingSchedule(booking.returnDate, booking.returnTime);
        }

        if (adminBookingDetailReceivingMethod) {
            adminBookingDetailReceivingMethod.textContent = formatAdminBookingMethod(booking.receivingMethod);
        }

        if (adminBookingDetailReturningMethod) {
            adminBookingDetailReturningMethod.textContent = formatAdminBookingMethod(booking.returningMethod);
        }

        if (adminBookingDetailCourier) {
            adminBookingDetailCourier.textContent = formatAdminBookingCourier(booking.courier);
        }

        if (adminBookingDetailPaymentMethod) {
            adminBookingDetailPaymentMethod.textContent = formatAdminBookingPayment(booking.paymentMethod);
        }

        if (adminBookingStatusOrderIdInput) {
            adminBookingStatusOrderIdInput.value = String(booking.id || "");
        }

        if (adminBookingDetailItems) {
            adminBookingDetailItems.innerHTML = "";

            var bookingItems = Array.isArray(booking.items) ? booking.items : [];

            if (!bookingItems.length) {
                var emptyItem = document.createElement("li");
                emptyItem.textContent = "No item details found.";
                adminBookingDetailItems.appendChild(emptyItem);
            } else {
                bookingItems.forEach(function (item) {
                    if (!item || typeof item !== "object") {
                        return;
                    }

                    var name = String(item.name || "Item");
                    var qty = Number.parseInt(item.qty, 10);
                    var days = Number.parseInt(item.days, 10);

                    if (!Number.isFinite(qty) || qty < 1) {
                        qty = 1;
                    }

                    if (!Number.isFinite(days) || days < 1) {
                        days = 1;
                    }

                    var dayLabel = days === 1 ? "day" : "days";
                    var entry = document.createElement("li");
                    entry.textContent = name + " - Quantity " + qty + ", " + days + " " + dayLabel;
                    adminBookingDetailItems.appendChild(entry);
                });
            }
        }
    }

    function openAdminBookingDetail(bookingId) {
        if (!adminBookingDetailBackdrop) {
            return;
        }

        var booking = findAdminBookingById(bookingId);
        if (!booking) {
            return;
        }

        populateAdminBookingDetails(booking);
        adminBookingDetailBackdrop.hidden = false;
        syncAdminModalBodyLock();
    }

    function closeAdminBookingDetail() {
        if (!adminBookingDetailBackdrop) {
            return;
        }

        adminBookingDetailBackdrop.hidden = true;
        syncAdminModalBodyLock();
    }

    adminUsersOpenModalButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            openAdminUsersCreateModal();
        });
    });

    adminUsersCloseModalButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            closeAdminUsersCreateModal();
        });
    });

    if (adminUsersCreateBackdrop) {
        adminUsersCreateBackdrop.addEventListener("click", function (event) {
            if (event.target === adminUsersCreateBackdrop) {
                closeAdminUsersCreateModal();
            }
        });
    }

    adminEquipmentArchiveOpenButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            openAdminEquipmentArchiveModal();
        });
    });

    adminEquipmentArchiveCloseButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            closeAdminEquipmentArchiveModal();
        });
    });

    if (adminEquipmentArchiveBackdrop) {
        adminEquipmentArchiveBackdrop.addEventListener("click", function (event) {
            if (event.target === adminEquipmentArchiveBackdrop) {
                closeAdminEquipmentArchiveModal();
            }
        });
    }

    adminEquipmentStatusOpenButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            openAdminEquipmentStatusModal();
        });
    });

    adminEquipmentStatusCloseButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            closeAdminEquipmentStatusModal();
        });
    });

    if (adminEquipmentStatusBackdrop) {
        adminEquipmentStatusBackdrop.addEventListener("click", function (event) {
            if (event.target === adminEquipmentStatusBackdrop) {
                closeAdminEquipmentStatusModal();
            }
        });
    }

    adminBookingRows.forEach(function (row) {
        row.addEventListener("click", function () {
            openAdminBookingDetail(row.getAttribute("data-admin-booking-id") || "");
        });

        row.addEventListener("keydown", function (event) {
            if (event.key !== "Enter" && event.key !== " ") {
                return;
            }

            event.preventDefault();
            openAdminBookingDetail(row.getAttribute("data-admin-booking-id") || "");
        });
    });

    adminBookingDetailCloseButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            closeAdminBookingDetail();
        });
    });

    if (adminBookingDetailBackdrop) {
        adminBookingDetailBackdrop.addEventListener("click", function (event) {
            if (event.target === adminBookingDetailBackdrop) {
                closeAdminBookingDetail();
            }
        });
    }

    if (adminActionModalBackdrop) {
        adminActionModalBackdrop.addEventListener("click", function (event) {
            if (event.target === adminActionModalBackdrop) {
                closeAdminActionModal(true);
            }
        });
    }

    adminActionModalCancelButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            closeAdminActionModal(true);
        });
    });

    if (adminActionModalConfirm) {
        adminActionModalConfirm.addEventListener("click", function () {
            var onConfirmCallback = adminActionModalState.onConfirm;
            var onCloseCallback = adminActionModalState.onClose;
            var quantityValue = 1;

            if (adminActionModalState.quantityRequired && adminActionModalQuantityInput) {
                var parsedQuantity = Number.parseInt(String(adminActionModalQuantityInput.value || "1"), 10);
                var minQuantity = Number.parseInt(String(adminActionModalQuantityInput.min || "1"), 10);
                var maxQuantity = Number.parseInt(String(adminActionModalQuantityInput.max || "200"), 10);

                if (!Number.isFinite(minQuantity) || minQuantity < 1) {
                    minQuantity = 1;
                }

                if (!Number.isFinite(maxQuantity) || maxQuantity < minQuantity) {
                    maxQuantity = 200;
                }

                if (!Number.isFinite(parsedQuantity)) {
                    parsedQuantity = minQuantity;
                }

                parsedQuantity = Math.max(minQuantity, Math.min(maxQuantity, parsedQuantity));
                adminActionModalQuantityInput.value = String(parsedQuantity);
                quantityValue = parsedQuantity;
            }

            closeAdminActionModal(false);

            if (typeof onConfirmCallback === "function") {
                onConfirmCallback(quantityValue);
            }

            if (typeof onCloseCallback === "function") {
                onCloseCallback();
            }
        });
    }

    if (adminActionModalQuantityInput) {
        adminActionModalQuantityInput.addEventListener("keydown", function (event) {
            if (event.key === "Enter" && adminActionModalConfirm && !adminActionModalConfirm.disabled) {
                event.preventDefault();
                adminActionModalConfirm.click();
            }
        });
    }

    adminEquipmentAddButtons.forEach(function (button) {
        button.addEventListener("click", function (event) {
            event.preventDefault();

            var form = button.closest("form");
            if (!form) {
                return;
            }

            var quantityInput = form.querySelector('input[name="quantity"]');
            var modelName = String(button.getAttribute("data-model") || "this model");

            openAdminActionModal({
                title: "Add Equipment Quantity",
                message: "How many units do you want to add for " + modelName + "? Removed IDs will be reused first.",
                confirmLabel: "Add",
                quantityRequired: true,
                quantityMin: 1,
                quantityMax: 200,
                quantityInitial: 1,
                onConfirm: function (quantityValue) {
                    if (quantityInput) {
                        quantityInput.value = String(quantityValue);
                    }

                    form.submit();
                }
            });
        });
    });

    adminEquipmentRemoveButtons.forEach(function (button) {
        button.addEventListener("click", function (event) {
            event.preventDefault();

            var form = button.closest("form");
            if (!form) {
                return;
            }

            var willArchive = button.getAttribute("data-will-archive") === "true";
            var unitId = String(button.getAttribute("data-unit-id") || "this unit");
            var modelName = String(button.getAttribute("data-model") || "the selected model");
            var confirmMessage = willArchive
                ? "Removing the last quantity will archive the featured product and all of its equipment units. Continue?"
                : "Remove " + unitId + " from active inventory?";
            var title = willArchive ? "Archive Product Warning" : "Remove Equipment Unit";
            var description = willArchive
                ? modelName + " only has one active unit left. Removing it will archive the featured product and all related inventory units."
                : confirmMessage;

            openAdminActionModal({
                title: title,
                message: description,
                confirmLabel: willArchive ? "Archive Product" : "Remove Unit",
                onConfirm: function () {
                    form.submit();
                }
            });
        });
    });

    adminEquipmentStatusDeleteButtons.forEach(function (button) {
        button.addEventListener("click", function (event) {
            if (button.disabled) {
                return;
            }

            event.preventDefault();

            var form = button.closest("form");
            if (!form) {
                return;
            }

            var statusLabel = String(button.getAttribute("data-status-label") || "this status");

            openAdminActionModal({
                title: "Remove Status",
                message: "Remove status \"" + statusLabel + "\"? Equipment using this status will be reassigned automatically.",
                confirmLabel: "Remove Status",
                onConfirm: function () {
                    form.submit();
                }
            });
        });
    });

    if (shouldOpenAdminUsersCreateModal) {
        openAdminUsersCreateModal();
    }

    if (shouldOpenAdminEquipmentArchiveModal) {
        openAdminEquipmentArchiveModal();
    }

    if (shouldOpenAdminEquipmentStatusModal) {
        openAdminEquipmentStatusModal();
    }

    document.addEventListener("keydown", function (event) {
        if (event.key !== "Escape") {
            return;
        }

        if (adminUsersCreateBackdrop && !adminUsersCreateBackdrop.hidden) {
            closeAdminUsersCreateModal();
        }

        if (adminEquipmentArchiveBackdrop && !adminEquipmentArchiveBackdrop.hidden) {
            closeAdminEquipmentArchiveModal();
        }

        if (adminEquipmentStatusBackdrop && !adminEquipmentStatusBackdrop.hidden) {
            closeAdminEquipmentStatusModal();
        }

        if (adminActionModalBackdrop && !adminActionModalBackdrop.hidden) {
            closeAdminActionModal(true);
        }

        if (adminBookingDetailBackdrop && !adminBookingDetailBackdrop.hidden) {
            closeAdminBookingDetail();
        }
    });

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
        var badges = document.querySelectorAll(".cart-count:not(.topbar-notification-count)");

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
        var deliveryOnlyBlock = bookingCard ? bookingCard.querySelector("[data-delivery-only-block]") : null;
        var courierSelect = bookingCard ? bookingCard.querySelector("[data-booking-field='courier']") : null;
        var placeSelect = bookingCard ? bookingCard.querySelector("[data-booking-field='place']") : null;
        var receiveDateInput = bookingCard ? bookingCard.querySelector("[data-booking-field='receiveDate']") : null;
        var returnDateInput = bookingCard ? bookingCard.querySelector("[data-booking-field='returnDate']") : null;
        var receiveTimeSelect = bookingCard ? bookingCard.querySelector("[data-booking-field='receiveTime']") : null;
        var returnTimeSelect = bookingCard ? bookingCard.querySelector("[data-booking-field='returnTime']") : null;
        var receiveMethodInputs = bookingCard ? bookingCard.querySelectorAll("input[name='receivingMethod']") : [];
        var returnMethodInputs = bookingCard ? bookingCard.querySelectorAll("input[name='returningMethod']") : [];
        var uploadInputs = bookingCard ? bookingCard.querySelectorAll("input[type='file'][data-booking-field]") : [];
        var cartNavButtons = document.querySelectorAll("[data-cart-nav]");
        var cartSidebar = document.querySelector("[data-cart-sidebar]");
        var cartLayout = document.querySelector(".cart-layout");
        var cartViewPanels = {
            cart: document.querySelector("[data-cart-view='cart']"),
            orderStatus: document.querySelector("[data-cart-view='order-status']")
        };
        var orderStatusList = document.querySelector("[data-cart-orders-list]");
        var orderStatusEmpty = document.querySelector("[data-cart-orders-empty]");
        var orderSubmitEndpoint = typeof window.__creatyCustomerOrderSubmitEndpoint === "string"
            ? String(window.__creatyCustomerOrderSubmitEndpoint || "")
            : "";
        var serverOrders = Array.isArray(window.__creatyCustomerOrders)
            ? window.__creatyCustomerOrders.slice()
            : [];
        var bookingState = loadJsonStorage(bookingStorageKey, {});
        var unavailableModal = document.querySelector("[data-cart-unavailable-modal]");
        var unavailableModalMessage = unavailableModal ? unavailableModal.querySelector("[data-cart-unavailable-message]") : null;
        var unavailableModalConfirm = unavailableModal ? unavailableModal.querySelector("[data-cart-unavailable-confirm]") : null;
        var unavailableModalCloseButtons = unavailableModal ? unavailableModal.querySelectorAll("[data-cart-unavailable-close]") : [];
        var availableCartItemIdsSource = Array.isArray(window.__creatyCartAvailableItemIds) ? window.__creatyCartAvailableItemIds : [];
        var availableCartItemIdsSet = availableCartItemIdsSource.reduce(function (set, itemId) {
            if (typeof itemId === "string" && itemId) {
                set[itemId] = true;
            }

            return set;
        }, {});
        var hasAvailabilitySnapshot = Object.keys(availableCartItemIdsSet).length > 0;

        function isUnavailableCartItem(item) {
            if (!item || !item.id || !hasAvailabilitySnapshot) {
                return false;
            }

            return !availableCartItemIdsSet[item.id];
        }

        function getUnavailableCartItems(items) {
            var source = Array.isArray(items) ? items : getCartItems();

            return source.filter(function (item) {
                return isUnavailableCartItem(item);
            });
        }

        function removeUnavailableCartItems() {
            var items = getCartItems();
            var filteredItems = items.filter(function (item) {
                return !isUnavailableCartItem(item);
            });

            saveCartItems(filteredItems);
            return filteredItems;
        }

        function closeUnavailableModal() {
            if (!unavailableModal) {
                return;
            }

            unavailableModal.hidden = true;
        }

        function openUnavailableModal(items, onConfirm) {
            if (!unavailableModal || !unavailableModalConfirm) {
                if (typeof onConfirm === "function") {
                    onConfirm();
                }
                return;
            }

            var unavailableItems = Array.isArray(items) ? items : [];
            var unavailableNames = unavailableItems.map(function (item) {
                return String(item && item.name ? item.name : "Item");
            });

            if (unavailableModalMessage) {
                if (!unavailableNames.length) {
                    unavailableModalMessage.textContent = "Some items are no longer available and will be removed from your cart.";
                } else {
                    unavailableModalMessage.textContent = "The following items are out of stock and will be removed: " + unavailableNames.join(", ") + ".";
                }
            }

            unavailableModalConfirm.onclick = function () {
                closeUnavailableModal();

                if (typeof onConfirm === "function") {
                    onConfirm();
                }
            };

            unavailableModal.hidden = false;
        }

        unavailableModalCloseButtons.forEach(function (button) {
            button.addEventListener("click", function () {
                closeUnavailableModal();
            });
        });

        function setCartView(nextView) {
            var normalizedView = nextView === "order-status" ? "order-status" : "cart";

            if (cartViewPanels.cart) {
                cartViewPanels.cart.hidden = normalizedView !== "cart";
            }

            if (cartViewPanels.orderStatus) {
                cartViewPanels.orderStatus.hidden = normalizedView !== "order-status";
            }

            if (cartSidebar) {
                cartSidebar.hidden = normalizedView !== "cart";
            }

            if (cartLayout) {
                cartLayout.classList.toggle("is-order-status-view", normalizedView === "order-status");
            }

            if (normalizedView === "order-status") {
                renderOrderStatusList();
            }

            Array.prototype.forEach.call(cartNavButtons, function (button) {
                var buttonView = button.getAttribute("data-cart-nav") || "";
                var isActive = buttonView === normalizedView;

                button.classList.toggle("is-active", isActive);

                if (isActive) {
                    button.setAttribute("aria-current", "page");
                } else {
                    button.removeAttribute("aria-current");
                }
            });
        }

        function normalizeStoredOrder(order) {
            if (!order || typeof order !== "object") {
                return null;
            }

            var items = Array.isArray(order.items)
                ? order.items.map(function (item) {
                    if (!item || typeof item !== "object") {
                        return null;
                    }

                    var qty = Number.parseInt(item.qty, 10);
                    var days = Number.parseInt(item.days, 10);

                    return {
                        name: String(item.name || "Item"),
                        qty: Number.isFinite(qty) && qty > 0 ? qty : 1,
                        days: Number.isFinite(days) && days > 0 ? days : 1
                    };
                }).filter(function (item) {
                    return Boolean(item);
                })
                : [];

            return {
                id: String(order.id || ""),
                status: String(order.status || "Pending"),
                items: items,
                receiveDate: String(order.receiveDate || ""),
                receiveTime: String(order.receiveTime || ""),
                returnDate: String(order.returnDate || ""),
                returnTime: String(order.returnTime || ""),
                place: String(order.place || ""),
                paymentMethod: String(order.paymentMethod || ""),
                createdAt: String(order.createdAt || "")
            };
        }

        function getStoredOrders() {
            return serverOrders
                .map(function (order) {
                    return normalizeStoredOrder(order);
                })
                .filter(function (order) {
                    return Boolean(order);
                });
        }

        function saveStoredOrders(orders) {
            if (!Array.isArray(orders)) {
                serverOrders = [];
                return;
            }

            serverOrders = orders
                .map(function (order) {
                    return normalizeStoredOrder(order);
                })
                .filter(function (order) {
                    return Boolean(order);
                });
        }

        function submitPendingOrder(orderRecord) {
            if (!orderSubmitEndpoint) {
                return Promise.reject(new Error("Order endpoint is unavailable."));
            }

            return window.fetch(orderSubmitEndpoint, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json"
                },
                body: JSON.stringify({
                    order: orderRecord
                })
            }).then(function (response) {
                return response.text().then(function (rawBody) {
                    var payload = {};

                    try {
                        payload = JSON.parse(rawBody);
                    } catch (error) {
                        payload = {};
                    }

                    if (!response.ok || !payload || payload.ok !== true) {
                        var errorMessage = payload && payload.message
                            ? String(payload.message)
                            : "Unable to save booking right now.";
                        throw new Error(errorMessage);
                    }

                    return payload;
                });
            });
        }

        function formatTimeDisplay(timeValue) {
            var normalized = String(timeValue || "").trim();
            if (!normalized) {
                return "";
            }

            var parts = normalized.split(":");
            if (parts.length < 2) {
                return normalized;
            }

            var hour = Number.parseInt(parts[0], 10);
            var minute = Number.parseInt(parts[1], 10);

            if (!Number.isFinite(hour) || !Number.isFinite(minute)) {
                return normalized;
            }

            var suffix = hour >= 12 ? "PM" : "AM";
            var hour12 = hour % 12;
            if (hour12 === 0) {
                hour12 = 12;
            }

            return padTwo(hour12) + ":" + padTwo(minute) + " " + suffix;
        }

        function formatDateDisplay(dateValue) {
            var normalized = String(dateValue || "").trim();
            if (!normalized) {
                return "";
            }

            var parts = normalized.split("-");
            if (parts.length !== 3) {
                return normalized;
            }

            var year = Number.parseInt(parts[0], 10);
            var month = Number.parseInt(parts[1], 10);
            var day = Number.parseInt(parts[2], 10);

            if (!Number.isFinite(year) || !Number.isFinite(month) || !Number.isFinite(day)) {
                return normalized;
            }

            var parsedDate = new Date(year, month - 1, day);
            if (Number.isNaN(parsedDate.getTime())) {
                return normalized;
            }

            return parsedDate.toLocaleDateString("en-US", {
                month: "short",
                day: "numeric",
                year: "numeric"
            });
        }

        function formatOrderSchedule(dateValue, timeValue) {
            var dateLabel = formatDateDisplay(dateValue);
            var timeLabel = formatTimeDisplay(timeValue);

            if (dateLabel && timeLabel) {
                return dateLabel + " " + timeLabel;
            }

            return dateLabel || timeLabel;
        }

        function buildOrderItemsSummary(items) {
            if (!Array.isArray(items) || !items.length) {
                return "No items";
            }

            return items.map(function (item) {
                var name = String(item.name || "Item");
                var qty = Number.isFinite(item.qty) && item.qty > 0 ? item.qty : 1;
                var days = Number.isFinite(item.days) && item.days > 0 ? item.days : 1;
                var dayLabel = days === 1 ? "day" : "days";

                return name + " x" + qty + " (" + days + " " + dayLabel + ")";
            }).join(", ");
        }

        function createPendingOrderRecord(items) {
            var booking = getBookingSnapshot();
            var source = Array.isArray(items) ? items : getCartItems();
            var normalizedItems = source
                .filter(function (item) {
                    return item && !isUnavailableCartItem(item);
                })
                .map(function (item) {
                    return {
                        name: String(item.name || "Item"),
                        qty: Number.isFinite(item.qty) && item.qty > 0 ? item.qty : 1,
                        days: Number.isFinite(item.days) && item.days > 0 ? item.days : 1
                    };
                });

            if (!normalizedItems.length) {
                return null;
            }

            return {
                id: "booking-" + Date.now() + "-" + Math.floor(Math.random() * 900 + 100),
                status: "Pending",
                items: normalizedItems,
                receiveDate: booking.receiveDate || "",
                receiveTime: booking.receiveTime || "",
                returnDate: booking.returnDate || "",
                returnTime: booking.returnTime || "",
                place: booking.place || "",
                receivingMethod: booking.receivingMethod || "",
                returningMethod: booking.returningMethod || "",
                courier: booking.courier || "",
                paymentMethod: booking.paymentMethod || "",
                createdAt: new Date().toISOString()
            };
        }

        function renderOrderStatusList() {
            if (!orderStatusList) {
                return;
            }

            orderStatusList.innerHTML = "";

            var pendingOrders = getStoredOrders().filter(function (order) {
                return String(order.status || "").toLowerCase() === "pending";
            });

            if (orderStatusEmpty) {
                orderStatusEmpty.hidden = pendingOrders.length > 0;
            }

            pendingOrders.forEach(function (order) {
                var statusText = String(order.status || "Pending");
                var statusSlug = statusText.toLowerCase().replace(/[^a-z0-9]+/g, "-");
                var paymentMethodSlug = String(order.paymentMethod || "").toLowerCase().trim();
                var orderedText = "Ordered: " + buildOrderItemsSummary(order.items);
                var receiveSchedule = formatOrderSchedule(order.receiveDate, order.receiveTime);
                var returnSchedule = formatOrderSchedule(order.returnDate, order.returnTime);
                var scheduleText = "Date: " + (receiveSchedule && returnSchedule
                    ? receiveSchedule + " to " + returnSchedule
                    : receiveSchedule || returnSchedule || "Not set");

                var orderItem = document.createElement("article");
                orderItem.className = "profile-order-item";

                var orderMeta = document.createElement("div");
                orderMeta.className = "profile-order-meta";

                var orderedLine = document.createElement("p");
                orderedLine.className = "profile-order-id cart-order-status-ordered";
                orderedLine.textContent = orderedText;

                var scheduleLine = document.createElement("p");
                scheduleLine.className = "cart-order-status-date";
                scheduleLine.textContent = scheduleText;

                var statusBadge = document.createElement("span");
                statusBadge.className = "profile-order-status status-" + statusSlug;
                statusBadge.textContent = statusText;

                orderMeta.appendChild(orderedLine);
                orderMeta.appendChild(scheduleLine);
                orderMeta.appendChild(statusBadge);
                orderItem.appendChild(orderMeta);

                if (statusSlug === "pending" && paymentMethodSlug === "gcash") {
                    var pendingAction = document.createElement("button");
                    pendingAction.type = "button";
                    pendingAction.className = "profile-order-action primary";
                    pendingAction.textContent = "Upload Payment Receipt";
                    orderItem.appendChild(pendingAction);
                }

                orderStatusList.appendChild(orderItem);
            });
        }

        cartNavButtons.forEach(function (button) {
            button.addEventListener("click", function () {
                setCartView(button.getAttribute("data-cart-nav") || "cart");
            });
        });

        setCartView("cart");

        function getMethodValue(inputList, fallbackValue) {
            var checked = Array.prototype.find.call(inputList, function (input) {
                return input.checked;
            });

            return checked ? checked.value : fallbackValue;
        }

        function restoreBookingDefaults() {
            var now = new Date();
            var today = now.toISOString().slice(0, 10);

            if (receiveDateInput) {
                receiveDateInput.min = today;
                receiveDateInput.value = bookingState.receiveDate || today;
            }

            if (returnDateInput) {
                returnDateInput.min = receiveDateInput ? receiveDateInput.value : today;
            }

            if (placeSelect) {
                if (bookingState.place) {
                    placeSelect.value = bookingState.place;
                }

                if (!placeSelect.value && placeSelect.options.length) {
                    placeSelect.selectedIndex = 0;
                }
            }

            if (courierSelect && bookingState.courier) {
                courierSelect.value = bookingState.courier;
            }

            if (receiveTimeSelect && bookingState.receiveTime) {
                receiveTimeSelect.value = bookingState.receiveTime;
            }

            if (paymentSelect) {
                if (bookingState.paymentMethod) {
                    paymentSelect.value = bookingState.paymentMethod;
                }

                if (!paymentSelect.value && paymentSelect.options.length) {
                    paymentSelect.selectedIndex = 0;
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

            var derivedSchedule = buildDerivedReturnSchedule();

            if (returnDateInput && derivedSchedule.date) {
                returnDateInput.value = derivedSchedule.date;
            }

            if (returnTimeSelect && derivedSchedule.time) {
                returnTimeSelect.value = derivedSchedule.time;
            }

            return {
                receiveDate: receiveDateInput ? receiveDateInput.value : "",
                receiveTime: receiveTimeSelect ? receiveTimeSelect.value : "",
                place: placeSelect ? placeSelect.value : "",
                returnDate: derivedSchedule.date || (returnDateInput ? returnDateInput.value : ""),
                returnTime: derivedSchedule.time || (returnTimeSelect ? returnTimeSelect.value : ""),
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

        function getCartRentalDays(items) {
            var source = Array.isArray(items) ? items : getCartItems();
            var maxDays = 1;

            source.forEach(function (item) {
                if (!item || isUnavailableCartItem(item)) {
                    return;
                }

                var itemDays = Number.parseInt(item.days, 10);
                if (!Number.isFinite(itemDays) || itemDays < 1) {
                    itemDays = 1;
                }

                if (itemDays > maxDays) {
                    maxDays = itemDays;
                }
            });

            return maxDays;
        }

        function buildDerivedReturnSchedule(items) {
            var receiveDateValue = receiveDateInput ? String(receiveDateInput.value || "").trim() : "";
            var receiveTimeValue = receiveTimeSelect ? String(receiveTimeSelect.value || "").trim() : "";

            if (!receiveDateValue) {
                return {
                    date: "",
                    time: receiveTimeValue
                };
            }

            var dateParts = receiveDateValue.split("-");
            if (dateParts.length !== 3) {
                return {
                    date: "",
                    time: receiveTimeValue
                };
            }

            var year = Number.parseInt(dateParts[0], 10);
            var month = Number.parseInt(dateParts[1], 10);
            var day = Number.parseInt(dateParts[2], 10);

            if (!Number.isFinite(year) || !Number.isFinite(month) || !Number.isFinite(day)) {
                return {
                    date: "",
                    time: receiveTimeValue
                };
            }

            var rentalDays = getCartRentalDays(items);
            var returnDate = new Date(year, month - 1, day + rentalDays);

            if (Number.isNaN(returnDate.getTime())) {
                return {
                    date: "",
                    time: receiveTimeValue
                };
            }

            return {
                date: dateKeyFromDate(returnDate),
                time: receiveTimeValue
            };
        }

        function syncReturnDateTimeFromReceive(items) {
            if (!returnDateInput && !returnTimeSelect) {
                return;
            }

            var receiveDateValue = receiveDateInput ? String(receiveDateInput.value || "").trim() : "";
            var derivedSchedule = buildDerivedReturnSchedule(items);

            if (returnDateInput) {
                if (receiveDateValue) {
                    returnDateInput.min = receiveDateValue;
                }

                if (derivedSchedule.date) {
                    returnDateInput.value = derivedSchedule.date;
                }

                if (!returnDateInput.readOnly) {
                    returnDateInput.readOnly = true;
                }

                if (!returnDateInput.hasAttribute("readonly")) {
                    returnDateInput.setAttribute("readonly", "readonly");
                }

                if (!returnDateInput.disabled) {
                    returnDateInput.disabled = true;
                }

                if (returnDateInput.getAttribute("aria-disabled") !== "true") {
                    returnDateInput.setAttribute("aria-disabled", "true");
                }

                if (returnDateInput.tabIndex !== -1) {
                    returnDateInput.tabIndex = -1;
                }
            }

            if (returnTimeSelect) {
                if (derivedSchedule.time) {
                    returnTimeSelect.value = derivedSchedule.time;
                }

                if (!returnTimeSelect.disabled) {
                    returnTimeSelect.disabled = true;
                }

                if (returnTimeSelect.getAttribute("aria-disabled") !== "true") {
                    returnTimeSelect.setAttribute("aria-disabled", "true");
                }

                if (returnTimeSelect.tabIndex !== -1) {
                    returnTimeSelect.tabIndex = -1;
                }
            }
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
                var itemIsUnavailable = isUnavailableCartItem(item);
                var lineTotal = itemIsUnavailable ? 0 : (item.price * item.qty * item.days);
                var nameLabel = String(item.name).toUpperCase();
                var card = document.createElement("article");
                card.className = "cart-item-card" + (itemIsUnavailable ? " is-unavailable" : "");
                card.setAttribute("data-cart-item-id", item.id);
                card.innerHTML = '' +
                    '<div class="cart-item-copy">' +
                        '<h2 class="cart-item-name' + (itemIsUnavailable ? ' is-unavailable' : '') + '">' + escapeHtml(nameLabel) + (itemIsUnavailable ? ' <span class="cart-item-stock-note">(OUT OF STOCK)</span>' : '') + '</h2>' +
                        '<p>' + escapeHtml(item.copy) + '</p>' +
                        '<label class="cart-mini-field">' +
                            '<span>Qty</span>' +
                            '<input type="number" min="1" max="20" value="' + item.qty + '" data-cart-edit="qty"' + (itemIsUnavailable ? ' disabled' : '') + '>' +
                        '</label>' +
                    '</div>' +
                    '<div class="cart-item-thumb' + (itemIsUnavailable ? ' cart-item-thumb-missing' : '') + '">' +
                        (itemIsUnavailable
                            ? '<span class="cart-item-thumb-missing-text">Missing</span>'
                            : '<img class="cart-item-thumb-image" src="' + escapeHtml(item.image) + '" alt="' + escapeHtml(item.name) + '">') +
                    '</div>' +
                    '<div class="cart-item-pricebox">' +
                        '<label class="cart-mini-field">' +
                            '<span>Days</span>' +
                            '<input type="number" min="1" max="14" value="' + item.days + '" data-cart-edit="days"' + (itemIsUnavailable ? ' disabled' : '') + '>' +
                        '</label>' +
                        '<p class="cart-item-price-label">' + (itemIsUnavailable ? 'Status:' : 'Price:') + '</p>' +
                        '<strong>' + (itemIsUnavailable ? 'Unavailable' : formatMoney(lineTotal)) + '</strong>' +
                    '</div>' +
                    '<button class="cart-remove-button" type="button" aria-label="Remove item" data-cart-remove>&#10005;</button>';

                panel.appendChild(card);
            });

            panel.querySelectorAll(".cart-item-thumb-image").forEach(function (imageNode) {
                imageNode.addEventListener("error", function () {
                    var thumbNode = imageNode.closest(".cart-item-thumb");
                    if (!thumbNode) {
                        return;
                    }

                    thumbNode.classList.add("cart-item-thumb-missing");
                    thumbNode.innerHTML = '<span class="cart-item-thumb-missing-text">Missing</span>';
                });
            });

            refreshTotals(items);
            syncCartCountBadges(items);
        }

        function refreshTotals(items) {
            var activeItems = Array.isArray(items) ? items : getCartItems();
            var subtotal = activeItems.reduce(function (sum, item) {
                if (isUnavailableCartItem(item)) {
                    return sum;
                }

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

            if (field === "days") {
                syncReturnDateTimeFromReceive(items);
                saveBookingSnapshot();
            }

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
            syncReturnDateTimeFromReceive(filteredItems);
            saveBookingSnapshot();
            renderCartItems();
        });

        panel.addEventListener("input", handleCartPanelInput);
        panel.addEventListener("change", handleCartPanelInput);

        [returnDateInput, returnTimeSelect].forEach(function (lockedControl) {
            if (!lockedControl) {
                return;
            }

            lockedControl.addEventListener("input", function () {
                syncReturnDateTimeFromReceive();
                saveBookingSnapshot();
            });

            lockedControl.addEventListener("change", function () {
                syncReturnDateTimeFromReceive();
                saveBookingSnapshot();
            });
        });

        if (bookingCard) {
            bookingCard.querySelectorAll("[data-booking-field], input[name='receivingMethod'], input[name='returningMethod']").forEach(function (control) {
                control.addEventListener("change", function () {
                    if (control === receiveDateInput || control === receiveTimeSelect) {
                        syncReturnDateTimeFromReceive();
                    }

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
                if (paymentSelect.value === "gcash") {
                    bookingNote.textContent = "Demo payment selected. No charges will be made in this frontend prototype.";
                    return;
                }

                bookingNote.textContent = "Demo flow only: no real booking or payment will be processed.";
            });
        }

        if (confirmButton && bookingNote) {
            confirmButton.addEventListener("click", function () {
                syncReturnDateTimeFromReceive();
                saveBookingSnapshot();

                var items = getCartItems();
                if (!items.length) {
                    bookingNote.textContent = "Add at least one item before confirming your demo booking.";
                    return;
                }

                var unavailableItems = getUnavailableCartItems(items);

                if (unavailableItems.length) {
                    openUnavailableModal(unavailableItems, function () {
                        var remainingItems = removeUnavailableCartItems();
                        syncReturnDateTimeFromReceive(remainingItems);
                        saveBookingSnapshot();
                        renderCartItems();

                        if (!remainingItems.length) {
                            bookingNote.textContent = "Out of stock items were removed from your cart.";
                            showCartToast("Unavailable items removed");
                            return;
                        }

                        bookingNote.textContent = "Out of stock items were removed. Booking request staged in demo mode.";
                        showCartToast("Unavailable items removed");
                    });

                    return;
                }

                var pendingOrder = createPendingOrderRecord(items);
                if (!pendingOrder) {
                    bookingNote.textContent = "Unable to create booking. Please check your cart items and try again.";
                    return;
                }

                if (confirmButton.disabled) {
                    return;
                }

                confirmButton.disabled = true;

                submitPendingOrder(pendingOrder)
                    .then(function (responsePayload) {
                        var savedOrder = normalizeStoredOrder(responsePayload.order || pendingOrder);
                        var existingOrders = getStoredOrders();

                        existingOrders.unshift(savedOrder);
                        saveStoredOrders(existingOrders);
                        renderOrderStatusList();

                        saveCartItems([]);
                        renderCartItems();

                        bookingNote.textContent = "Booking saved as Pending. Open Order Status to track your reservation.";
                        showCartToast("Booking saved as Pending");
                        setCartView("order-status");
                    })
                    .catch(function (error) {
                        bookingNote.textContent = error && error.message
                            ? String(error.message)
                            : "Unable to save booking right now.";
                    })
                    .finally(function () {
                        confirmButton.disabled = false;
                    });
            });
        }

        renderOrderStatusList();
        restoreBookingDefaults();
        syncReturnDateTimeFromReceive();
        updateDeliveryFields();
        saveBookingSnapshot();
        renderCartItems();
    }

    initializeCustomerMessageModal();
    initializeAdminNotificationsPage();
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