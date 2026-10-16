/*
 * Adaptadores da interface FERSOFT para identificadores históricos das views.
 * Mantemos a API pública dos campos e tabelas, sem carregar o runtime Metronic.
 */
(function (window, document, $) {
    'use strict';

    if (!$) {
        return;
    }

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
        } else {
            callback();
        }
    }

    window.KTUtil = {
        ready: ready,
        getById: function (id) { return document.getElementById(id); },
        isRTL: function () { return document.documentElement.dir === 'rtl'; },
        scrollTop: function () { window.scrollTo({ top: 0, behavior: 'smooth' }); }
    };

    function FerImageInput(id) {
        var element = typeof id === 'string' ? document.getElementById(id) : id;
        if (!element) {
            return;
        }
        if (element.ferImageInput) {
            return element.ferImageInput;
        }

        var input = element.querySelector('input[type="file"]');
        var preview = element.querySelector('.image-input-wrapper');
        var removeField = element.querySelector('input[name="profile_avatar_remove"]');
        var original = preview ? preview.style.backgroundImage : '';
        var imageUrl;
        var listeners = {};
        var instance = this;

        function emit(type) {
            (listeners[type] || []).forEach(function (listener) { listener(instance); });
        }

        if (input) {
            input.addEventListener('change', function () {
                if (!input.files || !input.files[0]) {
                    return;
                }
                if (imageUrl) {
                    URL.revokeObjectURL(imageUrl);
                }
                imageUrl = URL.createObjectURL(input.files[0]);
                if (preview) {
                    preview.style.backgroundImage = 'url("' + imageUrl + '")';
                }
                if (removeField) {
                    removeField.value = '';
                }
                element.classList.add('image-input-changed');
                emit('change');
            });
        }

        element.querySelectorAll('[data-action="cancel"], [data-action="remove"]').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                var removing = button.dataset.action === 'remove';
                if (input) {
                    input.value = '';
                }
                if (imageUrl) {
                    URL.revokeObjectURL(imageUrl);
                    imageUrl = null;
                }
                if (preview) {
                    preview.style.backgroundImage = removing ? '' : original;
                }
                if (removeField) {
                    removeField.value = removing ? '1' : '';
                }
                element.classList.remove('image-input-changed');
                emit(removing ? 'remove' : 'cancel');
            });
        });

        this.on = function (type, listener) {
            (listeners[type] = listeners[type] || []).push(listener);
            return this;
        };
        element.ferImageInput = this;
    }

    function FerWizard(element, options) {
        element = typeof element === 'string' ? document.getElementById(element) : element;
        if (!element) {
            return;
        }
        if (element.ferWizard) {
            return element.ferWizard;
        }
        var steps = Array.prototype.slice.call(element.querySelectorAll('[data-wizard-type="step"]'));
        var contents = Array.prototype.slice.call(element.querySelectorAll('[data-wizard-type="step-content"]'));
        var count = Math.min(steps.length, contents.length);
        var current = Math.max(1, Math.min(count, Number(options && options.startStep) || 1));
        var stopped = false;
        var listeners = {};
        var wizard = this;

        if (!count) {
            return;
        }

        function emit(type) {
            (listeners[type] || []).forEach(function (callback) { callback(wizard); });
        }

        function render() {
            steps.forEach(function (step, index) {
                var active = index + 1 === current;
                step.dataset.wizardState = active ? 'current' : index + 1 < current ? 'done' : 'pending';
                step.setAttribute('aria-selected', active ? 'true' : 'false');
                step.tabIndex = active ? 0 : -1;
            });
            contents.forEach(function (content, index) {
                content.hidden = index + 1 !== current;
            });
            element.dataset.wizardState = current === count ? 'last' : current === 1 ? 'first' : 'between';
        }

        this.getStep = function () { return current; };
        this.getNewStep = function () { return this.newStep || current; };
        this.stop = function () { stopped = true; };
        this.on = function (type, callback) {
            (listeners[type] = listeners[type] || []).push(callback);
            return this;
        };
        this.goTo = function (target) {
            target = Number(target);
            if (!Number.isInteger(target) || target < 1 || target > count || target === current) {
                return this;
            }
            this.newStep = target;
            stopped = false;
            if (target > current) {
                emit('beforeNext');
            }
            emit('change');
            if (!stopped) {
                current = target;
                render();
                emit('changed');
            }
            return this;
        };
        this.goNext = function () { return this.goTo(current + 1); };
        this.goPrev = function () { return this.goTo(current - 1); };

        steps.slice(0, count).forEach(function (step, index) {
            step.setAttribute('role', 'tab');
            step.tabIndex = -1;
            if (element.dataset.wizardClickable === 'true' || (options && options.clickableSteps)) {
                step.addEventListener('click', function () { wizard.goTo(index + 1); });
                step.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        wizard.goTo(index + 1);
                    }
                });
            }
        });
        element.querySelectorAll('[data-wizard-type="action-next"], [data-wizard-type="action-prev"]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (button.dataset.wizardType === 'action-next') wizard.goNext();
                else wizard.goPrev();
            });
        });
        element.ferWizard = this;
        render();
    }

    // Os nomes KT nos aliases abaixo são contratos históricos de views existentes.
    window.KTImageInput = FerImageInput;
    window.KTWizard = FerWizard;

    // Apenas as tabelas que pedem explicitamente a API legada recebem o adapter.
    // As tabelas Blade comuns mantêm suas linhas, links, formulários e paginação do servidor.
    $.fn.KTDatatable = function (options) {
        var first;
        this.each(function () {
            var table = $(this);
            var wrapper = table.closest('.datatable');
            if (!wrapper.length) {
                table.wrap('<div class="datatable"></div>');
                wrapper = table.parent();
            }
            if (wrapper.data('KTDatatable')) {
                first = first || wrapper.data('KTDatatable');
                return;
            }
            var rows = table.find('tbody > tr');
            var instance = {
                options: options || {},
                search: function (term) {
                    var query = String(term || '').toLocaleLowerCase();
                    rows.each(function () {
                        this.hidden = query !== '' && this.textContent.toLocaleLowerCase().indexOf(query) === -1;
                    });
                    return instance;
                },
                destroy: function () {
                    rows.prop('hidden', false);
                    wrapper.removeData('KTDatatable');
                }
            };
            wrapper.data('KTDatatable', instance);
            first = first || instance;
        });
        return first || this;
    };

    function initializeSelect2(context) {
        if (!$.fn.select2) {
            return;
        }
        var root = $(context);
        var selectors = [
            '.select2-custom', '.kt-select2-general',
            '[id^="kt_select2_"]'
        ].join(', ');
        root.find(selectors).addBack(selectors).filter('select').each(function () {
            var select = $(this);
            if (select.data('select2')) {
                return;
            }
            var options = { width: '100%', placeholder: select.attr('data-placeholder') || 'Selecione' };
            if (select.hasClass('select2-custom')) {
                options.tags = true;
            }
            if (select.closest('.modal').length) {
                options.dropdownParent = select.closest('.modal');
            }
            select.select2(options);
        });
    }

    // O layout histórico chama KTSelect2.init() antes de configurar os calendários.
    window.KTSelect2 = {
        init: function () { initializeSelect2(document); }
    };

    window.FerRuntime = {
        initSelect2: initializeSelect2,
        init: function (context) {
            var root = context || document;
            root.querySelectorAll('.image-input[id]').forEach(function (element) { new FerImageInput(element); });
            root.querySelectorAll('.wizard[id]').forEach(function (element) { new FerWizard(element, { startStep: 1 }); });
            initializeSelect2(root);
            if ($.fn.timepicker) {
                $(root).find('[id^="kt_timepicker_"]').each(function () {
                    if (!$(this).data('timepicker')) $(this).timepicker();
                });
            }
        }
    };
    ready(function () { window.FerRuntime.init(document); });
    $(document).on('shown.bs.modal', '.modal', function () { window.FerRuntime.init(this); });
})(window, document, window.jQuery);
