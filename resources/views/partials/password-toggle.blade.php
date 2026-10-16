{{-- Adds a show/hide (eye) button to every <input type="password"> on the page.
     Opt out with data-no-toggle. The button is a positioned sibling, so it
     doesn't alter the input's own layout. --}}
<script>
(function () {
    var EYE = '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>';
    var EYE_OFF = '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><path d="M1 1l22 22"/></svg>';
    var items = [];

    function place(it) {
        var i = it.input, b = it.btn;
        if (!i.isConnected) { b.remove(); return; }
        var visible = i.offsetWidth > 0 && i.offsetHeight > 0;
        b.style.display = visible ? 'flex' : 'none';
        if (!visible) return;
        b.style.left = (i.offsetLeft + i.offsetWidth - 34) + 'px';
        b.style.top  = (i.offsetTop + (i.offsetHeight - 28) / 2) + 'px';
    }

    function enhance(input) {
        if (input.dataset.toggleReady || input.hasAttribute('data-no-toggle')) return;
        input.dataset.toggleReady = '1';

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.tabIndex = -1;
        btn.setAttribute('aria-label', 'Show password');
        btn.innerHTML = EYE;
        btn.style.cssText = 'position:absolute;z-index:5;width:28px;height:28px;align-items:center;justify-content:center;'
            + 'background:none;border:0;padding:0;margin:0;cursor:pointer;color:#9ca3af;display:none;';
        btn.addEventListener('mousedown', function (e) { e.preventDefault(); }); // keep focus/caret in the field
        btn.addEventListener('click', function () {
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.innerHTML = show ? EYE_OFF : EYE;
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        });

        input.insertAdjacentElement('afterend', btn);
        var pr = parseFloat(getComputedStyle(input).paddingRight) || 0;
        if (pr < 38) input.style.paddingRight = '38px';

        var it = { input: input, btn: btn };
        items.push(it);
        place(it);
        if (window.ResizeObserver) new ResizeObserver(function () { place(it); }).observe(input);
    }

    function scan() { document.querySelectorAll('input[type="password"]').forEach(enhance); items.forEach(place); }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', scan); else scan();
    window.addEventListener('resize', function () { items.forEach(place); });
    setInterval(scan, 700); // modals/popups reveal fields later; also catches layout shifts
})();
</script>
