</main>

<footer class="footer">
    <div class="container">
        <p>&copy; <?= date('Y') ?> <span data-i18n="footer_title">ICT Ticketing System</span> &mdash; <span data-i18n="footer_subtitle">Institutional ICT Support &amp; Issue Tracking System</span></p>
    </div>
</footer>

<script>
function _ict_init() {
    function getControlLabel(control) {
        var label = control.closest('label');
        if (label) {
            var text = label.textContent || '';
            return text.replace(/\s+/g, ' ').trim();
        }

        if (control.id) {
            var explicitLabel = document.querySelector('label[for="' + CSS.escape(control.id) + '"]');
            if (explicitLabel) {
                var explicitText = explicitLabel.textContent || '';
                return explicitText.replace(/\s+/g, ' ').trim();
            }
        }

        var name = (control.name || control.id || '').replace(/[_-]+/g, ' ').trim();
        return name ? name.charAt(0).toUpperCase() + name.slice(1) : '';
    }

    function addPlaceholderToControl(control) {
        if (control.disabled || control.readOnly || control.hasAttribute('data-no-placeholder')) return;

        var tag = control.tagName.toLowerCase();
        if (tag === 'input') {
            var type = (control.type || '').toLowerCase();
            if (['hidden', 'checkbox', 'radio', 'file', 'submit', 'reset', 'button', 'image', 'range', 'color'].indexOf(type) !== -1) {
                return;
            }
            if (!control.placeholder) {
                var inputLabel = getControlLabel(control);
                if (inputLabel) {
                    control.placeholder = inputLabel;
                }
            }
            return;
        }

        if (tag === 'textarea') {
            if (!control.placeholder) {
                var textareaLabel = getControlLabel(control);
                if (textareaLabel) {
                    control.placeholder = textareaLabel;
                }
            }
            return;
        }

        if (tag === 'select') {
            if (control.multiple) return;
            if (control.querySelector('option[value=""]')) return;

            var selectLabel = getControlLabel(control);
            if (!selectLabel) return;

            var placeholderOption = document.createElement('option');
            placeholderOption.value = '';
            placeholderOption.textContent = 'Select ' + selectLabel;
            placeholderOption.disabled = true;
            placeholderOption.hidden = true;

            if (control.required || !control.value) {
                placeholderOption.selected = true;
            }

            control.insertBefore(placeholderOption, control.firstChild);
        }
    }

    document.querySelectorAll('form input, form textarea, form select').forEach(addPlaceholderToControl);

    var menuToggle = document.getElementById('menuToggle');
    var adminShell = document.querySelector('.admin-shell');

    if (menuToggle && adminShell) {
        menuToggle.addEventListener('click', function () {
            var expanded = menuToggle.getAttribute('aria-expanded') === 'true';
            menuToggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            adminShell.classList.toggle('sidebar-open');
        });
    }

    document.querySelectorAll('[data-admin-menu-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!adminShell) return;
            adminShell.classList.toggle('sidebar-open');
            var expanded = btn.getAttribute('aria-expanded') === 'true';
            btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
        });
    });

    document.addEventListener('click', function (e) {
        if (!adminShell) return;
        if (!adminShell.classList.contains('sidebar-open')) return;
        var sidebar = adminShell.querySelector('.admin-sidebar');
        if (!sidebar) return;
        if (!sidebar.contains(e.target) && !(menuToggle && menuToggle.contains(e.target))) {
            adminShell.classList.remove('sidebar-open');
        }
    }, true);

    function initFileDropZone(zone) {
        var input = zone.querySelector('.file-drop-input');
        var content = zone.querySelector('.file-drop-content');
        var preview = zone.querySelector('.file-drop-preview');
        var previewImg = preview ? preview.querySelector('img') : null;
        var previewName = preview ? preview.querySelector('.file-drop-name') : null;
        var removeBtn = preview ? preview.querySelector('.file-drop-remove') : null;
        if (!input || !content || !preview) return;

        function showFile(file) {
            if (!file) return;
            zone.classList.add('has-file');
            if (previewName) {
                previewName.textContent = file.name;
            }
            if (previewImg && file.type && file.type.indexOf('image/') === 0) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    previewImg.src = e.target.result;
                };
                reader.readAsDataURL(file);
            } else if (previewImg) {
                previewImg.src = '';
            }
        }

        function resetZone() {
            zone.classList.remove('has-file', 'drag-over');
            if (previewImg) previewImg.src = '';
            if (previewName) previewName.textContent = '';
            input.value = '';
        }

        input.addEventListener('change', function () {
            if (input.files && input.files.length > 0) {
                showFile(input.files[0]);
            } else {
                resetZone();
            }
        });

        if (removeBtn) {
            removeBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                resetZone();
            });
        }

        zone.addEventListener('dragenter', function (e) {
            e.preventDefault();
            e.stopPropagation();
            zone.classList.add('drag-over');
        });

        zone.addEventListener('dragover', function (e) {
            e.preventDefault();
            e.stopPropagation();
            zone.classList.add('drag-over');
        });

        zone.addEventListener('dragleave', function (e) {
            e.preventDefault();
            e.stopPropagation();
            zone.classList.remove('drag-over');
        });

        zone.addEventListener('drop', function (e) {
            e.preventDefault();
            e.stopPropagation();
            zone.classList.remove('drag-over');
            var files = e.dataTransfer && e.dataTransfer.files;
            if (files && files.length > 0) {
                input.files = files;
                showFile(files[0]);
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });

        zone.addEventListener('click', function (e) {
            if (e.target === removeBtn || removeBtn && removeBtn.contains(e.target)) return;
            input.click();
        });
    }

    document.querySelectorAll('.file-drop-zone').forEach(initFileDropZone);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', _ict_init);
} else {
    _ict_init();
}
</script>

<script src="<?= $baseUrl ?>assets/js/animations.js"></script>

</body>
</html>
