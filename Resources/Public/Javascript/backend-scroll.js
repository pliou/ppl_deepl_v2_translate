(function () {
  'use strict';

  const storageKey = 'pplDeeplV2BackendScroll';

  function readState() {
    try {
      return JSON.parse(window.sessionStorage.getItem(storageKey) || '{}');
    } catch (error) {
      return {};
    }
  }

  function escapeSelector(value) {
    if (window.CSS && typeof window.CSS.escape === 'function') {
      return window.CSS.escape(value);
    }

    return String(value).replace(/["\\#.;,[\]=:]/g, '\\$&');
  }

  function writeState(state) {
    try {
      window.sessionStorage.setItem(storageKey, JSON.stringify(state));
    } catch (error) {
      // Session storage can be unavailable in hardened browser contexts.
    }
  }

  document.addEventListener('submit', function (event) {
    const form = event.target;

    if (!form || !form.closest || !form.closest('.ppl-deepl')) {
      return;
    }

    const submitter = event.submitter || document.activeElement;
    const anchor = submitter && submitter.getAttribute
      ? submitter.getAttribute('data-scroll-anchor') || submitter.value || submitter.name || submitter.id || ''
      : '';
    const tab = form.querySelector('[name="config_tab"]');

    writeState({
      href: window.location.pathname + window.location.search,
      scrollY: window.scrollY || window.pageYOffset || 0,
      anchor: anchor,
      tab: tab ? tab.value : ''
    });
  }, true);

  document.addEventListener('click', function (event) {
    const link = event.target && event.target.closest
      ? event.target.closest('.ppl-deepl__tab')
      : null;

    if (!link) {
      return;
    }

    writeState({
      href: link.pathname + link.search,
      scrollY: 0,
      anchor: link.getAttribute('data-scroll-anchor') || ''
    });
  }, true);

  window.addEventListener('load', function () {
    const state = readState();
    const href = window.location.pathname + window.location.search;

    if (!state || state.href !== href) {
      return;
    }

    let target = null;
    if (state.anchor) {
      const escapedAnchor = escapeSelector(state.anchor);
      target = document.querySelector('[data-scroll-anchor="' + escapedAnchor + '"], [name="' + escapedAnchor + '"], #' + escapedAnchor);
    }

    if (target && target.scrollIntoView) {
      target.scrollIntoView({ block: 'center' });
      return;
    }

    if (typeof state.scrollY === 'number') {
      window.scrollTo(0, state.scrollY);
    }
  });
})();


(function () {
    'use strict';

    var key = 'ppl-deepl-v2-backend-scroll';

    function getScroller() {
        var candidates = [
            document.querySelector('.module-body'),
            document.querySelector('.t3js-module-body'),
            document.scrollingElement,
            document.documentElement,
            document.body
        ];

        for (var i = 0; i < candidates.length; i++) {
            var element = candidates[i];
            if (element && element.scrollHeight > element.clientHeight + 4) {
                return element;
            }
        }

        return document.scrollingElement || document.documentElement || document.body;
    }

    function safeSelector(value) {
        if (window.CSS && typeof window.CSS.escape === 'function') {
            return window.CSS.escape(value);
        }

        return String(value).replace(/[^a-zA-Z0-9_-]/g, '\$&');
    }

    function readPayload() {
        try {
            return JSON.parse(window.sessionStorage.getItem(key) || '{}');
        } catch (error) {
            return {};
        }
    }

    function writePayload(payload) {
        try {
            window.sessionStorage.setItem(key, JSON.stringify(payload));
        } catch (error) {
            // Ignore storage errors in restricted backend contexts.
        }
    }

    function remember(anchor) {
        var scroller = getScroller();
        var tab = document.querySelector('input[name="config_tab"]');

        writePayload({
            anchor: anchor || '',
            tab: tab ? tab.value : '',
            top: scroller ? scroller.scrollTop : window.pageYOffset || 0
        });
    }

    function restore() {
        var payload = readPayload();
        var scroller = getScroller();
        var target = null;

        if (payload.anchor) {
            target = document.querySelector('[data-scroll-anchor="' + safeSelector(payload.anchor) + '"]');
        }

        if (target && scroller) {
            var targetTop = target.getBoundingClientRect().top;
            var scrollerTop = scroller === document.body || scroller === document.documentElement
                ? window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0
                : scroller.scrollTop;
            var nextTop = Math.max(0, scrollerTop + targetTop - 24);

            if (scroller === document.body || scroller === document.documentElement) {
                window.scrollTo({ top: nextTop, behavior: 'auto' });
            } else {
                scroller.scrollTop = nextTop;
            }
            return;
        }

        if (payload.top && scroller) {
            scroller.scrollTop = payload.top;
        }
    }

    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-scroll-anchor], .ppl-deepl__tab');
        if (!trigger) {
            return;
        }

        remember(trigger.getAttribute('data-scroll-anchor') || trigger.getAttribute('href') || '');
    }, true);

    document.addEventListener('submit', function (event) {
        var submitter = event.submitter || document.activeElement;
        var anchor = submitter && submitter.getAttribute ? submitter.getAttribute('data-scroll-anchor') : '';
        remember(anchor || '');
    }, true);

    window.addEventListener('load', function () {
        window.setTimeout(restore, 0);
        window.setTimeout(restore, 80);
    });
})();

