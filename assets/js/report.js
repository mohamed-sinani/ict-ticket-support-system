// Multi-step reporting wizard.

document.addEventListener('DOMContentLoaded', function () {
    const wizardForm = document.getElementById('ticketWizard');
    if (!wizardForm) return;

    const steps = Array.from(wizardForm.querySelectorAll('.wizard-step'));
    const stepIndicators = Array.from(document.querySelectorAll('.stepper .step'));
    const totalSteps = steps.length;
    const submitBtn = document.getElementById('submitTicketBtn');
    const verifyBtn = document.getElementById('verifyEmployeeBtn');
    const categorySelect = document.getElementById('categoryId');
    const subcategorySelect = document.getElementById('subcategoryId');
    let ticketSubmitted = false;

    const localizedMessages = {
        en: {
            enter_employee_number: 'Please enter an employee number.',
            verify_failed: 'Employee record not found.',
            submit_failed: 'Could not submit ticket. Please try again.',
            employee_preview: 'Employee: {employee} | Email: {email} | Department: {department}',
            employee_welcome: 'Welcome, {employee}',
            processing: 'Processing...',
            verifying: 'Verifying...',
            loading: 'Loading...',
            select_subcategory: 'Select Subcategory',
            ticket_success_title: 'Ticket submitted successfully',
            ticket_success: 'Your tracking code is {code}. We sent it to your account email: {email}.',
            email_failed: 'Your tracking code is {code}. We could not send the email to {email}, so please keep this code.'
        },
        sw: {
            enter_employee_number: 'Tafadhali ingiza nambari ya mfanyakazi.',
            verify_failed: 'Taarifa za mfanyakazi hazijapatikana.',
            submit_failed: 'Tiketi haikuweza kutumwa. Tafadhali jaribu tena.',
            employee_preview: 'Mfanyakazi: {employee} | Barua pepe: {email} | Idara: {department}',
            employee_welcome: 'Karibu, {employee}',
            processing: 'Inasindika...',
            verifying: 'Inahakiki...',
            loading: 'Inapakia...',
            select_subcategory: 'Chagua Kipengele',
            ticket_success_title: 'Tiketi imetumwa kikamilifu',
            ticket_success: 'Msimbo wako wa ufuatiliaji ni {code}. Tumeutuma kwenye barua pepe ya akaunti yako: {email}.',
            email_failed: 'Msimbo wako wa ufuatiliaji ni {code}. Barua pepe haikuweza kutumwa kwenda {email}, tafadhali hifadhi msimbo huu.'
        }
    };

    function activeLanguage() {
        if (window.appI18n && typeof window.appI18n.getLanguage === 'function') {
            return window.appI18n.getLanguage();
        }
        return localStorage.getItem('app_lang') || 'en';
    }

    function msg(key, replacements) {
        const pack = localizedMessages[activeLanguage()] || localizedMessages.en;
        let text = pack[key] || localizedMessages.en[key] || '';
        Object.keys(replacements || {}).forEach(function (token) {
            text = text.replace(`{${token}}`, String(replacements[token]));
        });
        return text;
    }

    function showStep(stepNumber) {
        const idx = stepNumber - 1;
        steps.forEach((step, i) => step.classList.toggle('active', i === idx));
        stepIndicators.forEach((ind, i) => ind.classList.toggle('active', i === idx));
        wizardForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function activeStepIsValid() {
        const activeStep = wizardForm.querySelector('.wizard-step.active');
        const controls = Array.from(activeStep.querySelectorAll('input, select, textarea'));
        return controls.every(function (control) {
            return control.reportValidity();
        });
    }

    function setBusy(button, busy, label) {
        if (!button) return;
        button.disabled = busy;
        if (busy) {
            button.dataset.originalText = button.textContent;
            button.textContent = label;
        } else if (button.dataset.originalText) {
            button.textContent = button.dataset.originalText;
        }
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, function (char) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[char];
        });
    }

    async function verifyEmployee() {
        const empNum = document.getElementById('employeeNumber').value.trim();
        const errorBox = document.getElementById('verifyError');
        const previewBox = document.getElementById('employeeData');
        const welcomeBox = document.getElementById('employeeWelcome');
        const employeeId = document.getElementById('employeeId');

        if (!empNum) {
            errorBox.textContent = msg('enter_employee_number');
            errorBox.classList.remove('hidden');
            previewBox.classList.add('hidden');
            return;
        }

        setBusy(verifyBtn, true, msg('verifying'));
        const body = new FormData();
        body.append('employee_number', empNum);

        try {
            const response = await fetch('api/verify_employee.php', { method: 'POST', body });
            const data = await response.json();
            if (!data.success || !data.employee) {
                throw new Error(data.message || msg('verify_failed'));
            }

            employeeId.value = data.employee.id;
            const jobTitle = data.employee.job_title || '-';
            const department = data.employee.department_name || '-';
            previewBox.textContent = msg('employee_preview', {
                employee: data.employee.full_name,
                email: data.employee.email,
                department
            });
            if (welcomeBox) {
                welcomeBox.innerHTML = '<p>' + escapeHtml(msg('employee_welcome', { employee: data.employee.full_name })) + '</p>'
                    + '<span>' + escapeHtml(jobTitle) + ' | ' + escapeHtml(department) + '</span>';
                welcomeBox.classList.remove('hidden');
            }
            previewBox.classList.remove('hidden');
            errorBox.classList.add('hidden');
            showStep(2);
        } catch (error) {
            employeeId.value = '';
            if (welcomeBox) {
                welcomeBox.classList.add('hidden');
                welcomeBox.innerHTML = '';
            }
            errorBox.textContent = error.message || msg('verify_failed');
            errorBox.classList.remove('hidden');
            previewBox.classList.add('hidden');
        } finally {
            setBusy(verifyBtn, false);
        }
    }

    async function submitTicket() {
        const resultBox = document.getElementById('submitResult');
        if (ticketSubmitted) {
            showStep(5);
            return;
        }

        if (!wizardForm.checkValidity()) {
            const firstInvalid = wizardForm.querySelector(':invalid');
            const invalidStep = firstInvalid ? firstInvalid.closest('.wizard-step') : null;
            if (invalidStep) showStep(invalidStep.dataset.step);
            if (firstInvalid) firstInvalid.reportValidity();
            return;
        }

        setBusy(submitBtn, true, msg('processing'));
        resultBox.textContent = msg('processing');
        resultBox.classList.remove('hidden');

        try {
            const response = await fetch('api/submit_ticket.php', {
                method: 'POST',
                body: new FormData(wizardForm)
            });
            const data = await response.json();
            if (!data.success) {
                throw new Error(data.message || msg('submit_failed'));
            }

            const code = data.tracking_code || '';
            const email = data.email || '';
            const successText = data.email_status === 'Sent'
                ? msg('ticket_success', { code, email })
                : msg('email_failed', { code, email });
            resultBox.innerHTML = '<div class="submit-success-card">'
                + '<div class="submit-success-check" aria-hidden="true"><svg viewBox="0 0 52 52"><circle cx="26" cy="26" r="24"></circle><path d="M15 27.5l7 7L38 18"></path></svg></div>'
                + '<h3>' + escapeHtml(msg('ticket_success_title')) + '</h3>'
                + '<p>' + escapeHtml(successText) + '</p>'
                + '<div class="submit-success-code">' + escapeHtml(code) + '</div>'
                + '</div>';
            ticketSubmitted = true;
            showStep(5);
            document.querySelector('.wizard-wrap')?.classList.add('wizard-submitted');
            wizardForm.querySelectorAll('input, select, textarea, button').forEach(function (control) {
                if (control.id !== 'submitTicketBtn') control.disabled = true;
            });
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Submitted';
            }
        } catch (error) {
            resultBox.innerHTML = '<p class="alert alert-danger">' + (error.message || msg('submit_failed')) + '</p>';
        } finally {
            if (!ticketSubmitted) {
                setBusy(submitBtn, false);
            }
        }
    }

    async function loadSubcategories() {
        if (!categorySelect || !subcategorySelect) return;

        subcategorySelect.innerHTML = '<option value="">' + msg('loading') + '</option>';
        subcategorySelect.disabled = true;

        const body = new FormData();
        body.append('category_id', categorySelect.value);

        try {
            const response = await fetch('api/get_subcategories.php', { method: 'POST', body });
            const data = await response.json();
            const options = ['<option value="">' + msg('select_subcategory') + '</option>'];
            if (data.success && Array.isArray(data.subcategories)) {
                data.subcategories.forEach(function (subcategory) {
                    options.push('<option value="' + escapeHtml(subcategory.id) + '">' + escapeHtml(subcategory.name) + '</option>');
                });
            }
            subcategorySelect.innerHTML = options.join('');
        } catch (error) {
            subcategorySelect.innerHTML = '<option value="">' + msg('select_subcategory') + '</option>';
        } finally {
            subcategorySelect.disabled = false;
        }
    }

    wizardForm.addEventListener('click', function (event) {
        const el = event.target;
        if (el.matches('[data-next]')) {
            const next = parseInt(el.getAttribute('data-next'), 10);
            if (activeStepIsValid() && next >= 1 && next <= totalSteps) showStep(next);
        } else if (el.matches('[data-prev]')) {
            const prev = parseInt(el.getAttribute('data-prev'), 10);
            if (prev >= 1 && prev <= totalSteps) showStep(prev);
        } else if (el.id === 'verifyEmployeeBtn') {
            verifyEmployee();
        } else if (el.id === 'submitTicketBtn') {
            submitTicket();
        }
    });

    if (categorySelect) {
        categorySelect.addEventListener('change', loadSubcategories);
    }

    showStep(1);
});
