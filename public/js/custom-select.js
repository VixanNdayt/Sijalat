/**
 * Dropdown kustom yang dipakai bersama.
 *
 * Cara pakai: tambahkan atribut `data-custom-select` pada elemen <select>.
 * Select asli tetap berada di dalam form (tersembunyi secara visual) agar
 * nilai, validasi required, dan event `change` tetap berjalan seperti biasa.
 */
(function () {
    'use strict';

    if (typeof document === 'undefined') {
        return;
    }

    var uid = 0;
    var openMenu = null;

    function makeChevron() {
        var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('class', 'cs-chevron');
        svg.setAttribute('viewBox', '0 0 24 24');
        svg.setAttribute('fill', 'none');
        svg.setAttribute('stroke', 'currentColor');
        svg.setAttribute('stroke-width', '2.2');
        svg.setAttribute('stroke-linecap', 'round');
        svg.setAttribute('stroke-linejoin', 'round');
        svg.setAttribute('aria-hidden', 'true');

        var polyline = document.createElementNS('http://www.w3.org/2000/svg', 'polyline');
        polyline.setAttribute('points', '6 9 12 15 18 9');
        svg.appendChild(polyline);

        return svg;
    }

    function close(instance) {
        if (!instance) {
            return;
        }

        instance.menu.hidden = true;
        instance.trigger.setAttribute('aria-expanded', 'false');
        instance.wrapper.classList.remove('is-open');

        if (openMenu === instance) {
            openMenu = null;
        }
    }

    function closeOpenMenu(except) {
        if (openMenu && openMenu !== except) {
            close(openMenu);
        }
    }

    function positionMenu(instance) {
        var trigger = instance.trigger;
        var menu = instance.menu;
        var rect = trigger.getBoundingClientRect();
        var viewportHeight = window.innerHeight || document.documentElement.clientHeight;
        var viewportWidth = window.innerWidth || document.documentElement.clientWidth;
        var gap = 6;
        var spaceBelow = viewportHeight - rect.bottom - gap;
        var spaceAbove = rect.top - gap;
        var desired = Math.min(320, Math.max(160, menu.scrollHeight || 220));
        var openUp = spaceBelow < Math.min(desired, 200) && spaceAbove > spaceBelow;
        var available = openUp ? spaceAbove : spaceBelow;
        var maxHeight = Math.max(140, Math.min(desired, available));

        var width = rect.width;

        menu.style.width = width + 'px';
        menu.style.maxHeight = maxHeight + 'px';

        var left = Math.min(Math.max(8, rect.left), viewportWidth - width - 8);

        menu.style.left = left + 'px';
        menu.style.top = (openUp ? Math.max(8, rect.top - gap - maxHeight) : rect.bottom + gap) + 'px';
    }

    function show(instance) {
        closeOpenMenu(instance);

        instance.menu.hidden = false;
        instance.trigger.setAttribute('aria-expanded', 'true');
        instance.wrapper.classList.add('is-open');
        openMenu = instance;

        positionMenu(instance);

        var active = instance.menu.querySelector('.cs-option[aria-selected="true"]:not(.is-disabled)')
            || instance.menu.querySelector('.cs-option:not(.is-disabled)');

        if (active) {
            active.classList.add('is-active');
            active.scrollIntoView({ block: 'nearest' });
        }
    }

    function toggle(instance) {
        if (instance.menu.hidden) {
            show(instance);
        } else {
            close(instance);
        }
    }

    function sync(instance) {
        var select = instance.select;
        var selected = select.options[select.selectedIndex];
        var text = selected ? selected.textContent.trim() : '';
        var isPlaceholder = !selected || selected.disabled || select.value === '';

        instance.label.textContent = text || instance.placeholder;
        instance.trigger.classList.toggle('is-placeholder', isPlaceholder);
        instance.trigger.disabled = select.disabled;

        Array.prototype.forEach.call(instance.menu.children, function (option) {
            var value = option.getAttribute('data-value');
            var selectedOption = value === select.value;
            option.setAttribute('aria-selected', selectedOption ? 'true' : 'false');
        });

        if (select.value !== '') {
            instance.wrapper.classList.remove('is-invalid');
        }
    }

    function selectOption(instance, option) {
        if (option.classList.contains('is-disabled')) {
            return;
        }

        var value = option.getAttribute('data-value');

        if (instance.select.value !== value) {
            instance.select.value = value;
            instance.select.dispatchEvent(new Event('change', { bubbles: true }));
        }

        sync(instance);
        close(instance);
        instance.trigger.focus();
    }

    function setActive(instance, next) {
        var options = Array.prototype.slice.call(
            instance.menu.querySelectorAll('.cs-option:not(.is-disabled)')
        );

        if (!options.length) {
            return;
        }

        var current = instance.menu.querySelector('.cs-option.is-active');
        var index = options.indexOf(current);

        if (index === -1) {
            index = options.findIndex(function (option) {
                return option.getAttribute('aria-selected') === 'true';
            });
        }

        index = Math.max(0, Math.min(options.length - 1, index + next));

        options.forEach(function (option) {
            option.classList.toggle('is-active', option === options[index]);
        });

        options[index].scrollIntoView({ block: 'nearest' });
    }

    function moveToEdge(instance, last) {
        var options = Array.prototype.slice.call(
            instance.menu.querySelectorAll('.cs-option:not(.is-disabled)')
        );

        if (!options.length) {
            return;
        }

        var target = last ? options[options.length - 1] : options[0];

        options.forEach(function (option) {
            option.classList.toggle('is-active', option === target);
        });

        target.scrollIntoView({ block: 'nearest' });
    }

    function enhance(select) {
        if (select.dataset.customSelectReady === 'true' || select.multiple || select.size > 1) {
            return;
        }

        select.dataset.customSelectReady = 'true';

        var wrapper = document.createElement('div');
        wrapper.className = 'cs-select';
        if (select.classList.contains('date-picker')) {
            wrapper.classList.add('cs-select-compact');
        }

        select.parentNode.insertBefore(wrapper, select);
        wrapper.appendChild(select);
        select.classList.add('cs-native');
        select.setAttribute('tabindex', '-1');
        select.setAttribute('aria-hidden', 'true');

        var trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'cs-trigger';
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');

        if (select.id) {
            trigger.id = select.id + 'Trigger';
        }

        var label = document.createElement('span');
        label.className = 'cs-label';
        trigger.appendChild(label);
        trigger.appendChild(makeChevron());

        var menu = document.createElement('ul');
        menu.className = 'cs-menu';
        menu.hidden = true;
        menu.setAttribute('role', 'listbox');
        menu.id = 'cs-menu-' + (++uid);

        if (select.getAttribute('aria-label')) {
            trigger.setAttribute('aria-label', select.getAttribute('aria-label'));
        }

        var placeholder = '';
        var options = Array.prototype.slice.call(select.options);

        options.forEach(function (nativeOption, index) {
            var option = document.createElement('li');
            option.className = 'cs-option';
            option.setAttribute('role', 'option');
            option.setAttribute('data-value', nativeOption.value);
            option.setAttribute('aria-selected', nativeOption.selected ? 'true' : 'false');
            option.textContent = nativeOption.textContent.trim();

            if (nativeOption.disabled) {
                option.classList.add('is-disabled');
                option.setAttribute('aria-disabled', 'true');
            }

            if (index === 0 && nativeOption.disabled && nativeOption.value === '') {
                placeholder = nativeOption.textContent.trim();
            }

            option.addEventListener('click', function () {
                selectOption(instance, option);
            });

            option.addEventListener('mouseenter', function () {
                Array.prototype.forEach.call(menu.children, function (item) {
                    item.classList.toggle('is-active', item === option);
                });
            });

            menu.appendChild(option);
        });

        wrapper.appendChild(trigger);
        document.body.appendChild(menu);

        var instance = {
            select: select,
            wrapper: wrapper,
            trigger: trigger,
            label: label,
            menu: menu,
            placeholder: placeholder || 'Pilih opsi',
        };

        trigger.setAttribute('aria-controls', menu.id);

        trigger.addEventListener('click', function () {
            if (select.disabled) {
                return;
            }
            toggle(instance);
        });

        trigger.addEventListener('keydown', function (event) {
            var key = event.key;

            if (key === 'ArrowDown' || key === 'ArrowUp') {
                event.preventDefault();
                if (menu.hidden) {
                    show(instance);
                } else {
                    setActive(instance, key === 'ArrowDown' ? 1 : -1);
                }
                return;
            }

            if (key === 'Home' && !menu.hidden) {
                event.preventDefault();
                moveToEdge(instance, false);
                return;
            }

            if (key === 'End' && !menu.hidden) {
                event.preventDefault();
                moveToEdge(instance, true);
                return;
            }

            if ((key === 'Enter' || key === ' ') && !menu.hidden) {
                event.preventDefault();
                var active = menu.querySelector('.cs-option.is-active');
                if (active) {
                    selectOption(instance, active);
                }
                return;
            }

            if (key === 'Escape' && !menu.hidden) {
                event.preventDefault();
                close(instance);
                return;
            }

            if (key === 'Tab') {
                close(instance);
            }
        });

        select.addEventListener('change', function () {
            sync(instance);
        });

        // Label <label for="..."> mengarah ke select asli; alihkan fokus ke trigger.
        select.addEventListener('focus', function () {
            trigger.focus();
        });

        if (select.form) {
            select.form.addEventListener('reset', function () {
                window.setTimeout(function () {
                    sync(instance);
                }, 0);
            });

            select.form.addEventListener('submit', function (event) {
                sync(instance);

                if (select.required && !select.value) {
                    event.preventDefault();
                    wrapper.classList.add('is-invalid');
                    show(instance);
                    trigger.focus();
                }
            });
        }

        sync(instance);
    }

    function boot(root) {
        Array.prototype.forEach.call(
            (root || document).querySelectorAll('select[data-custom-select]'),
            enhance
        );
    }

    document.addEventListener('mousedown', function (event) {
        if (!openMenu) {
            return;
        }

        var item = event.target;

        if (
            item !== openMenu.trigger &&
            !openMenu.trigger.contains(item) &&
            !openMenu.menu.contains(item)
        ) {
            close(openMenu);
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && openMenu) {
            var instance = openMenu;
            close(instance);
            instance.trigger.focus();
        }
    });

    window.addEventListener('resize', function () {
        if (openMenu) {
            positionMenu(openMenu);
        }
    });

    window.addEventListener('scroll', function () {
        if (openMenu) {
            close(openMenu);
        }
    }, true);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            boot(document);
        });
    } else {
        boot(document);
    }

    window.CustomSelect = {
        refresh: function () {
            boot(document);
        },
    };
})();