/* PPL shared backend scroll: start */
(function () {
    'use strict';

    var key = 'ppl-deepl-backend-scroll';

    function getScroller() {
        var candidates = [
            document.querySelector('.module-body'),
            document.querySelector('.t3js-module-body'),
            document.scrollingElement,
            document.documentElement,
            document.body
        ];

        for (var i = 0; i < candidates.length; i++) {
            var element = candidates[i];
            if (element && element.scrollHeight > element.clientHeight + 4) {
                return element;
            }
        }

        return document.scrollingElement || document.documentElement || document.body;
    }

    function safeSelector(value) {
        if (window.CSS && typeof window.CSS.escape === 'function') {
            return window.CSS.escape(value);
        }

        return String(value).replace(/[^a-zA-Z0-9_-]/g, '\$&');
    }

    function readPayload() {
        try {
            return JSON.parse(window.sessionStorage.getItem(key) || '{}');
        } catch (error) {
            return {};
        }
    }

    function writePayload(payload) {
        try {
            window.sessionStorage.setItem(key, JSON.stringify(payload));
        } catch (error) {
            // Ignore storage errors in restricted backend contexts.
        }
    }

    function remember(anchor) {
        var scroller = getScroller();
        var tab = document.querySelector('input[name="config_tab"]');

        writePayload({
            anchor: anchor || '',
            tab: tab ? tab.value : '',
            top: scroller ? scroller.scrollTop : window.pageYOffset || 0
        });
    }

    function scrollToPosition(scroller, top) {
        if (!scroller) {
            return;
        }

        if (scroller === document.body || scroller === document.documentElement || scroller === document.scrollingElement) {
            window.scrollTo({ top: top, behavior: 'auto' });
            return;
        }

        scroller.scrollTop = top;
    }

    function restore() {
        var payload = readPayload();
        var scroller = getScroller();
        var target = null;

        if (payload.anchor) {
            target = document.querySelector('[data-scroll-anchor="' + safeSelector(payload.anchor) + '"]');
        }

        if (target && scroller) {
            var targetTop = target.getBoundingClientRect().top;
            var scrollerTop = scroller === document.body || scroller === document.documentElement || scroller === document.scrollingElement
                ? window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0
                : scroller.scrollTop;
            scrollToPosition(scroller, Math.max(0, scrollerTop + targetTop - 24));
            return;
        }

        if (payload.top) {
            scrollToPosition(scroller, payload.top);
        }
    }

    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-scroll-anchor], .ppl-deepl__tab');
        if (!trigger) {
            return;
        }

        remember(trigger.getAttribute('data-scroll-anchor') || trigger.getAttribute('href') || '');
    }, true);

    document.addEventListener('submit', function (event) {
        var submitter = event.submitter || document.activeElement;
        var anchor = submitter && submitter.getAttribute ? submitter.getAttribute('data-scroll-anchor') : '';
        remember(anchor || '');
    }, true);

    window.addEventListener('load', function () {
        window.setTimeout(restore, 0);
        window.setTimeout(restore, 80);
    });
})();
/* PPL shared backend scroll: end */


