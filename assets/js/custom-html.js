/*
 * Runs the scripts of the Zmove "HTML personnalisé" dashboard blocks.
 *
 * Dashboards insert widget markup through innerHTML, which never executes <script> tags, so the
 * widget renders them with an inert type and this recreates each one as soon as the block is in
 * the page. Scripts run one after the other in source order, an external script being awaited
 * before the next one, so an inline script can use a library loaded just above it.
 */
(function () {
    'use strict';

    var DEFERRED_SELECTOR = 'script[type="text/x-zmove-deferred"]';

    /*
     * Type given to a script once claimed, so the observer does not queue it twice while an
     * earlier script of its block is still loading.
     */
    var QUEUED_TYPE = 'text/x-zmove-queued';

    function findDeferredScripts(root) {
        if (root.matches && root.matches(DEFERRED_SELECTOR)) {
            return [root];
        }

        return Array.prototype.slice.call(root.querySelectorAll(DEFERRED_SELECTOR));
    }

    function recreate(inert) {
        var script = document.createElement('script');

        Array.prototype.forEach.call(inert.attributes, function (attribute) {
            if (attribute.name !== 'type' && attribute.name !== 'data-zmove-type') {
                script.setAttribute(attribute.name, attribute.value);
            }
        });

        if (inert.getAttribute('data-zmove-type')) {
            script.type = inert.getAttribute('data-zmove-type');
        }

        script.async = false;
        script.text = inert.text;

        return script;
    }

    function runInOrder(inertScripts) {
        inertScripts.forEach(function (inert) {
            inert.type = QUEUED_TYPE;
        });

        (function runNext() {
            var inert = inertScripts.shift();
            if (!inert) {
                return;
            }

            // The block may have been re-rendered meanwhile, dropping this script
            if (!inert.parentNode) {
                runNext();
                return;
            }

            var script = recreate(inert);

            // Only external scripts are awaited: inline classic scripts run synchronously when
            // inserted, and inline module scripts fire no load event, being deferred anyway.
            var isAwaited = script.hasAttribute('src');
            if (isAwaited) {
                script.addEventListener('load', runNext);
                script.addEventListener('error', runNext);
            }

            inert.parentNode.replaceChild(script, inert);

            if (!isAwaited) {
                runNext();
            }
        })();
    }

    function runDeferredScripts(root) {
        var inertScripts = findDeferredScripts(root);
        if (inertScripts.length) {
            runInOrder(inertScripts);
        }
    }

    new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            Array.prototype.forEach.call(mutation.addedNodes, function (node) {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    runDeferredScripts(node);
                }
            });
        });
    }).observe(document.documentElement, { childList: true, subtree: true });

    runDeferredScripts(document);
})();
