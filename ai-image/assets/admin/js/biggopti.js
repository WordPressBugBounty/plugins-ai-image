jQuery(document).ready(function ($) {

    /* ===================================
       Start Admin API BIGGOPTI
       =================================== */
    $(document).on('click', '.ai-image-biggopti.is-dismissible .bdt-admin-biggopti-api-biggopti-dismiss', function (e) {
        e.preventDefault();
        var $this = $(this).closest('.ai-image-biggopti');
        var displayId = $this.data('display-id') || $this.attr('data-display-id') || '';
        if (!displayId && $this.attr('id')) {
            var id = $this.attr('id');
            if (id.indexOf('bdt-admin-biggopti-api-biggopti-') === 0) {
                displayId = id.replace('bdt-admin-biggopti-api-biggopti-', '');
            }
        }
        var $time = $this.data('dismissible-time') || $this.attr('data-dismissible-time') || $this.attr('dismissible-time') || 604800;
        var $meta = $this.data('dismissible-meta') || $this.attr('data-dismissible-meta') || $this.attr('dismissible-meta') || 'transient';
        var cfg = window.AIImageBiggoptiConfig || window.AIImageAdminApiBiggoptiConfig || {};
        var ajaxUrl = cfg.ajaxurl || (typeof ajaxurl !== 'undefined' ? ajaxurl : '');
        if (!ajaxUrl || !displayId || !cfg.nonce) return;
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'bdt_admin_api_biggopti_dismiss',
                display_id: displayId,
                id: $this.attr('id'),
                meta: $meta,
                time: $time,
                _wpnonce: cfg.nonce,
            }
        }).done(function () {
            $('.ai-image-biggopti').filter(function () { return ($(this).data('display-id') || $(this).attr('data-display-id') || '') === displayId; }).fadeTo(50, 0, function () { $(this).slideUp(50, function () { $(this).remove(); }); });
        }).fail(function () {
            $('.ai-image-biggopti').filter(function () { return ($(this).data('display-id') || $(this).attr('data-display-id') || '') === displayId; }).fadeTo(50, 0, function () { $(this).slideUp(50, function () { $(this).remove(); }); });
        });
    });

    function initAPIBiggoptiCountdown() {
        jQuery('.bdt-biggopti-countdown').each(function () {
            var $countdown = jQuery(this);
            var $timer = $countdown.find('.countdown-timer');
            var endDate = $countdown.data('end-date');
            var timezone = $countdown.data('timezone');

            if (!endDate || !$timer.length) {
                return;
            }

            function updateCountdown() {
                var endTime = new Date(endDate + ' ' + timezone).getTime();
                var now = new Date().getTime();
                var distance = endTime - now;

                if (distance < 0) {
                    $countdown.hide();
                    return;
                }

                var days = Math.floor(distance / (1000 * 60 * 60 * 24));
                var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                var seconds = Math.floor((distance % (1000 * 60)) / 1000);

                days = days < 10 ? '0' + days : days;
                hours = hours < 10 ? '0' + hours : hours;
                minutes = minutes < 10 ? '0' + minutes : minutes;
                seconds = seconds < 10 ? '0' + seconds : seconds;

                var countdownText = '';
                if (days > 0) {
                    countdownText += '<div class="countdown-item"><span class="number">' + days + '</span><span class="label">days</span></div><span class="separator"></span>';
                }
                countdownText += '<div class="countdown-item"><span class="number">' + hours + '</span><span class="label">hrs</span></div><span class="separator"></span>';
                countdownText += '<div class="countdown-item"><span class="number">' + minutes + '</span><span class="label">min</span></div><span class="separator"></span>';
                countdownText += '<div class="countdown-item"><span class="number">' + seconds + '</span><span class="label">sec</span></div>';

                $timer.html(countdownText);
            }

            updateCountdown();
            setInterval(updateCountdown, 1000);
        });
    }

    initAPIBiggoptiCountdown();

    jQuery(document).on('DOMNodeInserted', '.bdt-biggopti-countdown', function () {
        initAPIBiggoptiCountdown();
    });

    var BIGGOPTI_API_URL = 'https://api.sigmative.io/prod/store/api/biggopti/api-data-records';
    var BIGGOPTI_CFG = window.AIImageBiggoptiConfig || window.AIImageAdminApiBiggoptiConfig || {};
    var BIGGOPTI_ASSETS_URL = BIGGOPTI_CFG.assetsUrl || '';

    var skippedDueToProTargetedAndPro = false;

    function isAiImagePromoItemValid(item) {
        if (!item || item.product !== 'ai-image' || item.type !== 'adminDashboard') return false;
        var targets = item.client_targets || [];
        var isPro = (BIGGOPTI_CFG && BIGGOPTI_CFG.isPro) || false;
        if (targets.includes('pro_targeted') && isPro) {
            skippedDueToProTargetedAndPro = true;
            return false;
        }
        var showForFree = targets.includes('free');
        var showForPro = targets.includes('pro') && isPro;
        if (!showForFree && !showForPro) return false;
        if (!item.is_enabled) return false;
        if (!item.end_date) return false;
        var tz = item.timezone || 'UTC';
        var endStr = (item.end_date + '').replace(' ', 'T') + (tz === 'UTC' ? 'Z' : '');
        var endDate = new Date(endStr);
        if (isNaN(endDate.getTime())) return false;
        return Date.now() <= endDate.getTime();
    }

    function renderBiggoptiHTML(item) {
        if (!isItemVisibleForCurrentSector(item)) return '';
        var esc = function (s) { return (s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); };
        var bg = (item.background_color || '') + (item.image ? ' background-image:url(' + esc(item.image) + ')' : '');
        var wrapperClass = 'bdt-biggopti-wrapper' + (item.image ? ' has-background-image' : '');
        var title = esc(item.title || '');
        var content = esc(item.content || '');
        var logoUrl = item.logo || '';
        var link = item.link || '';
        var btnText = item.button_text || 'Read More';
        var showCountdown = item.show_countdown && item.end_date;
        var endDate = item.end_date || '';
        var tz = item.timezone || 'UTC';
        var displayId = item.display_id || item.id || 'default';
        var biggoptiId = 'bdt-admin-biggopti-api-biggopti-' + displayId;

        var countdownHtml = showCountdown ? '<div class="bdt-biggopti-countdown" data-end-date="' + esc(endDate) + '" data-timezone="' + esc(tz) + '"><div class="countdown-timer">Loading...</div></div>' : '';
        var btnHtml = link ? '<div class="bdt-biggopti-btn"><a href="' + esc(link) + '" target="_blank"><div class="nm-biggopti-btn">' + esc(btnText) + ' <span class="dashicons dashicons-arrow-right-alt"></span></div></a></div>' : '';
        var logoHtml = logoUrl ? '<div class="bdt-biggopti-logo-wrapper"><img width="100" src="' + esc(logoUrl) + '" alt="Logo"></div>' : '';

        var inner = '<div class="' + wrapperClass + '"' + (bg ? ' style="' + esc(bg) + '"' : '') + '>' +
            '<div class="bdt-api-biggopti-content">' +
            '<div class="bdt-ai-image-biggopti-logo-wrapper"><img height="auto" width="40" src="' + BIGGOPTI_ASSETS_URL + 'imgs/logo.png" alt="AI Image Logo"></div>' +
            '<div class="bdt-biggopti-content">' +
            '<div class="bdt-biggopti-content-inner">' + logoHtml +
            '<div class="bdt-biggopti-title-description">' +
            (title ? '<h2 class="bdt-biggopti-title">' + title + '</h2>' : '') +
            (content ? '<div class="bdt-biggopti-html-content">' + content + '</div>' : '') +
            '</div></div>' +
            '<div class="bdt-biggopti-content-right">' + countdownHtml + btnHtml + '</div>' +
            '</div></div></div>';

        var endTs = endDate ? Math.max((new Date(endDate.replace(' ', 'T') + (tz === 'UTC' ? 'Z' : ''))).getTime() - Date.now(), 0) : 604800;
        var classes = 'ai-image-biggopti biggopti biggopti-info is-dismissible';
        var attrs = 'id="' + biggoptiId + '"';
        attrs += ' data-display-id="' + esc(displayId) + '" data-dismissible-meta="transient" data-dismissible-time="' + endTs + '"';
        var dismissBtn = '<button type="button" class="bdt-admin-biggopti-api-biggopti-dismiss dashicons dashicons-dismiss"><span class="screen-reader-text">Dismiss this biggopti.</span></button>';
        return '<div class="' + classes + '" ' + attrs + '>' + inner + dismissBtn + '</div>';
    }

    function renderFeedHTML(item) {
        var esc = function (s) {
            return (s || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        };

        var imageUrl = item.feed_image || '';
        var link = item.link || '#';
        var displayId = item.display_id || item.id || 'default';
        var feedId = 'bdt-admin-api-feed-' + displayId;

        if (!imageUrl) return '';

        return '\n            <div id="' + esc(feedId) + '" class="ai-image-feed">\n                <a href="' + esc(link) + '" target="_blank" rel="noopener noreferrer">\n                    <img src="' + esc(imageUrl) + '" alt="" style="max-width:100%; height:auto;">\n                </a>\n            </div>\n        ';
    }

    function isExcludedUrl() {
        var url = window.location.href || '';
        var patterns = ['plugin-install.php', 'theme-install.php', 'action=upload-plugin', 'action=upload-theme'];
        for (var i = 0; i < patterns.length; i++) {
            if (url.indexOf(patterns[i]) !== -1) return true;
        }
        return false;
    }

    function getCurrentVisibilitySectors() {
        var phpSector = (BIGGOPTI_CFG && BIGGOPTI_CFG.currentSector) || '';
        if (phpSector) {
            return [phpSector];
        }
        var path = (window.location.pathname || '').toLowerCase();
        var search = (window.location.search || '');
        var pageRaw = (search.match(/[?&]page=([^&]+)/i) || [])[1] || '';
        var page = '';
        try {
            page = pageRaw ? (decodeURIComponent(pageRaw.replace(/\+/g, ' ')) || pageRaw) : '';
        } catch (e) {
            page = pageRaw || '';
        }
        var sectors = [];
        var pathBase = path.split('?')[0];
        var isWpAdmin = path.indexOf('wp-admin') !== -1;
        if (isWpAdmin && (pathBase.indexOf('index.php') !== -1 || /\/wp-admin\/?$/.test(pathBase))) {
            sectors.push('wp_dashboard');
        }
        if (page && (page === 'ai-image-settings' || page.toLowerCase().indexOf('ai-image') !== -1)) {
            sectors.push('plugin_dashboard');
        }
        if (path.indexOf('themes.php') !== -1) {
            sectors.push('themes_page');
        }
        if (path.indexOf('options-') !== -1) {
            sectors.push('settings_page');
        }
        if (path.indexOf('profile.php') !== -1 || path.indexOf('user-edit.php') !== -1 || path.indexOf('user-new.php') !== -1) {
            sectors.push('user_page');
        }
        if (path.indexOf('plugins.php') !== -1 || path.indexOf('plugin-install.php') !== -1 || (path.indexOf('admin.php') !== -1 && page)) {
            sectors.push('plugin_pages');
        }
        if (path.indexOf('tools.php') !== -1) {
            sectors.push('tools_page');
        }
        return sectors;
    }

    function isItemVisibleForCurrentSector(item) {
        var sectors = item.visibility_sectors;
        if (!sectors || !Array.isArray(sectors) || sectors.length === 0) return true;
        var current = getCurrentVisibilitySectors();
        for (var i = 0; i < current.length; i++) {
            if (sectors.indexOf(current[i]) !== -1) return true;
        }
        return false;
    }

    var PROMO_SECTORS = ['wp_dashboard', 'plugin_dashboard', 'themes_page', 'settings_page', 'user_page', 'plugin_pages', 'tools_page'];

    function isCurrentSectorAllowedForPromo() {
        var current = getCurrentVisibilitySectors();
        for (var i = 0; i < current.length; i++) {
            if (PROMO_SECTORS.indexOf(current[i]) !== -1) return true;
        }
        return current.length === 0;
    }

    function injectBiggoptiesFromData(data) {
        var list = data && data['ai-image'];
        if (!Array.isArray(list)) return;
        var dismissed = (BIGGOPTI_CFG && BIGGOPTI_CFG.dismissedDisplayIds) || [];
        var valid = [];
        var validForDashboard = [];
        var seen = {};
        for (var i = 0; i < list.length; i++) {
            if (!isAiImagePromoItemValid(list[i])) continue;
            var did = list[i].display_id || list[i].id || 'default-' + i;
            if (seen[did]) continue;
            seen[did] = true;
            validForDashboard.push(list[i]);
            if (dismissed.indexOf(did) === -1) valid.push(list[i]);
        }
        if (valid.length === 0 && validForDashboard.length === 0) return;

        var $target = $('#wpbody-content .wrap').first();
        if (!$target.length) $target = $('.wrap').first();
        if (!$target.length) $target = $('#wpbody-content');

        var html = '';
        for (var j = 0; j < valid.length; j++) {
            var displayId = valid[j].display_id || valid[j].id || 'default-' + j;
            var classPattern = 'bdt-admin-biggopti-api-biggopti-' + displayId;
            if ($('[id="' + classPattern + '"]').length) continue;
            html += renderBiggoptiHTML(valid[j]);
        }
        if (!html && validForDashboard.length === 0) return;

        if (html) {
            var $markup = $(html);
            if ($target.children('hr.wp-header-end').length) {
                $target.children('hr.wp-header-end').first().after($markup);
            } else if ($target.children('h1').length) {
                $target.children('h1').first().after($markup);
            } else {
                $target.prepend($markup);
            }
        }

        initAPIBiggoptiCountdown();
    }

    function injectFeedsFromData(data) {
        var list = data && data['ultimate-store-kit'];
        if (!Array.isArray(list) || !list.length) return;

        var $dashboard = $('#bdt-dashboard-overview .inside');
        if (!$dashboard.length) $dashboard = $('#bdt-dashboard-overview');
        if (!$dashboard.length) return;

        var html = '';

        for (var i = 0; i < list.length; i++) {
            var did = list[i].display_id || list[i].id || 'default-' + i;
            if ($('#bdt-admin-api-feed-' + did).length) continue;

            html += renderFeedHTML(list[i]);
        }

        if (html) {
            $dashboard.prepend($(html));
        }
    }

    var FALLBACK = { sub_title: 'Go Pro', link: 'https://bdthemes.com/deals/?utm_source=WordPress_org&utm_medium=bfcm_cta&utm_campaign=ai_image' };

    function getFirstValidPromo(data) {
        var list = data && data['ai-image'];
        if (!Array.isArray(list)) return null;
        for (var i = 0; i < list.length; i++) {
            if (isAiImagePromoItemValid(list[i]) && list[i].link) {
                var t = list[i].sub_title;
                return { sub_title: t, link: list[i].link };
            }
        }
        return null;
    }

    function injectPromotionMenu(promo) {
        var isPro = (BIGGOPTI_CFG && BIGGOPTI_CFG.isPro) || false;
        if (isPro && !promo) return;
        var adminSubmenu = document.querySelector('#menu-posts-ai_image .wp-submenu');
        if (!adminSubmenu || adminSubmenu.querySelector('.bdt-promo-menu-item')) return;
        var p = promo || FALLBACK;
        var href = (p.link || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
        var text = (p.sub_title).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        var html = '<li class="bdt-promo-menu-item"><a href="' + href + '" target="_blank" style="color: #f24101; font-weight: 600;" rel="noopener noreferrer">' + text + '</a></li>';
        adminSubmenu.insertAdjacentHTML('beforeend', html);
    }

    function processApiData(data) {
        window.bdtPromoData = data;

        if (!isExcludedUrl()) {
            injectBiggoptiesFromData(data);
        }

        injectFeedsFromData(data);

        skippedDueToProTargetedAndPro = false;
        var promo = getFirstValidPromo(data);
        if (isCurrentSectorAllowedForPromo() && (promo || !skippedDueToProTargetedAndPro)) {
            injectPromotionMenu(promo);
        }
    }

    function fetchAiImagePromoData() {
        fetch(BIGGOPTI_API_URL).then(function (r) { return r.json(); }).then(processApiData).catch(function () {
            if (isCurrentSectorAllowedForPromo() && !(BIGGOPTI_CFG && BIGGOPTI_CFG.isPro)) {
                injectPromotionMenu(FALLBACK);
            }
        });
    }

    $(window).on('load', function () {
        setTimeout(function () {
            fetchAiImagePromoData();
            setTimeout(fetchAiImagePromoData, 500);
        }, 400);
    });

    /* ===================================
       END Admin API BIGGOPTI / Submenu Promotion
       =================================== */
});