/* PPL backend glossary and exclusive controls: start */
(function () {
    'use strict';

    function parseGlossaryOptions() {
        var element = document.getElementById('ppl-deepl-glossary-options');
        if (!element) {
            return {};
        }

        try {
            return JSON.parse(element.textContent || '{}');
        } catch (error) {
            return {};
        }
    }

    function normalizeGlossaryLanguage(language) {
        var normalizedLanguage = String(language || '').toUpperCase();

        if (normalizedLanguage === 'DE-DE') {
            return 'DE';
        }

        if (normalizedLanguage === 'EN-GB' || normalizedLanguage === 'EN-US') {
            return 'EN';
        }

        if (normalizedLanguage.indexOf('ES-') === 0) {
            return 'ES';
        }

        if (normalizedLanguage === 'PT-PT' || normalizedLanguage === 'PT-BR') {
            return 'PT';
        }

        if (normalizedLanguage === 'ZH-HANS' || normalizedLanguage === 'ZH-HANT') {
            return 'ZH';
        }

        return normalizedLanguage;
    }

    function isSameLanguageValue(sourceLanguage, targetLanguage) {
        return sourceLanguage !== ''
            && targetLanguage !== ''
            && normalizeGlossaryLanguage(sourceLanguage) === normalizeGlossaryLanguage(targetLanguage);
    }

    function updateTargetLanguageAvailability(source, target) {
        if (!source || !target) {
            return false;
        }

        var selectedValue = target.value;
        var fallbackValue = '';

        Array.prototype.forEach.call(target.options, function (option) {
            var blocked = isSameLanguageValue(source.value, option.value);
            option.disabled = blocked;
            option.hidden = blocked;
            if (blocked) {
                option.setAttribute('aria-disabled', 'true');
            } else {
                option.removeAttribute('aria-disabled');
                if (option.value !== '' && fallbackValue === '') {
                    fallbackValue = option.value;
                }
            }
        });

        if (isSameLanguageValue(source.value, selectedValue) && fallbackValue !== '') {
            target.value = fallbackValue;
            return true;
        }

        return false;
    }

    function updateGlossaryOptions(form, glossaryOptionsByCombination) {
        var source = form.querySelector('[data-role="deepl-source"]');
        var target = form.querySelector('[data-role="deepl-target"]');
        var glossary = form.querySelector('[data-role="deepl-glossary-select"]');
        var label = form.querySelector('[data-role="deepl-glossary-label"]');

        if (!source || !target || !glossary) {
            return;
        }

        updateTargetLanguageAvailability(source, target);

        var key = normalizeGlossaryLanguage(source.value) + ':' + normalizeGlossaryLanguage(target.value);
        var options = glossaryOptionsByCombination[key] || {};
        var selectedValue = glossary.value;
        var ids = Object.keys(options);

        glossary.innerHTML = '';

        var emptyOption = document.createElement('option');
        emptyOption.value = '';
        emptyOption.textContent = 'No glossary';
        glossary.appendChild(emptyOption);

        ids.forEach(function (id) {
            var option = document.createElement('option');
            option.value = id;
            option.textContent = options[id];
            if (id === selectedValue) {
                option.selected = true;
            }
            glossary.appendChild(option);
        });

        if (ids.length === 0 || !Object.prototype.hasOwnProperty.call(options, selectedValue)) {
            glossary.value = '';
        }

        glossary.disabled = ids.length === 0;

        if (label) {
            label.textContent = ids.length > 0 ? 'Choose an approved glossary.' : 'No glossary approved.';
        }
    }

    function updateExclusiveStyleTone(changed) {
        var writingStyle = document.querySelector('[data-role="deepl-writing-style"], #backend_writing_style');
        var tone = document.querySelector('[data-role="deepl-tone"], #backend_tone');

        if (!writingStyle || !tone) {
            return;
        }

        if (writingStyle.value !== '' && tone.value !== '') {
            if (changed === tone) {
                writingStyle.value = '';
            } else {
                tone.value = '';
            }
        }

        tone.disabled = writingStyle.value !== '';
        writingStyle.disabled = tone.value !== '';
    }

    document.addEventListener('DOMContentLoaded', function () {
        var glossaryOptionsByCombination = parseGlossaryOptions();

        document.querySelectorAll('#pplDeeplTextForm, #pplDeeplFileForm').forEach(function (form) {
            updateGlossaryOptions(form, glossaryOptionsByCombination);
            form.querySelectorAll('[data-role="deepl-source"], [data-role="deepl-target"]').forEach(function (select) {
                select.addEventListener('change', function () {
                    updateGlossaryOptions(form, glossaryOptionsByCombination);
                });
            });
        });

        var writingStyle = document.querySelector('[data-role="deepl-writing-style"], #backend_writing_style');
        var tone = document.querySelector('[data-role="deepl-tone"], #backend_tone');

        updateExclusiveStyleTone(null);

        if (writingStyle) {
            writingStyle.addEventListener('change', function () {
                updateExclusiveStyleTone(writingStyle);
            });
        }

        if (tone) {
            tone.addEventListener('change', function () {
                updateExclusiveStyleTone(tone);
            });
        }
    });
})();
/* PPL backend glossary and exclusive controls: end */
