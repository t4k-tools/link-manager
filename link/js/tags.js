// tags.js | Aggiunge un tag suggerito al campo "tags" al clic (separati da virgola).
// Vanilla JS, non sostitutivo della normalizzazione lato server.
(function () {
    'use strict';
    var input = document.getElementById('tags');
    if (!input) {
        return;
    }
    document.querySelectorAll('.tag-suggest').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var tag = (this.dataset.tag || '').trim();
            if (tag === '') {
                return;
            }
            var parts = input.value.split(',')
                .map(function (s) { return s.trim(); })
                .filter(function (s) { return s !== ''; });
            var exists = parts.some(function (p) { return p.toLowerCase() === tag.toLowerCase(); });
            if (!exists) {
                parts.push(tag);
            }
            input.value = parts.join(', ');
            input.focus();
        });
    });
})();
