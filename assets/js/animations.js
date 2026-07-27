(function () {
    'use strict';

    function qs(sel, ctx) { return (ctx || document).querySelector(sel); }
    function qsa(sel, ctx) { return Array.from((ctx || document).querySelectorAll(sel)); }

    function onReady(fn) {
        if (document.readyState !== 'loading') fn();
        else document.addEventListener('DOMContentLoaded', fn);
    }

    function initPageEntrance() {
        var targets = qsa(
            '.stat, .panel-card, .dept-card, .chart-card, .table-wrap, ' +
            '.auth-card, .wizard-wrap, .track-card, .hero, ' +
            '.admin-topbar, .module-summary-item, .staff-workload-card'
        );
        targets.forEach(function (el, i) {
            el.style.opacity = '0';
            el.style.transform = 'translateY(24px)';
            el.style.transition = 'opacity 0.5s cubic-bezier(.4,0,.2,1), transform 0.5s cubic-bezier(.4,0,.2,1)';
            el.style.transitionDelay = Math.min(i * 60, 600) + 'ms';
        });

        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                targets.forEach(function (el) {
                    el.style.opacity = '';
                    el.style.transform = '';
                });
            });
        });
    }

    function initScrollReveal() {
        if (!('IntersectionObserver' in window)) return;

        var items = qsa(
            'tbody tr, .dept-card, .panel-card, .chart-card, ' +
            '.module-summary-item, .stat, .table-wrap'
        );

        items.forEach(function (el) {
            el.style.opacity = '0';
            el.style.transform = 'translateY(18px)';
            el.style.transition = 'opacity 0.45s ease, transform 0.45s ease';
        });

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });

        items.forEach(function (el) { observer.observe(el); });
    }

    function initCountUp() {
        var stats = qsa('.stat h3');
        if (!stats.length) return;

        function parseTarget(el) {
            var raw = el.textContent.trim().replace(/[^0-9.,]/g, '');
            if (!raw) return null;
            var num = parseFloat(raw.replace(/,/g, ''));
            return isNaN(num) ? null : { num: num, format: raw.indexOf(',') !== -1 };
        }

        function formatNum(n, useComma) {
            var s = Math.round(n).toString();
            if (!useComma) return s;
            return s.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        function animate(el) {
            var info = parseTarget(el);
            if (!info) return;
            var suffix = el.textContent.trim().replace(/[\d.,]/g, '').trim();
            var duration = 900;
            var start = performance.now();

            function tick(now) {
                var t = Math.min((now - start) / duration, 1);
                var ease = 1 - Math.pow(1 - t, 3);
                var current = info.num * ease;
                el.textContent = formatNum(current, info.format) + (suffix ? ' ' + suffix : '');
                if (t < 1) requestAnimationFrame(tick);
            }
            requestAnimationFrame(tick);
        }

        if (!('IntersectionObserver' in window)) {
            stats.forEach(animate);
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    animate(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        stats.forEach(function (el) { observer.observe(el); });
    }

    function initRipple() {
        var style = document.createElement('style');
        style.textContent =
            '.btn-ripple{position:absolute;border-radius:50%;background:rgba(255,255,255,0.45);' +
            'transform:scale(0);animation:rippleExpand .55s ease-out forwards;pointer-events:none}' +
            '@keyframes rippleExpand{to{transform:scale(3.5);opacity:0}}';
        document.head.appendChild(style);

        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.btn');
            if (!btn) return;
            var rect = btn.getBoundingClientRect();
            var size = Math.max(rect.width, rect.height) * 2;
            var ripple = document.createElement('span');
            ripple.className = 'btn-ripple';
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
            ripple.style.top = (e.clientY - rect.top - size / 2) + 'px';
            btn.appendChild(ripple);
            setTimeout(function () { ripple.remove(); }, 600);
        });
    }

    function initOTPInput() {
        var otpField = qs('input[name="otp_code"]');
        if (!otpField) return;

        otpField.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 6);
            if (this.value.length === 6) {
                var form = this.closest('form');
                if (form) {
                    this.style.transition = 'transform 0.2s ease, box-shadow 0.2s ease';
                    this.style.transform = 'scale(1.04)';
                    this.style.boxShadow = '0 0 0 4px rgba(34,197,94,0.25)';
                    setTimeout(function () {
                        otpField.style.transform = '';
                        otpField.style.boxShadow = '';
                        form.submit();
                    }, 350);
                }
            }
        });

        otpField.addEventListener('paste', function (e) {
            var text = (e.clipboardData || window.clipboardData).getData('text') || '';
            var digits = text.replace(/\D/g, '').slice(0, 6);
            if (digits.length === 6) {
                e.preventDefault();
                this.value = digits;
                this.dispatchEvent(new Event('input'));
            }
        });

        otpField.focus();
    }

    function initValidationShake() {
        var style = document.createElement('style');
        style.textContent =
            '@keyframes shakeError{0%,100%{transform:translateX(0)}15%,45%,75%{transform:translateX(-6px)}' +
            '30%,60%{transform:translateX(6px)}}.shake-error{animation:shakeError .45s ease}';
        document.head.appendChild(style);

        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (!form.querySelector('form')) return;
            setTimeout(function () {
                var invalid = form.querySelectorAll(':invalid:not(:focus)');
                invalid.forEach(function (el) {
                    el.classList.remove('shake-error');
                    void el.offsetWidth;
                    el.classList.add('shake-error');
                });
            }, 10);
        });

        document.addEventListener('click', function (e) {
            var btn = e.target.closest('button[type="submit"]');
            if (!btn) return;
            var form = btn.closest('form');
            if (!form) return;
            setTimeout(function () {
                var invalid = form.querySelectorAll(':invalid');
                invalid.forEach(function (el) {
                    el.classList.remove('shake-error');
                    void el.offsetWidth;
                    el.classList.add('shake-error');
                });
            }, 10);
        });
    }

    function initFocusGlow() {
        var style = document.createElement('style');
        style.textContent =
            'input:focus,textarea:focus,select:focus{box-shadow:0 0 0 3px rgba(37,99,235,0.15),' +
            '0 8px 20px rgba(37,99,235,0.1)!important;transform:translateY(-1px)!important}' +
            '.otp-glow{box-shadow:0 0 0 4px rgba(34,197,94,0.3)!important;transform:scale(1.02)!important}';
        document.head.appendChild(style);
    }

    function initFlashToast() {
        var toast = qs('[data-flash-toast]');
        if (!toast) return;
        toast.style.cursor = 'pointer';
        toast.addEventListener('click', function () {
            toast.classList.add('flash-toast-hide');
            setTimeout(function () { toast.remove(); }, 300);
        });
    }

    function initSidebarHover() {
        var links = qsa('.admin-module-nav a');
        links.forEach(function (link) {
            link.addEventListener('mouseenter', function () {
                var icon = this.querySelector('.admin-module-icon');
                if (icon) {
                    icon.style.transition = 'transform 0.25s ease';
                    icon.style.transform = 'scale(1.15)';
                }
            });
            link.addEventListener('mouseleave', function () {
                var icon = this.querySelector('.admin-module-icon');
                if (icon) icon.style.transform = '';
            });
        });
    }

    function initTableHighlight() {
        var style = document.createElement('style');
        style.textContent =
            '@keyframes rowFlash{0%{background:rgba(37,99,235,0.08)}100%{background:transparent}}' +
            '.row-flash{animation:rowFlash .6s ease}';
        document.head.appendChild(style);

        document.addEventListener('click', function (e) {
            var row = e.target.closest('tbody tr');
            if (!row) return;
            row.classList.remove('row-flash');
            void row.offsetWidth;
            row.classList.add('row-flash');
        });
    }

    function initSelectAnimation() {
        var style = document.createElement('style');
        style.textContent =
            'select:focus{transform:translateY(-1px) scale(1.01)!important;' +
            'box-shadow:0 0 0 3px rgba(37,99,235,0.12),0 6px 16px rgba(37,99,235,0.1)!important}';
        document.head.appendChild(style);
    }

    function initCardTilt() {
        var cards = qsa('.stat, .panel-card, .auth-card, .track-card');
        cards.forEach(function (card) {
            card.addEventListener('mousemove', function (e) {
                var rect = this.getBoundingClientRect();
                var x = e.clientX - rect.left;
                var y = e.clientY - rect.top;
                var centerX = rect.width / 2;
                var centerY = rect.height / 2;
                var rotateX = ((y - centerY) / centerY) * -3;
                var rotateY = ((x - centerX) / centerX) * 3;
                this.style.transform = 'perspective(600px) rotateX(' + rotateX + 'deg) rotateY(' + rotateY + 'deg) translateY(-3px)';
            });
            card.addEventListener('mouseleave', function () {
                this.style.transform = '';
            });
        });
    }

    function initLoadingState() {
        var style = document.createElement('style');
        style.textContent =
            '.btn-loading{position:relative;color:transparent!important;pointer-events:none}' +
            '.btn-loading::after{content:"";position:absolute;width:20px;height:20px;' +
            'border:3px solid rgba(255,255,255,0.4);border-top-color:#fff;border-radius:50%;' +
            'animation:btnSpin .6s linear infinite}' +
            '@keyframes btnSpin{to{transform:rotate(360deg)}}';
        document.head.appendChild(style);

        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (!form || form.tagName !== 'FORM') return;
            var btn = form.querySelector('button[type="submit"]:not(.btn-link)');
            if (!btn || btn.classList.contains('btn-loading')) return;
            setTimeout(function () {
                if (form.querySelectorAll(':invalid').length > 0) return;
                btn.classList.add('btn-loading');
                setTimeout(function () { btn.classList.remove('btn-loading'); }, 5000);
            }, 20);
        });
    }

    function initAlertFade() {
        var alerts = qsa('.alert-danger, .alert-success');
        alerts.forEach(function (alert) {
            alert.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            alert.style.opacity = '1';
            setTimeout(function () {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-8px)';
                setTimeout(function () { alert.style.display = 'none'; }, 400);
            }, 5000);
        });
    }

    function initDeptPulse() {
        var style = document.createElement('style');
        style.textContent =
            '@keyframes cardPulse{0%{transform:scale(1)}50%{transform:scale(0.96)}100%{transform:scale(1)}}' +
            '.card-pulse{animation:cardPulse .3s ease}';
        document.head.appendChild(style);

        document.addEventListener('click', function (e) {
            var delBtn = e.target.closest('.dept-delete-btn, .btn-danger');
            if (!delBtn) return;
            var card = delBtn.closest('.dept-card, .panel-card');
            if (card) {
                card.classList.remove('card-pulse');
                void card.offsetWidth;
                card.classList.add('card-pulse');
            }
        });
    }

    function initTrackSlideIn() {
        var style = document.createElement('style');
        style.textContent =
            '@keyframes slideInUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}' +
            '.track-slide-in{animation:slideInUp .45s ease-out}';
        document.head.appendChild(style);

        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (m) {
                m.addedNodes.forEach(function (node) {
                    if (node.nodeType === 1 && node.matches && node.matches('.track-card, .track-result, .track-timeline-table')) {
                        node.classList.add('track-slide-in');
                    }
                });
            });
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }

    function initNavActive() {
        var style = document.createElement('style');
        style.textContent =
            '.nav-active-pulse{position:relative}' +
            '.nav-active-pulse::before{content:"";position:absolute;bottom:-6px;left:50%;width:6px;height:6px;' +
            'background:var(--accent);border-radius:50%;transform:translateX(-50%);' +
            'animation:navPulse 2s ease-in-out infinite}' +
            '@keyframes navPulse{0%,100%{opacity:1;transform:translateX(-50%) scale(1)}50%{opacity:.5;transform:translateX(-50%) scale(.7)}}';
        document.head.appendChild(style);

        qsa('.admin-module-nav a.active, nav a.active').forEach(function (link) {
            link.classList.add('nav-active-pulse');
        });
    }

    function initFileDropBounce() {
        var style = document.createElement('style');
        style.textContent =
            '@keyframes dropBounce{0%{transform:scale(1)}30%{transform:scale(1.03)}60%{transform:scale(.98)}100%{transform:scale(1)}}' +
            '.drop-bounce{animation:dropBounce .4s ease}';
        document.head.appendChild(style);

        document.addEventListener('dragenter', function (e) {
            var zone = e.target.closest('.file-drop-zone');
            if (zone) {
                zone.classList.remove('drop-bounce');
                void zone.offsetWidth;
                zone.classList.add('drop-bounce');
            }
        });
    }

    function initDrawerAnimation() {
        var style = document.createElement('style');
        style.textContent =
            '.evidence-drawer{transition:opacity .3s ease}' +
            '.evidence-drawer-panel{transition:transform .35s cubic-bezier(.4,0,.2,1),opacity .3s ease}' +
            '.evidence-drawer[hidden] .evidence-drawer-panel{transform:translateX(100%);opacity:0}';
        document.head.appendChild(style);
    }

    function initHamburgerAnim() {
        var toggle = qs('#menuToggle');
        if (!toggle) return;

        var style = document.createElement('style');
        style.textContent =
            '#menuToggle.active span:nth-child(1){transform:rotate(45deg) translate(5px,5px)}' +
            '#menuToggle.active span:nth-child(2){opacity:0;transform:scaleX(0)}' +
            '#menuToggle.active span:nth-child(3){transform:rotate(-45deg) translate(5px,-5px)}';
        document.head.appendChild(style);

        toggle.addEventListener('click', function () {
            this.classList.toggle('active');
        });
    }

    function initSmoothScroll() {
        document.addEventListener('click', function (e) {
            var link = e.target.closest('a[href^="#"]');
            if (!link) return;
            var target = document.querySelector(link.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    }

    function initSettingsFeedback() {
        var forms = qsa('.settings-category-grid form, .admin-content form');
        forms.forEach(function (form) {
            form.addEventListener('submit', function () {
                var btn = form.querySelector('button[type="submit"]');
                if (btn && !btn.classList.contains('btn-loading')) {
                    btn.style.transition = 'background 0.3s ease, transform 0.3s ease';
                    btn.style.background = 'var(--success)';
                    btn.style.transform = 'scale(1.02)';
                    setTimeout(function () {
                        btn.style.background = '';
                        btn.style.transform = '';
                    }, 1500);
                }
            });
        });
    }

    function initSubcatSlide() {
        var style = document.createElement('style');
        style.textContent =
            '@keyframes subcatIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}' +
            '.subcat-animate{animation:subcatIn .3s ease-out}';
        document.head.appendChild(style);

        document.addEventListener('change', function (e) {
            if (e.target.matches && e.target.matches('select[name="subcategory_id"], select[name="category_id"]')) {
                var subcat = document.querySelector('select[name="subcategory_id"]');
                if (subcat) {
                    subcat.classList.remove('subcat-animate');
                    void subcat.offsetWidth;
                    subcat.classList.add('subcat-animate');
                }
            }
        });
    }

    function initBrandFloat() {
        var brand = qs('.brand svg');
        if (!brand) return;
        var style = document.createElement('style');
        style.textContent =
            '@keyframes iconFloat{0%,100%{transform:translateY(0)}50%{transform:translateY(-3px)}}' +
            '.brand svg{animation:iconFloat 3s ease-in-out infinite}';
        document.head.appendChild(style);
    }

    function initStatAccents() {
        var stats = qsa('.admin-content .stat');
        var accents = ['#2563eb', '#f59e0b', '#22c55e', '#8b5cf6', '#ef4444', '#06b6d4'];
        stats.forEach(function (stat, i) {
            var color = accents[i % accents.length];
            stat.style.borderLeft = '4px solid ' + color;
            var h3 = stat.querySelector('h3');
            if (h3) h3.style.color = color;
            var after = document.createElement('style');
            after.textContent = '.admin-content .stat:nth-child(' + (i + 1) + ')::after{background:' + color + '}';
            document.head.appendChild(after);
        });
    }

    onReady(function () {
        initPageEntrance();
        initScrollReveal();
        initCountUp();
        initRipple();
        initOTPInput();
        initValidationShake();
        initFocusGlow();
        initFlashToast();
        initSidebarHover();
        initTableHighlight();
        initSelectAnimation();
        initCardTilt();
        initLoadingState();
        initAlertFade();
        initDeptPulse();
        initTrackSlideIn();
        initNavActive();
        initFileDropBounce();
        initDrawerAnimation();
        initHamburgerAnim();
        initSmoothScroll();
        initSettingsFeedback();
        initSubcatSlide();
        initBrandFloat();
        initStatAccents();
    });
})();
