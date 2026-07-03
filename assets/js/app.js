// MonsterList — light progressive enhancement. All pages work without JS.
document.addEventListener('DOMContentLoaded', function () {
  // Confirm dangerous actions
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      if (!window.confirm(el.getAttribute('data-confirm'))) e.preventDefault();
    });
  });

  // AI fill on the new-listing form
  var aiBtn = document.getElementById('ai-fill-btn');
  if (aiBtn) {
    var aiUrl = document.getElementById('ai-url');
    var aiStatus = document.getElementById('ai-status');
    var form = document.querySelector('form.card');

    var setField = function (name, value) {
      if (value === undefined || value === null || value === '') return;
      var el = form.querySelector('[name="' + name + '"]');
      if (!el) return;
      el.value = value;
      el.style.background = '#fef9e7'; // gentle highlight: review AI-filled values
    };

    var runFill = function () {
      var url = aiUrl.value.trim();
      if (!url) { aiStatus.textContent = 'Enter your website address first.'; return; }
      aiBtn.disabled = true;
      aiStatus.textContent = 'Reading your website… this usually takes 15–60 seconds.';

      var data = new FormData();
      data.append('_csrf', aiUrl.getAttribute('data-csrf'));
      data.append('url', url);

      fetch('/account/listings/autofill', { method: 'POST', body: data })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          aiBtn.disabled = false;
          if (!res.ok) { aiStatus.textContent = res.error || 'Something went wrong.'; return; }
          var f = res.fields;
          setField('name', f.name);
          setField('tagline', f.tagline);
          setField('description', f.description);
          setField('category_id', f.category_id);
          setField('country', f.country);
          setField('region', f.region);
          setField('city', f.city);
          setField('address', f.address);
          setField('phone', f.phone);
          setField('email', f.email);
          setField('website', f.website);
          setField('founded', f.founded);
          if (f.social) {
            Object.keys(f.social).forEach(function (net) { setField('social_' + net, f.social[net]); });
          }
          aiStatus.textContent = '✓ Done! Highlighted fields were filled by AI — please review them, then submit.';
        })
        .catch(function () {
          aiBtn.disabled = false;
          aiStatus.textContent = 'Network error — please try again.';
        });
    };
    aiBtn.addEventListener('click', runFill);
    aiUrl.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); runFill(); } });
  }

  // Client-side filter box on /browse lists
  document.querySelectorAll('[data-filter]').forEach(function (input) {
    var target = document.querySelector(input.getAttribute('data-filter'));
    if (!target) return;
    input.addEventListener('input', function () {
      var q = input.value.toLowerCase();
      target.querySelectorAll('a').forEach(function (a) {
        a.style.display = a.textContent.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
      });
    });
  });
});
