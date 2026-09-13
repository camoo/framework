(function () {
    'use strict';

    function copyText(text, button) {
        var done = function () {
            var original = button.innerHTML;
            button.textContent = 'Copied';
            window.setTimeout(function () {
                button.innerHTML = original;
            }, 1400);
        };

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(done);
            return;
        }

        var input = document.createElement('textarea');
        input.value = text;
        input.style.position = 'fixed';
        input.style.opacity = '0';
        document.body.appendChild(input);
        input.focus();
        input.select();
        document.execCommand('copy');
        document.body.removeChild(input);
        done();
    }

    function addIcon(button, type) {
        var icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        icon.setAttribute('aria-hidden', 'true');
        icon.setAttribute('viewBox', '0 0 24 24');
        icon.setAttribute('width', '16');
        icon.setAttribute('height', '16');
        icon.style.verticalAlign = 'text-bottom';
        icon.style.marginRight = '5px';

        var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('fill', 'currentColor');
        path.setAttribute('d', type === 'codex'
            ? 'M12 2.2c1.7 0 3.1 1.1 3.7 2.6 1.6-.3 3.2.4 4 1.8.9 1.5.6 3.2-.5 4.4 1.1 1.2 1.4 2.9.5 4.4-.8 1.4-2.4 2.1-4 1.8-.6 1.5-2 2.6-3.7 2.6s-3.1-1.1-3.7-2.6c-1.6.3-3.2-.4-4-1.8-.9-1.5-.6-3.2.5-4.4-1.1-1.2-1.4-2.9-.5-4.4.8-1.4 2.4-2.1 4-1.8C8.9 3.3 10.3 2.2 12 2.2zm0 4.1a5.7 5.7 0 1 0 0 11.4 5.7 5.7 0 0 0 0-11.4z'
            : 'M12 1.5l1.8 6.7 6.7 1.8-6.7 1.8-1.8 6.7-1.8-6.7-6.7-1.8 6.7-1.8L12 1.5zm7.1 13.4l.8 2.8 2.6.8-2.6.7-.8 2.9-.8-2.9-2.6-.7 2.6-.8.8-2.8z');
        icon.appendChild(path);
        button.appendChild(icon);
    }

    function createButton(label, prompt, iconType) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'rightButton camoo-copy-button';
        addIcon(button, iconType);
        button.appendChild(document.createTextNode(label));
        button.title = 'Copy exception details for ' + label.replace('Copy for ', '');
        button.style.marginLeft = '8px';
        button.addEventListener('click', function () {
            var exception = document.getElementById('plain-exception');
            var details = exception ? exception.textContent.trim() : document.title;
            copyText(prompt + '\n\nURL: ' + window.location.href + '\n\n' + details, button);
        });
        return button;
    }

    function addCopyActions() {
        var copyButton = document.getElementById('copy-button');
        if (!copyButton || !copyButton.parentNode) {
            return;
        }

        copyButton.parentNode.insertBefore(
            createButton('Copy for Codex', 'Please diagnose and fix this error in the current codebase.', 'codex'),
            copyButton.nextSibling
        );
        copyButton.parentNode.insertBefore(
            createButton('Copy for Antigravity', 'Please diagnose and fix this error in the current codebase using Antigravity.', 'antigravity'),
            copyButton.nextSibling
        );
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', addCopyActions);
    } else {
        addCopyActions();
    }
}());
