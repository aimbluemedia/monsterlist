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
    var aiCard = document.getElementById('ai-fill-card');
    var form = document.getElementById('listing-form') || document.querySelector('form.card');

    // Hide the long form until there is something in it worth reviewing. Done
    // here rather than in CSS so that a visitor without JS — who could never
    // reveal it again — still gets the whole form.
    var manualWrap = document.getElementById('ai-manual-wrap');
    var revealForm = function () {
      form.hidden = false;
      if (manualWrap) manualWrap.hidden = true;
    };
    if (aiCard && aiCard.getAttribute('data-collapse') === '1') {
      form.hidden = true;
      if (manualWrap) manualWrap.hidden = false;
      var manualLink = document.getElementById('ai-manual');
      if (manualLink) {
        manualLink.addEventListener('click', function (e) {
          e.preventDefault();
          revealForm();
          form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
      }
    }

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
          if (!res.ok) {
            // Show the form so a failed read is a detour, not a dead end.
            revealForm();
            aiStatus.textContent = (res.error || 'Something went wrong.') + ' Fill the form in below instead.';
            return;
          }
          var f = res.fields;
          setField('name', f.name);
          setField('tagline', f.tagline);
          setField('description', f.description);
          setField('category_id', f.category_id);
          setField('business_type', f.business_type);
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
          revealForm();
          aiStatus.textContent = '✓ Done! Highlighted fields were filled by AI — please review them, then submit.';
          form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        })
        .catch(function () {
          aiBtn.disabled = false;
          revealForm();
          aiStatus.textContent = 'Network error — please try again, or fill the form in below.';
        });
    };
    aiBtn.addEventListener('click', runFill);
    aiUrl.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); runFill(); } });
  }

  // Wizard step 1: cap how many service bubbles can be picked. The server caps
  // it too — this just stops the member choosing ten and losing three silently.
  var bubbleWrap = document.querySelector('.wz-bubbles');
  if (bubbleWrap) {
    var max = parseInt(bubbleWrap.getAttribute('data-max'), 10) || 7;
    var boxes = Array.prototype.slice.call(bubbleWrap.querySelectorAll('input[type=checkbox]'));
    var counter = document.getElementById('wz-count');

    var sync = function () {
      var picked = boxes.filter(function (b) { return b.checked; }).length;
      var full = picked >= max;
      boxes.forEach(function (b) {
        b.disabled = full && !b.checked;
        b.parentNode.classList.toggle('wz-full', b.disabled);
      });
      if (counter) {
        counter.textContent = picked + ' of ' + max + ' selected' +
          (full ? ' — deselect one to swap it out.' : '');
      }
    };
    boxes.forEach(function (b) { b.addEventListener('change', sync); });
    sync();
  }

  // Homepage graphic: count the numbers up once, when it scrolls into view.
  // The final value is already in the markup, so no-JS and reduced-motion
  // visitors simply see it sitting there.
  var stage = document.getElementById('ml-stage');
  var slowMo = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (stage && !slowMo && window.IntersectionObserver) {
    var targets = stage.querySelectorAll('[data-count]');

    var runCount = function () {
      targets.forEach(function (el) {
        var end = parseInt(el.getAttribute('data-count'), 10);
        var suffix = el.getAttribute('data-suffix') || '';
        if (isNaN(end)) return;
        var dur = 1100 + Math.random() * 500;
        var t0 = null;
        var tick = function (now) {
          if (t0 === null) t0 = now;
          var p = Math.min((now - t0) / dur, 1);
          var eased = 1 - Math.pow(1 - p, 3);           // ease-out cubic
          el.textContent = Math.round(end * eased).toLocaleString() + suffix;
          if (p < 1) requestAnimationFrame(tick);
        };
        el.textContent = '0' + suffix;
        requestAnimationFrame(tick);
      });
    };

    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        io.disconnect();
        runCount();
      });
    }, { threshold: 0.25 });
    io.observe(stage);
  }

  // Promotion-engine example. Pressing run steps through the four things the
  // engine really does to a link — read, write, file, publish — and the card
  // fills in as each one lands. The stages are real; nothing here invents a
  // result, which is why there are no view counts anywhere in it.
  var peRun = document.getElementById('pe-run');
  if (peRun) {
    var DEMOS = {
      yt:   { icon: 'ml-yt',   name: 'YouTube',
              url: 'https://youtube.com/watch?v=your-latest-video',
              title: 'How we doubled our bookings in 60 days',
              pitch: 'A short walkthrough of the two changes that filled our calendar — what we stopped doing, and the one question we started asking every caller.',
              cat: 'Home services', city: 'Phoenix, AZ' },
      blog: { icon: 'ml-blog', name: 'Blog post',
              url: 'https://yoursite.com/blog/questions-customers-ask',
              title: 'The 7 questions every customer asks before buying',
              pitch: 'The questions we hear on every first call, answered in full — so you know what to ask before you hire anybody.',
              cat: 'Professional services', city: 'Austin, TX' },
      ig:   { icon: 'ml-ig',   name: 'Instagram',
              url: 'https://instagram.com/p/your-latest-post',
              title: 'Behind the scenes: our new spring collection',
              pitch: 'Eight new pieces, photographed in the workshop where they were made, with the story behind each one.',
              cat: 'Retail & shopping', city: 'Portland, OR' },
      prod: { icon: 'ml-prod', name: 'Product',
              url: 'https://yourstore.com/products/oak-dining-table',
              title: 'Handmade oak dining table — now in three finishes',
              pitch: 'Built to order from solid oak, seats six, and it ships flat-packed with the legs already fitted.',
              cat: 'Home & garden', city: 'Nashville, TN' }
    };

    var $  = function (id) { return document.getElementById(id); };
    var card = $('pe-card'), stages = $('pe-stages'), picks = document.querySelector('.pe-picks');
    var runText = $('pe-run-text'), status = $('pe-status');
    var current = 'yt', timers = [], running = false;
    var slow = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    var setText = function (id, t) { var e = $(id); if (e) e.textContent = t; };
    var setUse  = function (id, sym) { var e = $(id); if (e) e.setAttribute('href', '#' + sym); };

    // Turn a pasted URL into the headline the engine would start from: take the
    // last meaningful path segment, drop any extension, swap separators for
    // spaces and capitalise. This is the one part of the preview that genuinely
    // responds to what somebody typed, which is the point — paste your own link
    // and the card is about your page, not ours.
    //
    // Returns {host, title} with title null when the path has nothing to read:
    // an opaque id, or a routing word like /watch or /index that names the
    // site's plumbing rather than the page.
    var PE_STOP = ['watch', 'index', 'home', 'page', 'post', 'posts', 'view', 'item',
                   'default', 'p', 'en', 'video', 'shorts', 'reel', 'status'];
    var fromUrl = function (raw) {
      var url = String(raw || '').trim();
      if (!url) return null;
      var u;
      try { u = new URL(url.indexOf('//') === -1 ? 'https://' + url : url); }
      catch (e) { return null; }
      var host = u.hostname.replace(/^www\./, '');
      var parts = u.pathname.split('/').filter(Boolean);
      var out = { host: host, title: null };

      // Work backwards: the last segment is usually the slug, but on /blog/p/1234
      // style paths it is a number and the one before it is the real name.
      for (var i = parts.length - 1; i >= 0; i--) {
        var slug = decodeURIComponent(parts[i])
          .replace(/\.[a-z0-9]{2,5}$/i, '')
          .replace(/[-_+]+/g, ' ')
          .replace(/\s+/g, ' ')
          .trim();
        if (!slug || slug.length < 3) continue;
        if (PE_STOP.indexOf(slug.toLowerCase()) !== -1) continue;
        var words = slug.split(' ');
        // One long word mixing letters and digits is an id, not a title.
        var opaque = words.length === 1 &&
          (/^\d+$/.test(slug) || (slug.length >= 8 && /\d/.test(slug) && /[a-z]/i.test(slug)));
        if (opaque) continue;
        out.title = slug.charAt(0).toUpperCase() + slug.slice(1);
        break;
      }
      return out;
    };

    var clear = function () {
      timers.forEach(clearTimeout);
      timers = [];
      stages.querySelectorAll('li').forEach(function (li) { li.classList.remove('on', 'done'); });
      card.classList.remove('pe-done', 'pe-working');
    };

    // Fill the card with a demo's content. `blank` leaves the written parts
    // empty, so a run visibly produces them rather than revealing text that
    // was already sitting there.
    var fill = function (key, blank) {
      var d = DEMOS[key];
      if (!d) return;
      setText('pe-url', d.url);
      setText('pe-badge-text', d.name);
      setUse('pe-badge-icon', d.icon);
      setText('pe-title', blank ? '' : d.title);
      setText('pe-pitch', blank ? '' : d.pitch);
      setText('pe-cat',   blank ? '' : d.cat);
      setText('pe-city',  blank ? '' : d.city);
    };

    var at = function (ms, fn) { timers.push(setTimeout(fn, slow ? 0 : ms)); };

    var run = function () {
      if (running) return;
      running = true;
      clear();
      fill(current, true);
      card.classList.add('pe-working');
      status.textContent = 'Working';
      runText.textContent = 'Writing…';
      peRun.disabled = true;

      var d = DEMOS[current];
      var typed = $('pe-url').value.trim();
      var own   = typed !== '' && typed !== DEMOS[current].url;
      var mine  = own ? fromUrl(typed) : null;
      var title = (mine && mine.title) ? mine.title : d.title;
      var pitch = (mine && mine.title)
        ? 'A page on ' + (mine.host || 'your site') + ' about ' + mine.title.toLowerCase()
          + ' \u2014 written up as a short pitch and put in front of people searching for it.'
        : d.pitch;

      var step = function (name, ms, fn) {
        at(ms, function () {
          var li = stages.querySelector('[data-stage="' + name + '"]');
          stages.querySelectorAll('li').forEach(function (o) { o.classList.remove('on'); });
          if (li) { li.classList.add('on'); }
          if (fn) fn();
        });
        at(ms + (slow ? 0 : 620), function () {
          var li = stages.querySelector('[data-stage="' + name + '"]');
          if (li) { li.classList.remove('on'); li.classList.add('done'); }
        });
      };

      // A pasted link is not the example's platform any more, so the badge
      // stops claiming YouTube over a URL that plainly is not YouTube.
      if (own && mine && mine.host) {
        setText('pe-badge-text', mine.host);
        setUse('pe-badge-icon', 'ml-link');
      }

      step('read',  0);
      step('write', 700,  function () { setText('pe-title', title); setText('pe-pitch', pitch); });
      step('file',  1400, function () { setText('pe-cat', d.cat); setText('pe-city', d.city); });
      step('send',  2100);
      at(2750, function () {
        card.classList.remove('pe-working');
        card.classList.add('pe-done');
        status.textContent = 'Published';
        runText.textContent = 'Run it again';
        peRun.disabled = false;
        running = false;
      });
    };

    picks.addEventListener('click', function (ev) {
      var btn = ev.target.closest('[data-demo]');
      if (!btn) return;
      picks.querySelectorAll('[data-demo]').forEach(function (b) {
        var on = b === btn;
        b.classList.toggle('on', on);
        b.setAttribute('aria-pressed', on ? 'true' : 'false');
      });
      current = btn.getAttribute('data-demo');
      running = false;
      peRun.disabled = false;
      clear();
      fill(current, false);
      status.textContent = 'Ready';
      runText.textContent = 'Write my promotion';
    });

    peRun.addEventListener('click', run);
    $('pe-url').addEventListener('keydown', function (ev) {
      if (ev.key === 'Enter') { ev.preventDefault(); run(); }
    });

    // Run it once when the section first comes into view, so somebody who
    // never presses anything still sees what the thing does.
    if (window.IntersectionObserver && !slow) {
      var io = new IntersectionObserver(function (es) {
        es.forEach(function (e) {
          if (!e.isIntersecting) return;
          io.disconnect();
          run();
        });
      }, { threshold: 0.35 });
      io.observe(document.querySelector('.pe-demo'));
    }
  }

  // engine would write for it. Content only — no counters, no progress bar and
  // no invented results. The previous version animated made-up view counts and
  // then admitted in small print that they were made up, which is a strange
  // thing to teach somebody on a page asking them to trust its numbers.
  var pePicks = document.querySelector('.pe-picks');
  if (pePicks) {
    var DEMOS = {
      yt:   { icon: 'ml-yt',   name: 'YouTube',   grad: 'linear-gradient(135deg,#2563eb,#7c3aed)',
              url: 'https://youtube.com/watch?v=your-latest-video',
              title: 'How we doubled our bookings in 60 days',
              pitch: 'A short walkthrough of the two changes that filled our calendar \u2014 what we stopped doing, and the one question we started asking every caller.',
              cat: 'Home services', city: 'Phoenix, AZ' },
      blog: { icon: 'ml-blog', name: 'Blog post',  grad: 'linear-gradient(135deg,#0ca678,#2563eb)',
              url: 'https://yoursite.com/blog/questions-customers-ask',
              title: 'The 7 questions every customer asks before buying',
              pitch: 'The questions we hear on every first call, answered in full \u2014 so you know what to ask before you hire anybody.',
              cat: 'Professional services', city: 'Austin, TX' },
      ig:   { icon: 'ml-ig',   name: 'Instagram', grad: 'linear-gradient(135deg,#db2777,#7c3aed)',
              url: 'https://instagram.com/p/your-latest-post',
              title: 'Behind the scenes: our new spring collection',
              pitch: 'Eight new pieces, photographed in the workshop where they were made, with the story behind each one.',
              cat: 'Retail & shopping', city: 'Portland, OR' },
      prod: { icon: 'ml-prod', name: 'Product',   grad: 'linear-gradient(135deg,#f59e0b,#db2777)',
              url: 'https://yourstore.com/products/oak-dining-table',
              title: 'Handmade oak dining table \u2014 now in three finishes',
              pitch: 'Built to order from solid oak, seats six, and it ships flat-packed with the legs already fitted.',
              cat: 'Home & garden', city: 'Nashville, TN' }
    };

    var setText = function (id, txt) { var el = document.getElementById(id); if (el) el.textContent = txt; };
    var setUse  = function (id, symbol) { var el = document.getElementById(id); if (el) el.setAttribute('href', '#' + symbol); };

    var showDemo = function (key) {
      var d = DEMOS[key];
      if (!d) return;
      setText('pe-url', d.url);
      setText('pe-title', d.title);
      setText('pe-pitch', d.pitch);
      setText('pe-cat', d.cat);
      setText('pe-city', d.city);
      setText('pe-badge-text', d.name);
      setUse('pe-badge-icon', d.icon);
      setUse('pe-thumb-icon', d.icon);
      var thumb = document.getElementById('pe-thumb');
      if (thumb) thumb.style.background = d.grad;
    };

    pePicks.addEventListener('click', function (ev) {
      var btn = ev.target.closest('[data-demo]');
      if (!btn) return;
      pePicks.querySelectorAll('[data-demo]').forEach(function (b) {
        var on = b === btn;
        b.classList.toggle('on', on);
        b.setAttribute('aria-pressed', on ? 'true' : 'false');
      });
      showDemo(btn.getAttribute('data-demo'));
    });
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
      // A country page can list regions and cities under separate headings.
      // Hide a heading once its own list has nothing left in it, or filtering
      // leaves "By city" standing over empty space.
      target.querySelectorAll('.geo-group').forEach(function (h) {
        var list = h.nextElementSibling, any = false;
        if (list) list.querySelectorAll('a').forEach(function (a) { if (a.style.display !== 'none') any = true; });
        h.style.display = any ? '' : 'none';
        if (list) list.style.display = any ? '' : 'none';
      });
    });
  });
});
